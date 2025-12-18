<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/config.php';

class UploadAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(['error' => 'Método no permitido'], 405);
            return;
        }

        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'upload_foto':
                $this->uploadFoto();
                break;
            case 'upload_archivo':
                $this->uploadArchivo();
                break;
            default:
                $this->sendResponse(['error' => 'Acción no válida'], 400);
                break;
        }
    }

    private function uploadFoto() {
        try {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $this->sendResponse(['error' => 'No se recibió archivo o hay un error'], 400);
                return;
            }

            $empleadoId = $_POST['empleado_id'] ?? null;
            if (!$empleadoId) {
                $this->sendResponse(['error' => 'ID de empleado requerido'], 400);
                return;
            }

            // Verificar que el empleado existe
            $stmt = $this->conn->prepare("SELECT id, foto FROM empleados WHERE id = ?");
            $stmt->execute([$empleadoId]);
            $empleado = $stmt->fetch();

            if (!$empleado) {
                $this->sendResponse(['error' => 'Empleado no encontrado'], 404);
                return;
            }

            $file = $_FILES['foto'];
            
            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                $this->sendResponse(['error' => 'Tipo de archivo no permitido. Solo JPG, PNG, GIF y WebP'], 400);
                return;
            }

            // Validar tamaño (5MB máximo)
            if ($file['size'] > 5 * 1024 * 1024) {
                $this->sendResponse(['error' => 'Archivo muy grande. Máximo 5MB'], 400);
                return;
            }

            // Eliminar foto anterior si existe
            if (!empty($empleado['foto'])) {
                $oldPhotoPath = UPLOAD_PATH . 'fotos/' . $empleado['foto'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'foto_' . $empleadoId . '_' . time() . '.' . $extension;
            $uploadPath = UPLOAD_PATH . 'fotos/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Actualizar base de datos
                $stmt = $this->conn->prepare("UPDATE empleados SET foto = ? WHERE id = ?");
                $stmt->execute([$filename, $empleadoId]);

                $this->sendResponse([
                    'success' => true,
                    'filename' => $filename,
                    'message' => 'Foto subida exitosamente'
                ]);
            } else {
                $this->sendResponse(['error' => 'Error al subir archivo'], 500);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al procesar foto: ' . $e->getMessage()], 500);
        }
    }

    private function uploadArchivo() {
        try {
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                $this->sendResponse(['error' => 'No se recibió archivo o hay un error'], 400);
                return;
            }

            $empleadoId = $_POST['empleado_id'] ?? null;
            $tipoCampoId = $_POST['tipo_campo_id'] ?? null;

            if (!$empleadoId || !$tipoCampoId) {
                $this->sendResponse(['error' => 'ID de empleado y tipo de campo requeridos'], 400);
                return;
            }

            // Verificar que el empleado existe
            $stmt = $this->conn->prepare("SELECT id FROM empleados WHERE id = ?");
            $stmt->execute([$empleadoId]);
            if (!$stmt->fetch()) {
                $this->sendResponse(['error' => 'Empleado no encontrado'], 404);
                return;
            }

            // Verificar que el tipo de campo existe y es de tipo archivo
            $stmt = $this->conn->prepare("SELECT id, nombre FROM tipos_campo WHERE id = ? AND tipo = 'archivo' AND activo = 1");
            $stmt->execute([$tipoCampoId]);
            $tipoCampo = $stmt->fetch();

            if (!$tipoCampo) {
                $this->sendResponse(['error' => 'Tipo de campo no válido'], 400);
                return;
            }

            $file = $_FILES['archivo'];
            
            // Validar tipo de archivo
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain'
            ];

            if (!in_array($file['type'], $allowedTypes)) {
                $this->sendResponse(['error' => 'Tipo de archivo no permitido'], 400);
                return;
            }

            // Validar tamaño (10MB máximo)
            if ($file['size'] > 10 * 1024 * 1024) {
                $this->sendResponse(['error' => 'Archivo muy grande. Máximo 10MB'], 400);
                return;
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'archivo_' . $empleadoId . '_' . $tipoCampoId . '_' . time() . '.' . $extension;
            $uploadPath = UPLOAD_PATH . 'archivos/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Guardar información del archivo en la base de datos
                $stmt = $this->conn->prepare("INSERT INTO archivos_empleados (empleado_id, tipo_campo_id, nombre_original, nombre_archivo, tipo_mime, tamano, ruta) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $empleadoId,
                    $tipoCampoId,
                    $file['name'],
                    $filename,
                    $file['type'],
                    $file['size'],
                    'uploads/archivos/' . $filename
                ]);

                // Actualizar el campo adicional del empleado
                $stmt = $this->conn->prepare("
                    INSERT INTO empleado_campos_adicionales (empleado_id, tipo_campo_id, valor) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");
                $stmt->execute([$empleadoId, $tipoCampoId, $filename]);

                $archivoId = $this->conn->lastInsertId();

                $this->sendResponse([
                    'success' => true,
                    'archivo_id' => $archivoId,
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'message' => 'Archivo subido exitosamente'
                ]);
            } else {
                $this->sendResponse(['error' => 'Error al subir archivo'], 500);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al procesar archivo: ' . $e->getMessage()], 500);
        }
    }

    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

$api = new UploadAPI();
$api->handleRequest();
?>