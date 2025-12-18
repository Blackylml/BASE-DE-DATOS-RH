<?php
require_once '../config/database.php';
require_once '../config/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

class DownloadAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendError('Método no permitido', 405);
            return;
        }

        $fileId = $_GET['id'] ?? null;
        if (!$fileId) {
            $this->sendError('ID de archivo requerido', 400);
            return;
        }

        $this->downloadFile($fileId);
    }

    private function downloadFile($fileId) {
        try {
            // Obtener información del archivo
            $stmt = $this->conn->prepare("
                SELECT ae.*, e.nombre as empleado_nombre, tc.nombre as campo_nombre 
                FROM archivos_empleados ae
                JOIN empleados e ON ae.empleado_id = e.id
                JOIN tipos_campo tc ON ae.tipo_campo_id = tc.id
                WHERE ae.id = ?
            ");
            $stmt->execute([$fileId]);
            $archivo = $stmt->fetch();

            if (!$archivo) {
                $this->sendError('Archivo no encontrado', 404);
                return;
            }

            $rutaCompleta = __DIR__ . '/../' . $archivo['ruta'];
            
            if (!file_exists($rutaCompleta)) {
                $this->sendError('Archivo físico no encontrado', 404);
                return;
            }

            // Configurar headers para descarga
            header('Content-Type: ' . $archivo['tipo_mime']);
            header('Content-Disposition: attachment; filename="' . $archivo['nombre_original'] . '"');
            header('Content-Length: ' . filesize($rutaCompleta));
            header('Cache-Control: no-cache');

            // Enviar archivo
            readfile($rutaCompleta);
            exit;

        } catch (Exception $e) {
            $this->sendError('Error al descargar archivo: ' . $e->getMessage(), 500);
        }
    }

    private function sendError($message, $code) {
        http_response_code($code);
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    }
}

$api = new DownloadAPI();
$api->handleRequest();
?>