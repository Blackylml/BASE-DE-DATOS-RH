<?php
/**
 * SimpleXLSXGen - Generador simple de archivos XLSX
 * Para DBRH project
 */

class SimpleXLSXGen {
    private $data;
    private $headers;
    
    public function __construct() {
        $this->data = [];
        $this->headers = [];
    }
    
    public function addSheet($data, $sheetName = 'Sheet1') {
        if (!empty($data)) {
            $this->headers = array_keys($data[0]);
            $this->data = $data;
        }
        return $this;
    }
    
    public function setHeaders($headers) {
        $this->headers = $headers;
        return $this;
    }
    
    public function addRow($row) {
        $this->data[] = $row;
        return $this;
    }
    
    public function download($filename = 'export.xlsx') {
        $this->generateXLSX($filename, true);
    }
    
    public function save($filename = 'export.xlsx') {
        $this->generateXLSX($filename, false);
    }
    
    private function generateXLSX($filename, $download = false) {
        // Crear archivo temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('No se pudo crear el archivo Excel');
        }
        
        // Crear estructura básica del XLSX
        $this->createXLSXStructure($zip);
        
        $zip->close();
        
        if ($download) {
            // Descargar archivo
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tempFile));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            readfile($tempFile);
            unlink($tempFile);
            exit;
        } else {
            // Guardar archivo
            copy($tempFile, $filename);
            unlink($tempFile);
        }
    }
    
    private function createXLSXStructure($zip) {
        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);
        
        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        
        // xl/_rels/workbook.xml.rels
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        
        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheets>
        <sheet name="Empleados" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>
    </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);
        
        // Crear shared strings y worksheet
        $this->createSharedStrings($zip);
        $this->createWorksheet($zip);
    }
    
    private function createSharedStrings($zip) {
        $strings = [];
        $stringIndex = [];
        
        // Agregar headers a strings compartidas
        foreach ($this->headers as $header) {
            if (!isset($stringIndex[$header])) {
                $stringIndex[$header] = count($strings);
                $strings[] = $header;
            }
        }
        
        // Agregar datos a strings compartidas
        foreach ($this->data as $row) {
            foreach ($row as $cell) {
                $cellValue = (string)$cell;
                if (!is_numeric($cellValue) && !isset($stringIndex[$cellValue])) {
                    $stringIndex[$cellValue] = count($strings);
                    $strings[] = $cellValue;
                }
            }
        }
        
        $sharedStrings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
        
        foreach ($strings as $string) {
            $sharedStrings .= '<si><t>' . htmlspecialchars($string, ENT_XML1) . '</t></si>';
        }
        
        $sharedStrings .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
        
        // Guardar índice para uso en worksheet
        $this->stringIndex = $stringIndex;
    }
    
    private function createWorksheet($zip) {
        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';
        
        $rowNum = 1;
        
        // Agregar headers
        if (!empty($this->headers)) {
            $worksheet .= '<row r="' . $rowNum . '">';
            $colNum = 1;
            foreach ($this->headers as $header) {
                $cellRef = $this->columnLetter($colNum) . $rowNum;
                $stringIndex = $this->stringIndex[$header];
                $worksheet .= '<c r="' . $cellRef . '" t="s"><v>' . $stringIndex . '</v></c>';
                $colNum++;
            }
            $worksheet .= '</row>';
            $rowNum++;
        }
        
        // Agregar datos
        foreach ($this->data as $row) {
            $worksheet .= '<row r="' . $rowNum . '">';
            $colNum = 1;
            foreach ($this->headers as $header) {
                $cellRef = $this->columnLetter($colNum) . $rowNum;
                $cellValue = isset($row[$header]) ? $row[$header] : '';
                
                if (is_numeric($cellValue)) {
                    $worksheet .= '<c r="' . $cellRef . '"><v>' . $cellValue . '</v></c>';
                } else {
                    $stringIndex = isset($this->stringIndex[$cellValue]) ? $this->stringIndex[$cellValue] : 0;
                    $worksheet .= '<c r="' . $cellRef . '" t="s"><v>' . $stringIndex . '</v></c>';
                }
                $colNum++;
            }
            $worksheet .= '</row>';
            $rowNum++;
        }
        
        $worksheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);
    }
    
    private function columnLetter($num) {
        $letter = '';
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = intval($num / 26);
        }
        return $letter;
    }
}
?>