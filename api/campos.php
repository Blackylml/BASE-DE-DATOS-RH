<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/config.php';

class CamposAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        // Si no hay action en POST, revisar en JSON
        if (empty($action) && $method === 'POST') {
            $json = $this->getPostData();
            $action = $json['action'] ?? '';
        }

        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        $this->getCampos();
                        break;
                    default:
                        $this->getCampos();
                        break;
                }
                break;
            
            case 'POST':
                switch ($action) {
                    case 'create':
                        $this->createCampo();
                        break;
                    case 'update':
                        $this->updateCampo();
                        break;
                    case 'delete':
                        $this->deleteCampo();
                        break;
                    case 'toggle':
                        $this->toggleCampo();
                        break;
                    default:
                        $this->sendResponse(['error' => 'Acción no válida'], 400);
                        break;
                }
                break;
        }
    }

    private function getCampos() {
        try {
            $includeInactive = $_GET['include_inactive'] ?? false;
            
            $sql = "SELECT tc.*, 
                           COUNT(eca.id) as uso_count
                    FROM tipos_campo tc
                    LEFT JOIN empleado_campos_adicionales eca ON tc.id = eca.tipo_campo_id
                    WHERE 1=1";
            
            if (!$includeInactive) {
                $sql .= " AND tc.activo = 1";
            }
            
            $sql .= " GROUP BY tc.id ORDER BY tc.nombre ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $campos = $stmt->fetchAll();
            
            foreach ($campos as &$campo) {
                if (!empty($campo['opciones'])) {
                    $campo['opciones'] = json_decode($campo['opciones'], true);
                } else {
                    $campo['opciones'] = [];
                }
            }
            
            $this->sendResponse(['campos' => $campos]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al obtener campos: ' . $e->getMessage()], 500);
        }
    }

    private function createCampo() {
        try {
            $data = $this->getPostData();
            
            $nombre = sanitizeInput($data['nombre'] ?? '');
            $tipo = sanitizeInput($data['tipo'] ?? '');
            $opciones = $data['opciones'] ?? [];
            
            if (empty($nombre) || empty($tipo)) {
                $this->sendResponse(['error' => 'Nombre y tipo son requeridos'], 400);
                return;
            }
            
            $tiposPermitidos = ['texto', 'numero', 'fecha', 'archivo', 'select', 'textarea', 'multiple'];
            if (!in_array($tipo, $tiposPermitidos)) {
                $this->sendResponse(['error' => 'Tipo no válido'], 400);
                return;
            }
            
            // Verificar que no exista un campo con el mismo nombre
            $stmt = $this->conn->prepare("SELECT id FROM tipos_campo WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetch()) {
                $this->sendResponse(['error' => 'Ya existe un campo con ese nombre'], 400);
                return;
            }
            
            $opcionesJson = null;
            if (in_array($tipo, ['select', 'multiple']) && !empty($opciones)) {
                $opcionesJson = json_encode($opciones, JSON_UNESCAPED_UNICODE);
            }
            
            $stmt = $this->conn->prepare("INSERT INTO tipos_campo (nombre, tipo, opciones) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $tipo, $opcionesJson]);
            
            $campoId = $this->conn->lastInsertId();
            
            $this->sendResponse([
                'success' => true, 
                'id' => $campoId, 
                'message' => 'Campo creado exitosamente'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al crear campo: ' . $e->getMessage()], 500);
        }
    }

    private function updateCampo() {
        try {
            $data = $this->getPostData();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->sendResponse(['error' => 'ID requerido'], 400);
                return;
            }
            
            $nombre = sanitizeInput($data['nombre'] ?? '');
            $tipo = sanitizeInput($data['tipo'] ?? '');
            $opciones = $data['opciones'] ?? [];
            
            if (empty($nombre) || empty($tipo)) {
                $this->sendResponse(['error' => 'Nombre y tipo son requeridos'], 400);
                return;
            }
            
            // Verificar que no exista otro campo con el mismo nombre
            $stmt = $this->conn->prepare("SELECT id FROM tipos_campo WHERE nombre = ? AND id != ?");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetch()) {
                $this->sendResponse(['error' => 'Ya existe otro campo con ese nombre'], 400);
                return;
            }
            
            $opcionesJson = null;
            if (in_array($tipo, ['select', 'multiple']) && !empty($opciones)) {
                $opcionesJson = json_encode($opciones, JSON_UNESCAPED_UNICODE);
            }
            
            $stmt = $this->conn->prepare("UPDATE tipos_campo SET nombre = ?, tipo = ?, opciones = ? WHERE id = ?");
            $stmt->execute([$nombre, $tipo, $opcionesJson, $id]);
            
            $this->sendResponse([
                'success' => true, 
                'message' => 'Campo actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al actualizar campo: ' . $e->getMessage()], 500);
        }
    }

    private function deleteCampo() {
        try {
            $data = $this->getPostData();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->sendResponse(['error' => 'ID requerido'], 400);
                return;
            }
            
            // Verificar si el campo está siendo usado
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM empleado_campos_adicionales WHERE tipo_campo_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $this->sendResponse(['error' => 'No se puede eliminar un campo que está siendo usado'], 400);
                return;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM tipos_campo WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $this->sendResponse(['success' => true, 'message' => 'Campo eliminado exitosamente']);
            } else {
                $this->sendResponse(['error' => 'Campo no encontrado'], 404);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al eliminar campo: ' . $e->getMessage()], 500);
        }
    }

    private function toggleCampo() {
        try {
            $data = $this->getPostData();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                $this->sendResponse(['error' => 'ID requerido'], 400);
                return;
            }
            
            $stmt = $this->conn->prepare("UPDATE tipos_campo SET activo = NOT activo WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $this->sendResponse(['success' => true, 'message' => 'Estado del campo actualizado']);
            } else {
                $this->sendResponse(['error' => 'Campo no encontrado'], 404);
            }
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Error al cambiar estado del campo'], 500);
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

    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}

$api = new CamposAPI();
$api->handleRequest();
?>