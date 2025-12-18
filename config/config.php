<?php
session_start();

define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!file_exists(UPLOAD_PATH . 'fotos/')) {
    mkdir(UPLOAD_PATH . 'fotos/', 0777, true);
}
if (!file_exists(UPLOAD_PATH . 'archivos/')) {
    mkdir(UPLOAD_PATH . 'archivos/', 0777, true);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function uploadFile($file, $directory = 'archivos') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir archivo'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Archivo muy grande'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . $directory . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename, 'original_name' => $file['name']];
    }

    return ['success' => false, 'error' => 'Error al mover archivo'];
}
?>