<?php
// Solo establecer headers de JSON si no es una exportación
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action !== 'export') {
    header('Content-Type: application/json');
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/config.php';

// Cargar PhpSpreadsheet si está disponible
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

class EmpleadosAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        
        if ($this->conn === null) {
            error_log("ERROR: No se pudo establecer conexión a la base de datos");
            $this->sendResponse(['error' => 'Error de conexión a la base de datos'], 500);
            exit;
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'departamentos':
                        $this->getDepartamentos();
                        break;
                    case 'campos_adicionales':
                        $this->getCamposAdicionales();
                        break;
                    case 'get_file':
                        $this->getFileId();
                        break;
                    case 'export':
                        $this->exportEmpleados();
                        break;
                    default:
                        $this->getEmpleados();
                        break;
                }
                break;
            
            case 'POST':
                switch ($action) {
                    case 'search':
                        $this->searchEmpleados();
                        break;
                    case 'create':
                        $this->createEmpleado();
                        break;
                    case 'update':
                        $this->updateEmpleado();
                        break;
                    case 'delete':
                        $this->deleteEmpleado();
                        break;
                    default:
                        $this->sendResponse(['error' => 'Acción no válida'], 400);
                        break;
                }
                break;
        }
    }

    private function searchEmpleados() {
        try {
            $ficha = trim($_POST['ficha'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $departamento = trim($_POST['departamento'] ?? '');

            $sql = "SELECT e.*,
                           GROUP_CONCAT(
                               JSON_OBJECT(
                                   'id', tc.id,
                                   'nombre', tc.nombre,
                                   'valor', eca.valor,
                                   'tipo', tc.tipo
                               ) SEPARATOR '||'
                           ) as campos_adicionales_json
                    FROM empleados e
                    LEFT JOIN empleado_campos_adicionales eca ON e.id = eca.empleado_id
                    LEFT JOIN tipos_campo tc ON eca.tipo_campo_id = tc.id AND tc.activo = 1
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($ficha) && !empty($nombre)) {
                // Si ambos parámetros vienen (desde admin.php), buscar por OR
                $sql .= " AND (e.ficha LIKE ? OR e.nombre LIKE ?)";
                $params[] = "%$ficha%";
                $params[] = "%$nombre%";
            } else {
                // Búsqueda individual
                if (!empty($ficha)) {
                    $sql .= " AND e.ficha LIKE ?";
                    $params[] = "%$ficha%";
                }
                
                if (!empty($nombre)) {
                    $sql .= " AND e.nombre LIKE ?";
                    $params[] = "%$nombre%";
                }
            }
            
            if (!empty($departamento)) {
                $sql .= " AND e.departamento = ?";
                $params[] = $departamento;
            }
            
            $sql .= " GROUP BY e.id ORDER BY e.nombre ASC LIMIT 50";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $empleados = $stmt->fetchAll();

            // Procesar campos adicionales
            foreach ($empleados as &$empleado) {
                $empleado['campos_adicionales'] = [];
                if (!empty($empleado['campos_adicionales_json'])) {
                    $campos_json = explode('||', $empleado['campos_adicionales_json']);
                    foreach ($campos_json as $campo_json) {
                        if (!empty($campo_json) && $campo_json !== 'null') {
                            $campo = json_decode($campo_json, true);
                            if ($campo) {
                                // Procesar el valor para campos múltiples
                                $campo['valor'] = $this->formatCampoValor($campo['valor'], $campo['tipo']);
                                $empleado['campos_adicionales'][] = $campo;
                            }
                        }
                    }
                }
                unset($empleado['campos_adicionales_json']);
            }

            $this->sendResponse(['empleados' => $empleados]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error en la búsqueda: ' . $e->getMessage()], 500);
        }
    }

    private function getDepartamentos() {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT departamento FROM empleados WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento");
            $stmt->execute();
            $departamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $this->sendResponse(['departamentos' => $departamentos]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al obtener departamentos'], 500);
        }
    }

    private function getEmpleados() {
        try {
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                $stmt = $this->conn->prepare("
                    SELECT e.*, 
                           GROUP_CONCAT(
                               JSON_OBJECT(
                                   'id', tc.id,
                                   'nombre', tc.nombre,
                                   'tipo', tc.tipo,
                                   'valor', eca.valor,
                                   'opciones', tc.opciones
                               ) SEPARATOR '||'
                           ) as campos_adicionales_json
                    FROM empleados e
                    LEFT JOIN empleado_campos_adicionales eca ON e.id = eca.empleado_id
                    LEFT JOIN tipos_campo tc ON eca.tipo_campo_id = tc.id AND tc.activo = 1
                    WHERE e.id = ?
                    GROUP BY e.id
                ");
                $stmt->execute([$id]);
                $empleado = $stmt->fetch();
                
                if ($empleado) {
                    // Procesar campos adicionales
                    $empleado['campos_adicionales'] = [];
                    if (!empty($empleado['campos_adicionales_json'])) {
                        $campos_json = explode('||', $empleado['campos_adicionales_json']);
                        foreach ($campos_json as $campo_json) {
                            if (!empty($campo_json) && $campo_json !== 'null') {
                                $campo = json_decode($campo_json, true);
                                if ($campo) {
                                    // Procesar el valor para campos múltiples
                                    $campo['valor'] = $this->formatCampoValor($campo['valor'], $campo['tipo']);
                                    $empleado['campos_adicionales'][] = $campo;
                                }
                            }
                        }
                    }
                    unset($empleado['campos_adicionales_json']);
                    
                    $this->sendResponse(['empleado' => $empleado]);
                } else {
                    $this->sendResponse(['error' => 'Empleado no encontrado'], 404);
                }
            } else {
                $stmt = $this->conn->prepare("
                    SELECT e.*,
                           GROUP_CONCAT(
                               JSON_OBJECT(
                                   'id', tc.id,
                                   'nombre', tc.nombre,
                                   'tipo', tc.tipo,
                                   'valor', eca.valor,
                                   'opciones', tc.opciones
                               ) SEPARATOR '||'
                           ) as campos_adicionales_json
                    FROM empleados e
                    LEFT JOIN empleado_campos_adicionales eca ON e.id = eca.empleado_id
                    LEFT JOIN tipos_campo tc ON eca.tipo_campo_id = tc.id AND tc.activo = 1
                    GROUP BY e.id
                    ORDER BY e.nombre ASC
                    LIMIT 100
                ");
                $stmt->execute();
                $empleados = $stmt->fetchAll();

                // Procesar campos adicionales para cada empleado
                foreach ($empleados as &$empleado) {
                    $empleado['campos_adicionales'] = [];
                    if (!empty($empleado['campos_adicionales_json'])) {
                        $campos_json = explode('||', $empleado['campos_adicionales_json']);
                        foreach ($campos_json as $campo_json) {
                            if (!empty($campo_json) && $campo_json !== 'null') {
                                $campo = json_decode($campo_json, true);
                                if ($campo && !empty($campo['id'])) {
                                    $empleado['campos_adicionales'][$campo['id']] = $campo['valor'];
                                }
                            }
                        }
                    }
                    unset($empleado['campos_adicionales_json']);
                }

                $this->sendResponse(['empleados' => $empleados]);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al obtener empleados'], 500);
        }
    }

    private function createEmpleado() {
        try {
            $data = $this->getPostData();
            
            // Verificar que la ficha no exista
            $stmt = $this->conn->prepare("SELECT id FROM empleados WHERE ficha = ?");
            $stmt->execute([$data['ficha']]);
            if ($stmt->fetch()) {
                $this->sendResponse(['error' => 'Ya existe un empleado con esa ficha'], 400);
                return;
            }
            
            $campos = [
                'ficha', 'nombre', 'escolaridad', 'sexo', 'direccion', 'ano_nacimiento',
                'edad', 'cumpleanos', 'area', 'departamento', 'puesto', 'mensual_con_bd',
                'mensual_sin_bd', 'salario_real_excel', 'bd', 'sueldo_real_mas_bd',
                'categorias', 'sueldo_microsip', 'sd', 'sdi', 'jefe_directo',
                'dia_ingreso', 'mes_ingreso', 'ano_ingreso', 'fecha_ingreso', 'nomina', 
                'curp', 'rfc', 'imss', 'numero', 'contacto', 'registro_patronal', 'foto'
            ];
            
            $placeholders = str_repeat('?,', count($campos) - 1) . '?';
            $sql = "INSERT INTO empleados (" . implode(',', $campos) . ") VALUES ($placeholders)";
            
            $values = [];
            foreach ($campos as $campo) {
                $values[] = $data[$campo] ?? null;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            
            $empleadoId = $this->conn->lastInsertId();
            
            // Guardar campos adicionales si existen
            if (!empty($data['campos_adicionales'])) {
                error_log("DEBUG: Campos adicionales recibidos para crear: " . json_encode($data['campos_adicionales']));
                $this->saveCamposAdicionales($empleadoId, $data['campos_adicionales']);
            }
            
            // Procesar archivos subidos
            $this->processUploadedFiles($empleadoId);
            
            $this->sendResponse(['success' => true, 'id' => $empleadoId, 'message' => 'Empleado creado exitosamente']);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al crear empleado: ' . $e->getMessage()], 500);
        }
    }

    private function updateEmpleado() {
        try {
            $data = $this->getPostData();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->sendResponse(['error' => 'ID requerido'], 400);
                return;
            }
            
            // Verificar que la ficha no exista en otro empleado
            $stmt = $this->conn->prepare("SELECT id FROM empleados WHERE ficha = ? AND id != ?");
            $stmt->execute([$data['ficha'], $id]);
            if ($stmt->fetch()) {
                $this->sendResponse(['error' => 'Ya existe otro empleado con esa ficha'], 400);
                return;
            }
            
            $campos = [
                'ficha', 'nombre', 'escolaridad', 'sexo', 'direccion', 'ano_nacimiento',
                'edad', 'cumpleanos', 'area', 'departamento', 'puesto', 'mensual_con_bd',
                'mensual_sin_bd', 'salario_real_excel', 'bd', 'sueldo_real_mas_bd',
                'categorias', 'sueldo_microsip', 'sd', 'sdi', 'jefe_directo',
                'dia_ingreso', 'mes_ingreso', 'ano_ingreso', 'fecha_ingreso', 'nomina', 
                'curp', 'rfc', 'imss', 'numero', 'contacto', 'registro_patronal', 'foto'
            ];
            
            $setParts = array_map(function($campo) { return "$campo = ?"; }, $campos);
            $sql = "UPDATE empleados SET " . implode(', ', $setParts) . " WHERE id = ?";
            
            $values = [];
            foreach ($campos as $campo) {
                $values[] = $data[$campo] ?? null;
            }
            $values[] = $id;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            
            // Actualizar campos adicionales
            if (isset($data['campos_adicionales'])) {
                error_log("DEBUG: Campos adicionales recibidos para actualizar: " . json_encode($data['campos_adicionales']));
                
                // Eliminar campos adicionales existentes
                $stmt = $this->conn->prepare("DELETE FROM empleado_campos_adicionales WHERE empleado_id = ?");
                $stmt->execute([$id]);
                
                // Agregar nuevos campos adicionales
                if (!empty($data['campos_adicionales'])) {
                    $this->saveCamposAdicionales($id, $data['campos_adicionales']);
                }
                
                // Procesar archivos subidos
                $this->processUploadedFiles($id);
            }
            
            $this->sendResponse(['success' => true, 'message' => 'Empleado actualizado exitosamente']);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al actualizar empleado: ' . $e->getMessage()], 500);
        }
    }

    private function deleteEmpleado() {
        try {
            $data = $this->getPostData();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->sendResponse(['error' => 'ID requerido'], 400);
                return;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM empleados WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $this->sendResponse(['success' => true, 'message' => 'Empleado eliminado exitosamente']);
            } else {
                $this->sendResponse(['error' => 'Empleado no encontrado'], 404);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al eliminar empleado'], 500);
        }
    }

    private function getCamposAdicionales() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM tipos_campo WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            $campos = $stmt->fetchAll();
            
            foreach ($campos as &$campo) {
                if (!empty($campo['opciones'])) {
                    $campo['opciones'] = json_decode($campo['opciones'], true);
                }
            }
            
            $this->sendResponse(['campos' => $campos]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al obtener campos adicionales'], 500);
        }
    }

    private function saveCamposAdicionales($empleadoId, $campos) {
        foreach ($campos as $campoId => $valor) {
            if (!empty($valor) || $valor === '0') {
                // Validar que el campo existe antes de insertar
                $stmt = $this->conn->prepare("SELECT id FROM tipos_campo WHERE id = ? AND activo = 1");
                $stmt->execute([$campoId]);
                $campoExiste = $stmt->fetch();
                
                if ($campoExiste) {
                    $stmt = $this->conn->prepare("INSERT INTO empleado_campos_adicionales (empleado_id, tipo_campo_id, valor) VALUES (?, ?, ?)");
                    $stmt->execute([$empleadoId, $campoId, $valor]);
                    error_log("DEBUG: Campo adicional insertado - empleado_id: $empleadoId, tipo_campo_id: $campoId, valor: $valor");
                } else {
                    error_log("WARNING: Intentando insertar campo adicional con ID $campoId que no existe o está inactivo. Valor: $valor");
                    // No lanzar excepción, solo registrar el warning y continuar
                }
            }
        }
    }

    private function getFileId() {
        try {
            $empleadoId = $_GET['empleado_id'] ?? null;
            $campoId = $_GET['campo_id'] ?? null;
            
            if (!$empleadoId || !$campoId) {
                $this->sendResponse(['error' => 'Faltan parámetros'], 400);
                return;
            }
            
            $stmt = $this->conn->prepare("
                SELECT id 
                FROM archivos_empleados 
                WHERE empleado_id = ? AND tipo_campo_id = ?
                ORDER BY fecha_creacion DESC
                LIMIT 1
            ");
            $stmt->execute([$empleadoId, $campoId]);
            $archivo = $stmt->fetch();
            
            if ($archivo) {
                $this->sendResponse(['archivo_id' => $archivo['id']]);
            } else {
                $this->sendResponse(['error' => 'Archivo no encontrado'], 404);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al buscar archivo: ' . $e->getMessage()], 500);
        }
    }

    private function processUploadedFiles($empleadoId) {
        try {
            // Verificar que el empleado existe
            $stmt = $this->conn->prepare("SELECT id FROM empleados WHERE id = ?");
            $stmt->execute([$empleadoId]);
            if (!$stmt->fetch()) {
                error_log("ERROR: Empleado $empleadoId no existe");
                throw new Exception("Empleado no existe");
            }
            
            // Procesar archivos que vienen con nombres como 'archivo_X'
            foreach ($_FILES as $fieldName => $file) {
                if (strpos($fieldName, 'archivo_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
                    $tiposCampoId = str_replace('archivo_', '', $fieldName);
                    error_log("DEBUG: Procesando archivo $fieldName para campo $tiposCampoId");
                    
                    // Validar que el ID sea numérico
                    if (!is_numeric($tiposCampoId)) {
                        error_log("ERROR: ID de campo no válido: $tiposCampoId");
                        continue;
                    }
                    
                    // Validar que el campo existe y es de tipo archivo
                    $stmt = $this->conn->prepare("SELECT nombre FROM tipos_campo WHERE id = ? AND tipo = 'archivo' AND activo = 1");
                    $stmt->execute([$tiposCampoId]);
                    $campo = $stmt->fetch();
                    
                    if ($campo) {
                        $this->saveUploadedFile($empleadoId, $tiposCampoId, $file, $campo['nombre']);
                    } else {
                        error_log("ERROR: Campo $tiposCampoId no existe, no es de tipo archivo, o está inactivo");
                        throw new Exception("Campo de archivo no válido: $tiposCampoId");
                    }
                }
            }
        } catch (Exception $e) {
            error_log("ERROR en processUploadedFiles: " . $e->getMessage());
            throw $e; // Re-lanzar para que se maneje en el nivel superior
        }
    }
    
    private function saveUploadedFile($empleadoId, $tiposCampoId, $file, $campoNombre) {
        try {
            error_log("DEBUG: Procesando archivo para empleado $empleadoId, campo $tiposCampoId");
            
            // Verificar que el tipo_campo_id existe y es válido
            $stmt = $this->conn->prepare("SELECT id, nombre FROM tipos_campo WHERE id = ? AND tipo = 'archivo'");
            $stmt->execute([$tiposCampoId]);
            $campo = $stmt->fetch();
            
            if (!$campo) {
                error_log("ERROR: El campo $tiposCampoId no existe o no es de tipo archivo");
                throw new Exception("El campo no existe o no es de tipo archivo");
            }
            
            error_log("DEBUG: Campo validado correctamente: ID={$campo['id']}, Nombre={$campo['nombre']}");
            
            // Validaciones
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                throw new Exception('Archivo demasiado grande');
            }
            
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 
                'application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Tipo de archivo no permitido');
            }
            
            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'emp_' . $empleadoId . '_campo_' . $tiposCampoId . '_' . time() . '.' . $extension;
            $rutaCompleta = __DIR__ . '/../uploads/empleados/' . $nombreArchivo;
            
            // Crear directorio si no existe
            $uploadDir = __DIR__ . '/../uploads/empleados/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                error_log("DEBUG: Directorio de uploads creado");
            }
            
            // Mover archivo
            if (move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
                error_log("DEBUG: Archivo movido exitosamente");
                
                // Guardar en base de datos
                $stmt = $this->conn->prepare(
                    "INSERT INTO archivos_empleados (empleado_id, tipo_campo_id, nombre_original, nombre_archivo, tipo_mime, tamano, ruta) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $empleadoId,
                    $tiposCampoId,
                    $file['name'],
                    $nombreArchivo,
                    $file['type'],
                    $file['size'],
                    'uploads/empleados/' . $nombreArchivo
                ]);
                error_log("DEBUG: Archivo registrado en BD");
                
                // Primero eliminar el campo existente si hay uno
                $stmt = $this->conn->prepare("DELETE FROM empleado_campos_adicionales WHERE empleado_id = ? AND tipo_campo_id = ?");
                $stmt->execute([$empleadoId, $tiposCampoId]);
                error_log("DEBUG: Campo existente eliminado");
                
                // Insertar el nuevo campo - usando el ID validado
                error_log("DEBUG: Intentando insertar campo adicional - empleado_id: $empleadoId, tipo_campo_id: {$campo['id']}, valor: {$file['name']}");
                $stmt = $this->conn->prepare(
                    "INSERT INTO empleado_campos_adicionales (empleado_id, tipo_campo_id, valor) VALUES (?, ?, ?)"
                );
                $stmt->execute([$empleadoId, $campo['id'], $file['name']]);
                error_log("DEBUG: Campo adicional insertado exitosamente");
            } else {
                throw new Exception('Error al subir archivo');
            }
        } catch (Exception $e) {
            error_log("Error al procesar archivo: " . $e->getMessage());
            throw $e; // Re-lanzar la excepción para que se maneje arriba
        }
    }

    private function getPostData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true);
        } else {
            return $_POST;
        }
    }

    private function exportEmpleados() {
        try {
            // Verificar si PhpSpreadsheet está instalado
            if (!file_exists('../vendor/autoload.php')) {
                $this->sendResponse(['error' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet'], 500);
                return;
            }

            // Verificar si las clases están disponibles
            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $this->sendResponse(['error' => 'PhpSpreadsheet no está configurado correctamente'], 500);
                return;
            }

            // Obtener todos los empleados con sus campos adicionales
            $sql = "SELECT e.*,
                           GROUP_CONCAT(
                               CONCAT(tc.nombre, ':', COALESCE(eca.valor, ''))
                               SEPARATOR '||'
                           ) as campos_adicionales_data
                    FROM empleados e
                    LEFT JOIN empleado_campos_adicionales eca ON e.id = eca.empleado_id
                    LEFT JOIN tipos_campo tc ON eca.tipo_campo_id = tc.id AND tc.activo = 1
                    GROUP BY e.id
                    ORDER BY e.nombre ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener todos los campos adicionales activos para crear las columnas
            $stmt = $this->conn->prepare("SELECT nombre FROM tipos_campo WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            $camposAdicionales = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Crear nueva hoja de cálculo
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Lista de Empleados');

            // Definir headers fijos
            $headersFijos = [
                'FICHA', 'NOMBRE', 'ESCOLARIDAD', 'SEXO', 'DIRECCION', 'AÑO NACIMIENTO',
                'EDAD', 'CUMPLEAÑOS', 'AREA', 'DEPARTAMENTO', 'PUESTO', 'MENSUAL CON BD',
                'MENSUAL SIN BD', 'SALARIO REAL EXCEL', 'BD', 'SUELDO REAL MAS BD',
                'CATEGORIAS', 'SUELDO MICROSIP', 'S.D.', 'SDI', 'JEFE DIRECTO',
                'DIA', 'MES', 'AÑO', 'FECHA INGRESO', 'NOMINA', 'CURP', 'RFC', 'IMSS',
                'NUMERO', 'CONTACTO', 'REGISTRO PATRONAL'
            ];

            // Combinar headers fijos con campos adicionales
            $todosLosHeaders = array_merge($headersFijos, $camposAdicionales);

            // Establecer headers en la primera fila
            $columna = 1;
            foreach ($todosLosHeaders as $header) {
                $coordenada = $this->columnNumberToLetter($columna) . '1';
                $sheet->setCellValue($coordenada, $header);
                $columna++;
            }

            // Procesar cada empleado
            $fila = 2;
            foreach ($empleados as $empleado) {
                // Procesar campos adicionales del empleado
                $camposAdicionalEmpleado = [];
                if (!empty($empleado['campos_adicionales_data'])) {
                    $campos = explode('||', $empleado['campos_adicionales_data']);
                    foreach ($campos as $campo) {
                        if (!empty($campo) && strpos($campo, ':') !== false) {
                            list($nombre, $valor) = explode(':', $campo, 2);
                            $camposAdicionalEmpleado[trim($nombre)] = trim($valor);
                        }
                    }
                }

                // Datos fijos del empleado
                $datosFijos = [
                    $empleado['ficha'],
                    $empleado['nombre'],
                    $empleado['escolaridad'],
                    $empleado['sexo'],
                    $empleado['direccion'],
                    $empleado['ano_nacimiento'],
                    $empleado['edad'],
                    $empleado['cumpleanos'],
                    $empleado['area'],
                    $empleado['departamento'],
                    $empleado['puesto'],
                    $empleado['mensual_con_bd'],
                    $empleado['mensual_sin_bd'],
                    $empleado['salario_real_excel'],
                    $empleado['bd'],
                    $empleado['sueldo_real_mas_bd'],
                    $empleado['categorias'],
                    $empleado['sueldo_microsip'],
                    $empleado['sd'],
                    $empleado['sdi'],
                    $empleado['jefe_directo'],
                    $empleado['dia_ingreso'],
                    $empleado['mes_ingreso'],
                    $empleado['ano_ingreso'],
                    $empleado['fecha_ingreso'],
                    $empleado['nomina'],
                    $empleado['curp'],
                    $empleado['rfc'],
                    $empleado['imss'],
                    $empleado['numero'],
                    $empleado['contacto'],
                    $empleado['registro_patronal']
                ];

                // Escribir datos fijos
                $columna = 1;
                foreach ($datosFijos as $dato) {
                    $coordenada = $this->columnNumberToLetter($columna) . $fila;
                    $sheet->setCellValue($coordenada, $dato);
                    $columna++;
                }

                // Escribir campos adicionales
                foreach ($camposAdicionales as $nombreCampo) {
                    $valor = isset($camposAdicionalEmpleado[$nombreCampo]) ? $camposAdicionalEmpleado[$nombreCampo] : '';
                    $coordenada = $this->columnNumberToLetter($columna) . $fila;
                    $sheet->setCellValue($coordenada, $valor);
                    $columna++;
                }

                $fila++;
            }

            // Formatear headers (negrita, fondo azul)
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
            ];
            $ultimaColumna = count($todosLosHeaders);
            $rango = 'A1:' . $this->columnNumberToLetter($ultimaColumna) . '1';
            $sheet->getStyle($rango)->applyFromArray($headerStyle);

            // Ajustar ancho de columnas
            for ($i = 1; $i <= $ultimaColumna; $i++) {
                $letra = $this->columnNumberToLetter($i);
                $sheet->getColumnDimension($letra)->setAutoSize(true);
            }

            // Generar nombre de archivo con fecha
            $fecha = date('Y-m-d_H-i-s');
            $nombreArchivo = "lista_empleados_completa_{$fecha}.xlsx";

            // Generar archivo y enviarlo para descarga
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al exportar empleados: ' . $e->getMessage()], 500);
        }
    }

    private function formatCampoValor($valor, $tipo) {
        // Si el valor es un array (campos múltiples), convertirlo a string legible
        if (is_array($valor)) {
            return implode(', ', $valor);
        }

        // Si el valor es un JSON string, intentar decodificarlo
        if (is_string($valor) && $this->isJson($valor)) {
            $decoded = json_decode($valor, true);
            if (is_array($decoded)) {
                return implode(', ', $decoded);
            }
        }

        // Para archivos, incluir información sobre si es imagen
        if ($tipo === 'archivo' && !empty($valor)) {
            $filename = basename($valor);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (in_array($extension, $imageExtensions)) {
                return json_encode([
                    'filename' => $filename,
                    'isImage' => true,
                    'path' => $valor
                ]);
            } else {
                return json_encode([
                    'filename' => $filename,
                    'isImage' => false,
                    'path' => $valor
                ]);
            }
        }

        return $valor;
    }

    private function isJson($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function columnNumberToLetter($columnNumber) {
        $columnLetter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $columnLetter = chr(65 + ($columnNumber % 26)) . $columnLetter;
            $columnNumber = intval($columnNumber / 26);
        }
        return $columnLetter;
    }

    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

$api = new EmpleadosAPI();
$api->handleRequest();
?>