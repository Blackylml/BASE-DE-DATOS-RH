<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Se mantiene en 0 para producción, los errores se manejan explícitamente.
ob_start();

header('Content-Type: application/json');

// Verificar si PhpSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'PhpSpreadsheet no está instalado. Por favor ejecute: composer require phpoffice/phpspreadsheet'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../config/database.php';
require_once '../vendor/autoload.php';

// Importar las clases necesarias
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    if (!isset($_FILES['excel_file'])) {
        throw new Exception('No se recibió archivo');
    }
    
    $file = $_FILES['excel_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir archivo');
    }
    
    // Verificar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'xlsx') {
        throw new Exception('Solo archivos .xlsx');
    }
    
    // Conectar a base de datos
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a BD');
    }
    
    // Cargar archivo Excel con PhpSpreadsheet
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    
    if ($highestRow < 2) {
        throw new Exception('El archivo debe tener al menos una fila de datos además de los headers');
    }
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    $skipped_reasons = []; // Nuevo array para razones de omisión
    
    // Procesar cada fila (empezando desde la fila 2, saltando headers)
    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            // Leer valores de las celdas
            $ficha = trim($worksheet->getCell('A' . $row)->getCalculatedValue() ?? '');
            $nombre = trim($worksheet->getCell('B' . $row)->getCalculatedValue() ?? '');
            
            // Saltar filas vacías (si tanto ficha como nombre están vacíos)
            if (empty($ficha) && empty($nombre)) {
                continue;
            }
            
            // Validar que la ficha no esté vacía
            if (empty($ficha)) {
                $errors[] = "Fila $row: Se omitió porque la Ficha está vacía.";
                continue;
            }
            
            // ** LÓGICA MEJORADA: VERIFICAR SI LA FICHA YA EXISTE **
            $stmt = $conn->prepare("SELECT id FROM empleados WHERE ficha = ?");
            $stmt->execute([$ficha]);
            if ($stmt->fetch()) {
                $skipped++;
                // Se agrega a un array separado para no confundirlo con errores.
                $skipped_reasons[] = "Fila $row: Ficha '$ficha' ya existe.";
                continue; // Salta a la siguiente fila
            }
            
            // Leer todos los demás campos
            $escolaridad = trim($worksheet->getCell('C' . $row)->getCalculatedValue() ?? '');
            $sexoRaw = trim($worksheet->getCell('D' . $row)->getCalculatedValue() ?? '');
            $direccion = trim($worksheet->getCell('E' . $row)->getCalculatedValue() ?? '');
            
            // Mapear sexo a valores válidos del ENUM
            $sexo = null;
            if (!empty($sexoRaw)) {
                $sexoUpper = strtoupper($sexoRaw);
                if (in_array($sexoUpper, ['M', 'MASCULINO', 'HOMBRE'])) {
                    $sexo = 'M';
                } elseif (in_array($sexoUpper, ['F', 'FEMENINO', 'MUJER'])) {
                    $sexo = 'F';
                } else {
                    $sexo = 'Otro';
                }
            }
            $anoNacimiento = (int)($worksheet->getCell('F' . $row)->getCalculatedValue() ?? 0);
            $edad = (int)($worksheet->getCell('G' . $row)->getCalculatedValue() ?? 0);
            $cumpleanos = $worksheet->getCell('H' . $row)->getCalculatedValue();
            $area = trim($worksheet->getCell('I' . $row)->getCalculatedValue() ?? '');
            $departamento = trim($worksheet->getCell('J' . $row)->getCalculatedValue() ?? '');
            $puesto = trim($worksheet->getCell('K' . $row)->getCalculatedValue() ?? '');
            $mensualConBd = (float)($worksheet->getCell('L' . $row)->getCalculatedValue() ?? 0);
            $mensualSinBd = (float)($worksheet->getCell('M' . $row)->getCalculatedValue() ?? 0);
            $salarioRealExcel = (float)($worksheet->getCell('N' . $row)->getCalculatedValue() ?? 0);
            $bd = (float)($worksheet->getCell('O' . $row)->getCalculatedValue() ?? 0);
            $sueldoRealMasBd = (float)($worksheet->getCell('P' . $row)->getCalculatedValue() ?? 0);
            $categorias = trim($worksheet->getCell('Q' . $row)->getCalculatedValue() ?? '');
            $sueldoMicrosip = (float)($worksheet->getCell('R' . $row)->getCalculatedValue() ?? 0);
            $sd = (float)($worksheet->getCell('S' . $row)->getCalculatedValue() ?? 0);
            $sdi = (float)($worksheet->getCell('T' . $row)->getCalculatedValue() ?? 0);
            $jefeDirecto = trim($worksheet->getCell('U' . $row)->getCalculatedValue() ?? '');
            $diaRaw = $worksheet->getCell('V' . $row)->getCalculatedValue();
            $mesRaw = $worksheet->getCell('W' . $row)->getCalculatedValue();
            $anoRaw = $worksheet->getCell('X' . $row)->getCalculatedValue();
            
            $dia = !empty($diaRaw) ? (int)$diaRaw : 0;
            $ano = !empty($anoRaw) ? (int)$anoRaw : 0;
            
            $mes = 0;
            if (!empty($mesRaw)) {
                if (is_numeric($mesRaw)) {
                    $mes = (int)$mesRaw;
                } else {
                    $mesesTexto = [
                        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
                        'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
                    ];
                    $mesLower = strtolower(trim($mesRaw));
                    $mes = $mesesTexto[$mesLower] ?? 0;
                }
            }
            $nomina = trim($worksheet->getCell('Y' . $row)->getCalculatedValue() ?? '');
            $curp = trim($worksheet->getCell('Z' . $row)->getCalculatedValue() ?? '');
            $rfc = trim($worksheet->getCell('AA' . $row)->getCalculatedValue() ?? '');
            $imss = trim($worksheet->getCell('AB' . $row)->getCalculatedValue() ?? '');
            $numero = trim($worksheet->getCell('AC' . $row)->getCalculatedValue() ?? '');
            $contacto = trim($worksheet->getCell('AD' . $row)->getCalculatedValue() ?? '');
            $registroPatronal = trim($worksheet->getCell('AE' . $row)->getCalculatedValue() ?? '');
            
            $cumpleanosDate = null;
            if (!empty($cumpleanos)) {
                try {
                    if (Date::isDateTime($worksheet->getCell('H' . $row))) {
                        $cumpleanosDate = Date::excelToDateTimeObject($cumpleanos)->format('Y-m-d');
                    } else {
                        $cumpleanosDate = date('Y-m-d', strtotime($cumpleanos));
                    }
                } catch (Exception $e) {
                    $cumpleanosDate = null;
                }
            }
            
            $fechaIngreso = null;
            if ($dia > 0 && $mes > 0 && $ano > 0) {
                if (checkdate($mes, $dia, $ano)) {
                    $fechaIngreso = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                } else {
                    $errors[] = "Fila $row: Fecha de ingreso inválida ($dia/$mes/$ano)";
                }
            }

            // Preparar datos para inserción
            $sql = "INSERT INTO empleados (
                ficha, nombre, escolaridad, sexo, direccion, ano_nacimiento, edad, cumpleanos,
                area, departamento, puesto, mensual_con_bd, mensual_sin_bd, salario_real_excel,
                bd, sueldo_real_mas_bd, categorias, sueldo_microsip, sd, sdi, jefe_directo,
                dia_ingreso, mes_ingreso, ano_ingreso, fecha_ingreso, nomina, curp, rfc, imss,
                numero, contacto, registro_patronal
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $data = [
                $ficha, $nombre, $escolaridad, $sexo, $direccion, $anoNacimiento ?: null, $edad ?: null, $cumpleanosDate,
                $area, $departamento, $puesto, $mensualConBd, $mensualSinBd, $salarioRealExcel,
                $bd, $sueldoRealMasBd, $categorias, $sueldoMicrosip, $sd, $sdi, $jefeDirecto,
                $dia ?: null, $mes ?: null, $ano ?: null, $fechaIngreso, $nomina, $curp, $rfc, $imss,
                $numero, $contacto, $registroPatronal
            ];
            
            $stmt = $conn->prepare($sql);
            if ($stmt->execute($data)) {
                $imported++;
            } else {
                $errorInfo = $stmt->errorInfo();
                $errors[] = "Fila $row: Error al insertar en BD - " . $errorInfo[2];
            }
            
        } catch (Exception $e) {
            $errors[] = "Fila $row: Error de procesamiento - " . $e->getMessage();
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'total_rows' => $highestRow - 1,
        'errors' => $errors,
        'skipped_reasons' => $skipped_reasons, // Enviar las razones de omisión al frontend
        'message' => "Importación completada."
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
