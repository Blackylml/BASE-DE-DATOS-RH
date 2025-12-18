<?php
echo "<h1>Diagnóstico de Campos de Empleados</h1>";

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        die("Error de conexión a la base de datos");
    }

    echo "<h2>1. Estructura de la tabla 'empleados'</h2>";
    $stmt = $conn->prepare("DESCRIBE empleados");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Contar empleados
    echo "<h2>2. Estadísticas de Empleados</h2>";
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleados");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>Total de empleados: <strong>" . $result['total'] . "</strong></p>";

    // Mostrar algunos empleados de ejemplo
    if ($result['total'] > 0) {
        echo "<h3>Primeros 5 empleados (campos básicos)</h3>";
        $stmt = $conn->prepare("SELECT id, ficha, nombre, area, departamento, puesto FROM empleados LIMIT 5");
        $stmt->execute();
        $empleados = $stmt->fetchAll();

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Ficha</th><th>Nombre</th><th>Área</th><th>Departamento</th><th>Puesto</th></tr>";
        foreach ($empleados as $emp) {
            echo "<tr>";
            echo "<td>" . $emp['id'] . "</td>";
            echo "<td>" . ($emp['ficha'] ?? '') . "</td>";
            echo "<td>" . ($emp['nombre'] ?? '') . "</td>";
            echo "<td>" . ($emp['area'] ?? '') . "</td>";
            echo "<td>" . ($emp['departamento'] ?? '') . "</td>";
            echo "<td>" . ($emp['puesto'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Verificar campos dinámicos
    echo "<h2>3. Campos Dinámicos (tipos_campo)</h2>";
    $stmt = $conn->prepare("SELECT * FROM tipos_campo ORDER BY id");
    $stmt->execute();
    $tipos_campo = $stmt->fetchAll();

    if (count($tipos_campo) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Opciones</th><th>Activo</th><th>Fecha Creación</th></tr>";
        foreach ($tipos_campo as $campo) {
            echo "<tr>";
            echo "<td>" . $campo['id'] . "</td>";
            echo "<td>" . $campo['nombre'] . "</td>";
            echo "<td>" . $campo['tipo'] . "</td>";
            echo "<td>" . ($campo['opciones'] ?? 'NULL') . "</td>";
            echo "<td>" . ($campo['activo'] ? 'SÍ' : 'NO') . "</td>";
            echo "<td>" . $campo['fecha_creacion'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay campos dinámicos registrados.</p>";
    }

    // Verificar valores de campos adicionales
    echo "<h2>4. Valores de Campos Adicionales</h2>";
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleado_campos_adicionales");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>Total de valores de campos adicionales: <strong>" . $result['total'] . "</strong></p>";

    if ($result['total'] > 0) {
        echo "<h3>Primeros 10 registros</h3>";
        $stmt = $conn->prepare("
            SELECT eca.id, e.nombre as empleado_nombre, tc.nombre as campo_nombre, eca.valor
            FROM empleado_campos_adicionales eca
            JOIN empleados e ON eca.empleado_id = e.id
            JOIN tipos_campo tc ON eca.tipo_campo_id = tc.id
            LIMIT 10
        ");
        $stmt->execute();
        $valores = $stmt->fetchAll();

        if (count($valores) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Empleado</th><th>Campo</th><th>Valor</th></tr>";
            foreach ($valores as $val) {
                echo "<tr>";
                echo "<td>" . $val['id'] . "</td>";
                echo "<td>" . $val['empleado_nombre'] . "</td>";
                echo "<td>" . $val['campo_nombre'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($val['valor'], 0, 100)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }

    // Verificar archivos
    echo "<h2>5. Archivos Adjuntos</h2>";
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM archivos_empleados");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>Total de archivos: <strong>" . $result['total'] . "</strong></p>";

    // Verificar usuarios
    echo "<h2>6. Usuarios del Sistema</h2>";
    $stmt = $conn->prepare("SELECT id, usuario, nombre_completo, activo, fecha_creacion FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Activo</th><th>Fecha Creación</th></tr>";
    foreach ($usuarios as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['usuario'] . "</td>";
        echo "<td>" . ($user['nombre_completo'] ?? '') . "</td>";
        echo "<td>" . ($user['activo'] ? 'SÍ' : 'NO') . "</td>";
        echo "<td>" . $user['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #2c5aa0; margin-top: 30px; border-bottom: 2px solid #2c5aa0; padding-bottom: 5px; }
h3 { color: #555; margin-top: 20px; }
table { margin-top: 10px; font-size: 12px; }
th { background-color: #2c5aa0; color: white; font-weight: bold; }
tr:nth-child(even) { background-color: #f2f2f2; }
p { font-size: 14px; }
</style>
