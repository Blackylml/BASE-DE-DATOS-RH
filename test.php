<?php
echo "<h1>Diagnóstico del Sistema DBRH</h1>";

echo "<h2>1. Verificación de PHP</h2>";
echo "Versión de PHP: " . phpversion() . "<br>";

echo "<h2>2. Verificación de Extensiones</h2>";
echo "ZipArchive disponible: " . (class_exists('ZipArchive') ? '✅ SÍ' : '❌ NO') . "<br>";
echo "PDO disponible: " . (extension_loaded('pdo') ? '✅ SÍ' : '❌ NO') . "<br>";
echo "MySQL PDO disponible: " . (extension_loaded('pdo_mysql') ? '✅ SÍ' : '❌ NO') . "<br>";

echo "<h2>3. Verificación de Archivos</h2>";
$files = [
    'config/database.php',
    'config/config.php',
    'lib/SimpleXLSX.php',
    'api/import.php'
];

foreach ($files as $file) {
    echo "$file: " . (file_exists($file) ? '✅ EXISTE' : '❌ NO EXISTE') . "<br>";
}

echo "<h2>4. Verificación de Directorios</h2>";
$dirs = [
    'uploads',
    'uploads/fotos',
    'uploads/archivos'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        echo "$dir: ❌ NO EXISTE - Creando...<br>";
        mkdir($dir, 0777, true);
    } else {
        echo "$dir: ✅ EXISTE<br>";
    }
}

echo "<h2>5. Test de Conexión a Base de Datos</h2>";
try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ Conexión a BD exitosa<br>";
        
        // Test simple query
        $stmt = $conn->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Query de prueba exitosa<br>";
        
        // Verificar tablas
        $stmt = $conn->prepare("SHOW TABLES LIKE 'empleados'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "✅ Tabla 'empleados' existe<br>";
        } else {
            echo "❌ Tabla 'empleados' no existe<br>";
        }
        
    } else {
        echo "❌ Error en conexión a BD<br>";
    }
} catch (Exception $e) {
    echo "❌ Error de BD: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Test de SimpleXLSX</h2>";
try {
    require_once 'lib/SimpleXLSX.php';
    echo "✅ SimpleXLSX cargado correctamente<br>";
    
    if (class_exists('SimpleXLSX')) {
        echo "✅ Clase SimpleXLSX disponible<br>";
    } else {
        echo "❌ Clase SimpleXLSX no disponible<br>";
    }
} catch (Exception $e) {
    echo "❌ Error cargando SimpleXLSX: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Test de API Import</h2>";
if (isset($_POST['test_api'])) {
    echo "Probando API...<br>";
    try {
        require_once 'api/import.php';
        echo "✅ API cargada sin errores<br>";
    } catch (Exception $e) {
        echo "❌ Error en API: " . $e->getMessage() . "<br>";
    }
} else {
    echo '<form method="POST"><button name="test_api" type="submit">Probar cargar API</button></form>';
}

echo "<h2>8. Configuración PHP Relevante</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 20px; }
</style>