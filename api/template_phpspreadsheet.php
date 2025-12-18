<?php
// Verificar si PhpSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet']);
    exit;
}

require_once '../vendor/autoload.php';

// Importar las clases necesarias
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Crear nueva hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Empleados');
    
    // Definir headers
    $headers = [
        'A1' => 'FICHA',
        'B1' => 'NOMBRE', 
        'C1' => 'ESCOLARIDAD',
        'D1' => 'SEXO',
        'E1' => 'DIRECCION',
        'F1' => 'AÑO NACIMIENTO',
        'G1' => 'EDAD',
        'H1' => 'CUMPLEAÑOS',
        'I1' => 'AREA',
        'J1' => 'DEPARTAMENTO', 
        'K1' => 'PUESTO',
        'L1' => 'MENSUAL CON BD',
        'M1' => 'MENSUAL SIN BD',
        'N1' => 'SALARIO REAL EXCEL',
        'O1' => 'BD',
        'P1' => 'SUELDO REAL MAS BD',
        'Q1' => 'CATEGORIAS',
        'R1' => 'SUELDO MICROSIP',
        'S1' => 'S.D.',
        'T1' => 'SDI',
        'U1' => 'JEFE DIRECTO',
        'V1' => 'DIA',
        'W1' => 'MES', 
        'X1' => 'AÑO',
        'Y1' => 'NOMINA',
        'Z1' => 'CURP',
        'AA1' => 'RFC',
        'AB1' => 'IMSS',
        'AC1' => 'NUMERO',
        'AD1' => 'CONTACTO',
        'AE1' => 'REGISTRO PATRONAL'
    ];
    
    // Establecer headers
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Datos de ejemplo
    $ejemplos = [
        [
            'A2' => '001',
            'B2' => 'JUAN PEREZ GARCIA',
            'C2' => 'LICENCIATURA',
            'D2' => 'M',
            'E2' => 'CALLE PRINCIPAL 123',
            'F2' => 1985,
            'G2' => 39,
            'H2' => '1985-03-15',
            'I2' => 'ADMINISTRACION',
            'J2' => 'RECURSOS HUMANOS',
            'K2' => 'GERENTE',
            'L2' => 25000,
            'M2' => 22000,
            'N2' => 25000,
            'O2' => 3000,
            'P2' => 25000,
            'Q2' => 'A',
            'R2' => 24500,
            'S2' => 833.33,
            'T2' => 950.50,
            'U2' => 'DIRECTOR GENERAL',
            'V2' => 15,
            'W2' => 3,
            'X2' => 2020,
            'Y2' => 'NOM001',
            'Z2' => 'PEGJ850315HDFRNN09',
            'AA2' => 'PEGJ850315ABC',
            'AB2' => '12345678901',
            'AC2' => '5551234567',
            'AD2' => '5551234567',
            'AE2' => 'REG001'
        ],
        [
            'A3' => '002',
            'B3' => 'MARIA GONZALEZ LOPEZ',
            'C3' => 'INGENIERIA',
            'D3' => 'F',
            'E3' => 'AVENIDA CENTRAL 456',
            'F3' => 1990,
            'G3' => 34,
            'H3' => '1990-07-22',
            'I3' => 'OPERACIONES',
            'J3' => 'PRODUCCION',
            'K3' => 'SUPERVISOR',
            'L3' => 18000,
            'M3' => 16000,
            'N3' => 18000,
            'O3' => 2000,
            'P3' => 18000,
            'Q3' => 'B',
            'R3' => 17500,
            'S3' => 600.00,
            'T3' => 690.00,
            'U3' => 'GERENTE PRODUCCION',
            'V3' => 22,
            'W3' => 7,
            'X3' => 2019,
            'Y3' => 'NOM002',
            'Z3' => 'GOLM900722MDFNPR03',
            'AA3' => 'GOLM900722XYZ',
            'AB3' => '23456789012',
            'AC3' => '5559876543',
            'AD3' => '5559876543',
            'AE3' => 'REG001'
        ]
    ];
    
    // Establecer datos de ejemplo
    foreach ($ejemplos as $ejemplo) {
        foreach ($ejemplo as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
    }
    
    // Formatear headers (negrita, fondo azul)
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
    ];
    $sheet->getStyle('A1:AE1')->applyFromArray($headerStyle);
    
    // Ajustar ancho de columnas
    foreach (range('A', 'AE') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Generar archivo y enviarlo para descarga
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="plantilla_empleados_DBRH.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al generar plantilla: ' . $e->getMessage()]);
}
?>