<?php
/**
 * SimpleXLSX - Lightweight XLSX reader for PHP
 * Based on SimpleXLSX by Sergey Shuchkin
 * Simplified version for DBRH project
 */

class SimpleXLSX {
    const SCHEMA_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    const SCHEMA_OFFICEDOCUMENT = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
    const SCHEMA_WORKSHEET = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';
    
    private $workbook;
    private $sheets;
    private $sharedStrings;
    private $error = false;
    private $errorMessage = '';
    
    public function __construct($filename = null) {
        if ($filename) {
            $this->parse($filename);
        }
    }
    
    public static function parse($filename) {
        $xlsx = new self();
        return $xlsx->_parse($filename) ? $xlsx : false;
    }
    
    private function _parse($filename) {
        if (!is_readable($filename)) {
            $this->error('File not found or not readable');
            return false;
        }
        
        $zip = new ZipArchive();
        if ($zip->open($filename) !== TRUE) {
            $this->error('Unable to open zip archive');
            return false;
        }
        
        // Parse relationships
        $rels = $this->parseXML($zip->getFromName('_rels/.rels'));
        if (!$rels) {
            $zip->close();
            return false;
        }
        
        $workbookPath = '';
        foreach ($rels->Relationship as $rel) {
            if ((string)$rel['Type'] === self::SCHEMA_OFFICEDOCUMENT) {
                $workbookPath = (string)$rel['Target'];
                break;
            }
        }
        
        if (!$workbookPath) {
            $this->error('Workbook not found');
            $zip->close();
            return false;
        }
        
        // Parse workbook
        $workbook = $this->parseXML($zip->getFromName($workbookPath));
        if (!$workbook) {
            $zip->close();
            return false;
        }
        
        // Parse shared strings
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $sharedStrings = $this->parseXML($sharedStringsXML);
            $this->sharedStrings = [];
            if ($sharedStrings && $sharedStrings->si) {
                foreach ($sharedStrings->si as $si) {
                    $this->sharedStrings[] = isset($si->t) ? (string)$si->t : (isset($si->r) ? $this->parseRichText($si->r) : '');
                }
            }
        }
        
        // Parse worksheets
        $worksheetRels = $this->parseXML($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $this->sheets = [];
        
        if ($worksheetRels) {
            foreach ($worksheetRels->Relationship as $rel) {
                if ((string)$rel['Type'] === self::SCHEMA_WORKSHEET) {
                    $worksheetPath = 'xl/' . (string)$rel['Target'];
                    $worksheetXML = $zip->getFromName($worksheetPath);
                    if ($worksheetXML) {
                        $worksheet = $this->parseXML($worksheetXML);
                        if ($worksheet) {
                            $this->sheets[] = $this->parseWorksheet($worksheet);
                        }
                    }
                }
            }
        }
        
        $zip->close();
        return true;
    }
    
    private function parseWorksheet($worksheet) {
        $rows = [];
        
        if (!isset($worksheet->sheetData) || !isset($worksheet->sheetData->row)) {
            return $rows;
        }
        
        foreach ($worksheet->sheetData->row as $row) {
            $rowData = [];
            $colIndex = 0;
            
            if (isset($row->c)) {
                foreach ($row->c as $cell) {
                    $cellRef = (string)$cell['r'];
                    $cellType = isset($cell['t']) ? (string)$cell['t'] : '';
                    
                    // Calculate column index from cell reference (A1, B1, etc.)
                    $colIndex = $this->columnIndexFromReference($cellRef);
                    
                    // Fill empty columns
                    while (count($rowData) < $colIndex) {
                        $rowData[] = '';
                    }
                    
                    $value = '';
                    if (isset($cell->v)) {
                        $value = (string)$cell->v;
                        
                        if ($cellType === 's') {
                            // Shared string
                            $value = isset($this->sharedStrings[$value]) ? $this->sharedStrings[$value] : '';
                        }
                    } elseif (isset($cell->is) && isset($cell->is->t)) {
                        // Inline string
                        $value = (string)$cell->is->t;
                    }
                    
                    $rowData[] = $value;
                }
            }
            
            $rows[] = $rowData;
        }
        
        return $rows;
    }
    
    private function columnIndexFromReference($cellRef) {
        preg_match('/([A-Z]+)/', $cellRef, $matches);
        if (!isset($matches[1])) return 0;
        
        $column = $matches[1];
        $index = 0;
        
        for ($i = 0; $i < strlen($column); $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }
        
        return $index - 1; // Convert to 0-based index
    }
    
    private function parseRichText($richText) {
        $text = '';
        foreach ($richText as $r) {
            if (isset($r->t)) {
                $text .= (string)$r->t;
            }
        }
        return $text;
    }
    
    private function parseXML($xmlString) {
        if (!$xmlString) return false;
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        
        if ($xml === false) {
            $this->error('XML parsing failed');
            return false;
        }
        
        return $xml;
    }
    
    private function error($message) {
        $this->error = true;
        $this->errorMessage = $message;
    }
    
    public function hasError() {
        return $this->error;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    public function rows($sheetIndex = 0) {
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }
    
    public function sheetNames() {
        // For simplicity, return sheet numbers
        $names = [];
        for ($i = 0; $i < count($this->sheets); $i++) {
            $names[] = 'Sheet' . ($i + 1);
        }
        return $names;
    }
}
?>