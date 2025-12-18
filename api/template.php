<?php
header('Content-Type: application/json');

require_once '../lib/SimpleXLSXGen.php';

try {
    // Definir las columnas exactas que necesita el sistema
    $headers = [
        'FICHA',
        'NOMBRE', 
        'ESCOLARIDAD',
        'SEXO',
        'DIRECCION',
        'AÑO NACIMIENTO',
        'EDAD',
        'CUMPLEAÑOS',
        'AREA',
        'DEPARTAMENTO', 
        'PUESTO',
        'MENSUAL CON BD',
        'MENSUAL SIN BD',
        'SALARIO REAL EXCEL',
        'BD',
        'SUELDO REAL MAS BD',
        'CATEGORIAS',
        'SUELDO MICROSIP',
        'S.D.',
        'SDI',
        'JEFE DIRECTO',
        'DIA',
        'MES', 
        'AÑO',
        'NOMINA',
        'CURP',
        'RFC',
        'IMSS',
        'NUMERO',
        'CONTACTO',
        'REGISTRO PATRONAL'
    ];
    
    // Crear datos de ejemplo
    $ejemplos = [
        [
            'FICHA' => '001',
            'NOMBRE' => 'JUAN PEREZ GARCIA',
            'ESCOLARIDAD' => 'LICENCIATURA',
            'SEXO' => 'M',
            'DIRECCION' => 'CALLE PRINCIPAL 123',
            'AÑO NACIMIENTO' => '1985',
            'EDAD' => '39',
            'CUMPLEAÑOS' => '1985-03-15',
            'AREA' => 'ADMINISTRACION',
            'DEPARTAMENTO' => 'RECURSOS HUMANOS',
            'PUESTO' => 'GERENTE',
            'MENSUAL CON BD' => '25000',
            'MENSUAL SIN BD' => '22000',
            'SALARIO REAL EXCEL' => '25000',
            'BD' => '3000',
            'SUELDO REAL MAS BD' => '25000',
            'CATEGORIAS' => 'A',
            'SUELDO MICROSIP' => '24500',
            'S.D.' => '833.33',
            'SDI' => '950.50',
            'JEFE DIRECTO' => 'DIRECTOR GENERAL',
            'DIA' => '15',
            'MES' => '3',
            'AÑO' => '2020',
            'NOMINA' => 'NOM001',
            'CURP' => 'PEGJ850315HDFRNN09',
            'RFC' => 'PEGJ850315ABC',
            'IMSS' => '12345678901',
            'NUMERO' => '5551234567',
            'CONTACTO' => '5551234567',
            'REGISTRO PATRONAL' => 'REG001'
        ],
        [
            'FICHA' => '002',
            'NOMBRE' => 'MARIA GONZALEZ LOPEZ',
            'ESCOLARIDAD' => 'INGENIERIA',
            'SEXO' => 'F',
            'DIRECCION' => 'AVENIDA CENTRAL 456',
            'AÑO NACIMIENTO' => '1990',
            'EDAD' => '34',
            'CUMPLEAÑOS' => '1990-07-22',
            'AREA' => 'OPERACIONES',
            'DEPARTAMENTO' => 'PRODUCCION',
            'PUESTO' => 'SUPERVISOR',
            'MENSUAL CON BD' => '18000',
            'MENSUAL SIN BD' => '16000',
            'SALARIO REAL EXCEL' => '18000',
            'BD' => '2000',
            'SUELDO REAL MAS BD' => '18000',
            'CATEGORIAS' => 'B',
            'SUELDO MICROSIP' => '17500',
            'S.D.' => '600.00',
            'SDI' => '690.00',
            'JEFE DIRECTO' => 'GERENTE PRODUCCION',
            'DIA' => '22',
            'MES' => '7',
            'AÑO' => '2019',
            'NOMINA' => 'NOM002',
            'CURP' => 'GOLM900722MDFNPR03',
            'RFC' => 'GOLM900722XYZ',
            'IMSS' => '23456789012',
            'NUMERO' => '5559876543',
            'CONTACTO' => '5559876543',
            'REGISTRO PATRONAL' => 'REG001'
        ],
        [
            'FICHA' => '',
            'NOMBRE' => '',
            'ESCOLARIDAD' => '',
            'SEXO' => '',
            'DIRECCION' => '',
            'AÑO NACIMIENTO' => '',
            'EDAD' => '',
            'CUMPLEAÑOS' => '',
            'AREA' => '',
            'DEPARTAMENTO' => '',
            'PUESTO' => '',
            'MENSUAL CON BD' => '',
            'MENSUAL SIN BD' => '',
            'SALARIO REAL EXCEL' => '',
            'BD' => '',
            'SUELDO REAL MAS BD' => '',
            'CATEGORIAS' => '',
            'SUELDO MICROSIP' => '',
            'S.D.' => '',
            'SDI' => '',
            'JEFE DIRECTO' => '',
            'DIA' => '',
            'MES' => '',
            'AÑO' => '',
            'NOMINA' => '',
            'CURP' => '',
            'RFC' => '',
            'IMSS' => '',
            'NUMERO' => '',
            'CONTACTO' => '',
            'REGISTRO PATRONAL' => ''
        ]
    ];
    
    // Crear el archivo Excel
    $xlsx = new SimpleXLSXGen();
    $xlsx->setHeaders($headers);
    
    foreach ($ejemplos as $ejemplo) {
        $xlsx->addRow($ejemplo);
    }
    
    // Generar y descargar
    $xlsx->download('plantilla_empleados_DBRH.xlsx');
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al generar plantilla: ' . $e->getMessage()]);
}
?>