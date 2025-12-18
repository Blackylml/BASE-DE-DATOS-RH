<?php
// Verificar si PhpSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet']);
    exit;
}

require_once '../vendor/autoload.php';
require_once '../config/database.php';

// Importar las clases necesarias
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    if ($conn === null) {
        throw new Exception('Error de conexión a la base de datos');
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

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los campos adicionales activos para crear las columnas
    $stmt = $conn->prepare("SELECT nombre FROM tipos_campo WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    $camposAdicionales = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Crear nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
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
        $sheet->setCellValueByColumnAndRow($columna, 1, $header);
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
            $sheet->setCellValueByColumnAndRow($columna, $fila, $dato);
            $columna++;
        }

        // Escribir campos adicionales
        foreach ($camposAdicionales as $nombreCampo) {
            $valor = isset($camposAdicionalEmpleado[$nombreCampo]) ? $camposAdicionalEmpleado[$nombreCampo] : '';
            $sheet->setCellValueByColumnAndRow($columna, $fila, $valor);
            $columna++;
        }

        $fila++;
    }

    // Formatear headers (negrita, fondo azul)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
    ];
    $ultimaColumna = count($todosLosHeaders);
    $sheet->getStyle("A1:" . chr(64 + ($ultimaColumna > 26 ? 65 + ($ultimaColumna - 27) : 0)) . chr(65 + (($ultimaColumna - 1) % 26)) . "1")->applyFromArray($headerStyle);

    // Ajustar ancho de columnas
    for ($i = 1; $i <= $ultimaColumna; $i++) {
        $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    // Generar nombre de archivo con fecha
    $fecha = date('Y-m-d_H-i-s');
    $nombreArchivo = "lista_empleados_completa_{$fecha}.xlsx";

    // Generar archivo y enviarlo para descarga
    $writer = new Xlsx($spreadsheet);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');

    $writer->save('php://output');

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al exportar empleados: ' . $e->getMessage()]);
}
?>