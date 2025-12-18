<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    require_once '../lib/SimpleXLSX.php';
    
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
    
    // Leer archivo Excel
    $xlsx = SimpleXLSX::parse($file['tmp_name']);
    
    if (!$xlsx) {
        throw new Exception('No se pudo leer el archivo Excel');
    }
    
    $rows = $xlsx->rows();
    
    if (empty($rows)) {
        throw new Exception('Archivo vacío');
    }
    
    // Primera fila son los headers
    $headers = array_shift($rows);
    
    // Mapeo simple y directo
    $columnMap = [
        'FICHA' => 0,
        'NOMBRE' => 1,
        'ESCOLARIDAD' => 2,
        'SEXO' => 3,
        'DIRECCION' => 4,
        'AÑO NACIMIENTO' => 5,
        'EDAD' => 6,
        'CUMPLEAÑOS' => 7,
        'AREA' => 8,
        'DEPARTAMENTO' => 9,
        'PUESTO' => 10,
        'MENSUAL CON BD' => 11,
        'MENSUAL SIN BD' => 12,
        'SALARIO REAL EXCEL' => 13,
        'BD' => 14,
        'SUELDO REAL MAS BD' => 15,
        'CATEGORIAS' => 16,
        'SUELDO MICROSIP' => 17,
        'S.D.' => 18,
        'SDI' => 19,
        'JEFE DIRECTO' => 20,
        'DIA' => 21,
        'MES' => 22,
        'AÑO' => 23,
        'NOMINA' => 24,
        'CURP' => 25,
        'RFC' => 26,
        'IMSS' => 27,
        'NUMERO' => 28,
        'CONTACTO' => 29,
        'REGISTRO PATRONAL' => 30
    ];
    
    $imported = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        if (empty($row) || count($row) < 31) {
            continue; // Saltar filas vacías o incompletas
        }
        
        // Obtener valores básicos
        $ficha = trim($row[0] ?? '');
        $nombre = trim($row[1] ?? '');
        
        if (empty($ficha) || empty($nombre)) {
            continue; // Saltar si no hay ficha o nombre
        }
        
        // Verificar si ya existe
        $stmt = $conn->prepare("SELECT id FROM empleados WHERE ficha = ?");
        $stmt->execute([$ficha]);
        if ($stmt->fetch()) {
            $errors[] = "Fila " . ($index + 2) . ": Ficha '$ficha' ya existe";
            continue;
        }
        
        // Construir fecha de ingreso
        $dia = (int)trim($row[21] ?? '0');
        $mes = (int)trim($row[22] ?? '0');
        $ano = (int)trim($row[23] ?? '0');
        
        $fechaIngreso = null;
        if ($dia > 0 && $mes > 0 && $ano > 0) {
            if (checkdate($mes, $dia, $ano)) {
                $fechaIngreso = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            }
        }
        
        // Preparar datos para inserción
        $data = [
            trim($row[0] ?? ''),  // ficha
            trim($row[1] ?? ''),  // nombre
            trim($row[2] ?? ''),  // escolaridad
            trim($row[3] ?? ''),  // sexo
            trim($row[4] ?? ''),  // direccion
            (int)(trim($row[5] ?? '') ?: null),  // ano_nacimiento
            (int)(trim($row[6] ?? '') ?: null),  // edad
            trim($row[7] ?? '') ?: null,  // cumpleanos
            trim($row[8] ?? ''),  // area
            trim($row[9] ?? ''),  // departamento
            trim($row[10] ?? ''), // puesto
            (float)(trim($row[11] ?? '') ?: 0), // mensual_con_bd
            (float)(trim($row[12] ?? '') ?: 0), // mensual_sin_bd
            (float)(trim($row[13] ?? '') ?: 0), // salario_real_excel
            (float)(trim($row[14] ?? '') ?: 0), // bd
            (float)(trim($row[15] ?? '') ?: 0), // sueldo_real_mas_bd
            trim($row[16] ?? ''), // categorias
            (float)(trim($row[17] ?? '') ?: 0), // sueldo_microsip
            (float)(trim($row[18] ?? '') ?: 0), // sd
            (float)(trim($row[19] ?? '') ?: 0), // sdi
            trim($row[20] ?? ''), // jefe_directo
            $dia, // dia_ingreso
            $mes, // mes_ingreso
            $ano, // ano_ingreso
            $fechaIngreso, // fecha_ingreso
            trim($row[24] ?? ''), // nomina
            trim($row[25] ?? ''), // curp
            trim($row[26] ?? ''), // rfc
            trim($row[27] ?? ''), // imss
            trim($row[28] ?? ''), // numero
            trim($row[29] ?? ''), // contacto
            trim($row[30] ?? ''), // registro_patronal
            null // foto
        ];
        
        // Insertar en base de datos
        $sql = "INSERT INTO empleados (
            ficha, nombre, escolaridad, sexo, direccion, ano_nacimiento, edad, cumpleanos,
            area, departamento, puesto, mensual_con_bd, mensual_sin_bd, salario_real_excel,
            bd, sueldo_real_mas_bd, categorias, sueldo_microsip, sd, sdi, jefe_directo,
            dia_ingreso, mes_ingreso, ano_ingreso, fecha_ingreso, nomina, curp, rfc, imss,
            numero, contacto, registro_patronal, foto
        ) VALUES (" . str_repeat('?,', 32) . "?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt->execute($data)) {
            $imported++;
        } else {
            $errors[] = "Fila " . ($index + 2) . ": Error al insertar datos";
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'total_rows' => count($rows),
        'errors' => $errors,
        'message' => "Importación completada: $imported empleados importados"
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>