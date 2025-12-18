<?php
require_once '../config/database.php';

// Verificar parámetros
$archivo_id = $_GET['id'] ?? null;
$empleado_id = $_GET['empleado_id'] ?? null;
$campo_id = $_GET['campo_id'] ?? null;

if (!$archivo_id && !$empleado_id) {
    http_response_code(400);
    exit('Parámetros requeridos: id o empleado_id');
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($conn === null) {
        http_response_code(500);
        exit('Error de conexión a la base de datos');
    }

    $sql = "SELECT nombre_archivo, tipo_mime, ruta FROM archivos_empleados WHERE ";
    $params = [];

    if ($archivo_id) {
        $sql .= "id = ?";
        $params[] = $archivo_id;
    } else {
        if ($campo_id) {
            $sql .= "empleado_id = ? AND tipo_campo_id = ? ORDER BY fecha_creacion DESC LIMIT 1";
            $params[] = $empleado_id;
            $params[] = $campo_id;
        } else {
            $sql .= "empleado_id = ? ORDER BY fecha_creacion DESC LIMIT 1";
            $params[] = $empleado_id;
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $archivo = $stmt->fetch();

    if (!$archivo) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }

    $filePath = __DIR__ . '/../' . $archivo['ruta'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('Archivo físico no encontrado');
    }

    // Verificar que es una imagen
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    if (!in_array($extension, $imageExtensions)) {
        http_response_code(400);
        exit('El archivo no es una imagen');
    }

    // Determinar el tipo MIME correcto
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp'
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

    // Configurar headers para imagen
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600'); // Cache por 1 hora

    // Enviar la imagen
    readfile($filePath);

} catch (Exception $e) {
    http_response_code(500);
    exit('Error interno del servidor');
}
?>