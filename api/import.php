<?php
// Capturar errores PHP para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Buffers para capturar salida no deseada
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que los archivos existan antes de incluirlos
$configPath = __DIR__ . '/../config/database.php';
$configPathGeneral = __DIR__ . '/../config/config.php';
$simplexlsxPath = __DIR__ . '/../lib/SimpleXLSX.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Archivo de configuración de BD no encontrado']);
    exit;
}

if (!file_exists($configPathGeneral)) {
    http_response_code(500);
    echo json_encode(['error' => 'Archivo de configuración general no encontrado']);
    exit;
}

if (!file_exists($simplexlsxPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Librería SimpleXLSX no encontrada']);
    exit;
}

require_once $configPath;
require_once $configPathGeneral;
require_once $simplexlsxPath;

class ImportAPI {
    private $db;
    private $conn;

    public function __construct() {
        try {
            $this->db = new Database();
            $this->conn = $this->db->getConnection();
            
            if (!$this->conn) {
                throw new Exception('No se pudo conectar a la base de datos');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error de conexión a BD: ' . $e->getMessage()]);
            exit;
        }
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['error' => 'Método no permitido'], 405);
            return;
        }

        $action = $_POST['action'] ?? 'import_excel';
        
        switch ($action) {
            case 'import_excel':
                $this->importFromExcel();
                break;
            default:
                $this->sendResponse(['error' => 'Acción no válida'], 400);
                break;
        }
    }

    private function importFromExcel() {
        try {
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $this->sendResponse(['error' => 'No se recibió archivo Excel o hay un error'], 400);
                return;
            }

            $file = $_FILES['excel_file'];
            
            // Validar extensión de archivo
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension !== 'xlsx') {
                $this->sendResponse(['error' => 'Tipo de archivo no válido. Solo se aceptan archivos Excel (.xlsx)'], 400);
                return;
            }

            // Validar tamaño (20MB máximo)
            if ($file['size'] > 20 * 1024 * 1024) {
                $this->sendResponse(['error' => 'Archivo muy grande. Máximo 20MB'], 400);
                return;
            }

            // Procesar archivo Excel usando SimpleXLSX o similar
            // Para este ejemplo, usaremos un parser CSV simple
            $data = $this->parseExcelFile($file['tmp_name']);
            
            if (empty($data)) {
                $this->sendResponse(['error' => 'No se pudieron leer datos del archivo'], 400);
                return;
            }

            $results = $this->importEmployeeData($data);
            
            $this->sendResponse($results);

        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al procesar archivo: ' . $e->getMessage()], 500);
        }
    }

    private function parseExcelFile($filePath) {
        $data = [];
        
        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if ($extension === 'xlsx') {
                // Verificar que ZipArchive esté disponible
                if (!class_exists('ZipArchive')) {
                    throw new Exception('ZipArchive no está disponible. Se requiere la extensión php-zip para leer archivos Excel.');
                }
                
                // Usar SimpleXLSX para archivos Excel
                $xlsx = SimpleXLSX::parse($filePath);
                
                if ($xlsx === false) {
                    throw new Exception('No se pudo abrir el archivo Excel. Verifique que el archivo no esté corrupto.');
                }
                
                if ($xlsx->hasError()) {
                    throw new Exception('Error en archivo Excel: ' . $xlsx->getErrorMessage());
                }
                
                $rows = $xlsx->rows();
                if (empty($rows)) {
                    throw new Exception('El archivo Excel no contiene datos');
                }
                
                // Primera fila como headers
                $headers = array_shift($rows);
                $headers = array_map(function($header) {
                    return trim(str_replace(["\n", "\r", "\t"], ' ', $header));
                }, $headers);
                
                // Verificar que tenemos headers válidos
                if (empty($headers) || empty(array_filter($headers))) {
                    throw new Exception('El archivo Excel no tiene encabezados válidos en la primera fila');
                }
                
                // Verificar que existe la columna FICHA
                if (!in_array('FICHA', $headers)) {
                    throw new Exception('El archivo Excel debe tener una columna llamada "FICHA"');
                }
                
                // Procesar filas de datos
                $rowNumber = 2; // Empezamos en la fila 2 (después de headers)
                foreach ($rows as $row) {
                    // Skip empty rows
                    if (empty(array_filter($row, function($cell) { return trim($cell) !== ''; }))) {
                        $rowNumber++;
                        continue;
                    }
                    
                    // Asegurar que la fila tenga suficientes columnas
                    while (count($row) < count($headers)) {
                        $row[] = '';
                    }
                    
                    // Crear array asociativo
                    $rowData = [];
                    for ($i = 0; $i < count($headers); $i++) {
                        $cellValue = isset($row[$i]) ? trim($row[$i]) : '';
                        $rowData[$headers[$i]] = $cellValue;
                    }
                    
                    // Solo agregar filas que tengan al menos la ficha
                    if (!empty($rowData['FICHA'])) {
                        $data[] = $rowData;
                    }
                    
                    $rowNumber++;
                }
                
            } elseif ($extension === 'xls') {
                throw new Exception('Archivos .xls no son compatibles. Por favor use formato .xlsx');
                
            } elseif ($extension === 'csv') {
                // Leer CSV directamente
                if (($handle = fopen($filePath, "r")) !== FALSE) {
                    $headers = null;
                    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if ($headers === null) {
                            $headers = array_map('trim', $row);
                        } else {
                            if (count($row) >= count($headers)) {
                                $rowData = array_combine($headers, array_map('trim', $row));
                                if (!empty($rowData['FICHA'])) {
                                    $data[] = $rowData;
                                }
                            }
                        }
                    }
                    fclose($handle);
                } else {
                    throw new Exception('No se pudo abrir el archivo CSV');
                }
                
            } else {
                throw new Exception('Formato de archivo no soportado. Use .xlsx o .csv');
            }
            
        } catch (Exception $e) {
            throw new Exception('Error al procesar archivo: ' . $e->getMessage());
        }
        
        return $data;
    }

    private function importEmployeeData($data) {
        $imported = 0;
        $errors = [];
        $skipped = 0;

        // Mapeo flexible de columnas Excel a campos de BD - Basado en tu archivo
        $columnMapping = [
            // Columnas básicas
            'FICHA' => 'ficha',
            'NOMBRE' => 'nombre',
            'ESCOLARIDAD' => 'escolaridad',
            'SEXO' => 'sexo',
            'DIRECCION' => 'direccion',
            
            // Información personal
            'ANO NACIMIENTO' => 'ano_nacimiento',
            'AÑO NACIMIENTO' => 'ano_nacimiento',
            'EDAD' => 'edad',
            'CUMPLEANOS' => 'cumpleanos',
            'CUMPLEAÑOS' => 'cumpleanos',
            
            // Información laboral
            'AREA' => 'area',
            'DEPARTAMENTO' => 'departamento',
            'DEPTO' => 'departamento',
            'PUESTO' => 'puesto',
            
            // Información salarial
            'MENSUAL CON BD' => 'mensual_con_bd',
            'MENSUAL SIN BD' => 'mensual_sin_bd',
            'SALARIO REAL EXCEL' => 'salario_real_excel',
            'BD' => 'bd',
            'SUELDO REAL MAS BD' => 'sueldo_real_mas_bd',
            'CATEGORIAS' => 'categorias',
            'SUELDO MICROSIP' => 'sueldo_microsip',
            'S.D.' => 'sd',
            'SD' => 'sd',
            'S D' => 'sd',
            'SDI' => 'sdi',
            
            // Jefatura
            'JEFE DIRECTO' => 'jefe_directo',
            
            // Fecha de ingreso (separada)
            'DIA' => 'dia_ingreso',
            'MES' => 'mes_ingreso',
            'ANO' => 'ano_ingreso',
            'AÑO' => 'ano_ingreso',
            
            // Documentos
            'NOMINA' => 'nomina',
            'NÓMINA' => 'nomina',
            'CURP' => 'curp',
            'RFC' => 'rfc',
            'IMSS' => 'imss',
            'NUMERO' => 'numero',
            'NÚMERO' => 'numero',
            'CONTACTO' => 'contacto',
            'REGISTRO PATRONAL' => 'registro_patronal'
        ];


        // Crear mapeo normalizado
        $normalizedMapping = [];
        foreach ($columnMapping as $excelCol => $dbCol) {
            $normalizedMapping[$this->normalizeColumnName($excelCol)] = $dbCol;
        }

        foreach ($data as $index => $row) {
            try {
                $employeeData = [];
                $ficha = null;

                // Mapear datos usando mapeo normalizado
                foreach ($row as $excelCol => $value) {
                    $normalizedCol = $this->normalizeColumnName($excelCol);
                    
                    if (isset($normalizedMapping[$normalizedCol])) {
                        $dbCol = $normalizedMapping[$normalizedCol];
                        
                        // Limpiar y procesar el valor
                        $value = trim($value);
                        
                        if ($dbCol === 'ficha') {
                            $ficha = $value;
                            if (empty($ficha)) {
                                $errors[] = "Fila " . ($index + 2) . ": Ficha vacía";
                                continue 2;
                            }
                        }

                        // Procesar fechas
                        if (in_array($dbCol, ['cumpleanos', 'fecha_ingreso']) && !empty($value)) {
                            $date = $this->parseDate($value);
                            $employeeData[$dbCol] = $date;
                        } 
                        // Procesar números decimales
                        else if (in_array($dbCol, ['mensual_con_bd', 'mensual_sin_bd', 'salario_real_excel', 'bd', 'sueldo_real_mas_bd', 'sueldo_microsip', 'sd', 'sdi'])) {
                            $employeeData[$dbCol] = $this->parseDecimal($value);
                        }
                        // Procesar enteros
                        else if (in_array($dbCol, ['ano_nacimiento', 'edad', 'dia_ingreso', 'mes_ingreso', 'ano_ingreso'])) {
                            $employeeData[$dbCol] = $this->parseInteger($value);
                        }
                        else {
                            $employeeData[$dbCol] = $value ?: null;
                        }
                    }
                }

                // Construir fecha_ingreso a partir de dia, mes, año
                if (!empty($employeeData['dia_ingreso']) && !empty($employeeData['mes_ingreso']) && !empty($employeeData['ano_ingreso'])) {
                    $dia = $employeeData['dia_ingreso'];
                    $mes = $employeeData['mes_ingreso'];
                    $ano = $employeeData['ano_ingreso'];
                    
                    // Validar que sea una fecha válida
                    if (checkdate($mes, $dia, $ano)) {
                        $employeeData['fecha_ingreso'] = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                    } else {
                        $errors[] = "Fila " . ($index + 2) . ": Fecha de ingreso inválida ($dia/$mes/$ano)";
                    }
                }

                // Verificar si ya existe un empleado con esta ficha
                $stmt = $this->conn->prepare("SELECT id FROM empleados WHERE ficha = ?");
                $stmt->execute([$ficha]);
                if ($stmt->fetch()) {
                    $skipped++;
                    $errors[] = "Fila " . ($index + 2) . ": Empleado con ficha '$ficha' ya existe";
                    continue;
                }

                // Insertar empleado
                $this->insertEmployee($employeeData);
                $imported++;

            } catch (Exception $e) {
                $errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_rows' => count($data),
            'message' => "Importación completada: $imported importados, $skipped omitidos"
        ];
    }

    private function insertEmployee($data) {
        $campos = array_keys($data);
        $placeholders = str_repeat('?,', count($campos) - 1) . '?';
        $sql = "INSERT INTO empleados (" . implode(',', $campos) . ") VALUES ($placeholders)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_values($data));
    }

    private function parseDate($dateString) {
        if (empty($dateString)) return null;
        
        // Intentar varios formatos de fecha
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Si es un número (Excel date serial)
        if (is_numeric($dateString)) {
            try {
                $date = new DateTime('1900-01-01');
                $date->add(new DateInterval('P' . (intval($dateString) - 2) . 'D'));
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                return null;
            }
        }
        
        return null;
    }

    private function parseDecimal($value) {
        if (empty($value)) return null;
        
        // Remover caracteres no numéricos excepto punto y coma
        $value = preg_replace('/[^0-9.,-]/', '', $value);
        $value = str_replace(',', '.', $value);
        
        return is_numeric($value) ? floatval($value) : null;
    }

    private function parseInteger($value) {
        if (empty($value)) return null;
        
        $value = preg_replace('/[^0-9]/', '', $value);
        return is_numeric($value) ? intval($value) : null;
    }

    private function normalizeColumnName($name) {
        $name = trim(strtoupper($name));
        $name = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    private function sendResponse($data, $statusCode = 200) {
        // Limpiar cualquier salida previa
        ob_clean();
        
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // Terminar el script para evitar salida adicional
        exit;
    }
}

try {
    $api = new ImportAPI();
    $api->handleRequest();
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fatal de PHP: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>