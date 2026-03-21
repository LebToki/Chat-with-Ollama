<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class DocumentService
{
    private $uploadDir;
    private $allowedTypes = ['pdf', 'txt', 'docx', 'xlsx', 'csv', 'md', 'ppt', 'pptx'];

    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function processDocument($file)
    {
        $originalFilename = $file['name'];
        $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $this->allowedTypes)) {
            throw new Exception("File type not supported: {$fileExtension}");
        }

        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $this->uploadDir . $filename;

        // Handle both uploaded files and test files
        if (is_uploaded_file($file['tmp_name'])) {
            // Real HTTP upload
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Failed to upload file");
            }
        } else {
            // Test file or direct file copy
            if (!copy($file['tmp_name'], $filePath)) {
                throw new Exception("Failed to copy file");
            }
        }

        $content = $this->extractText($filePath, $fileExtension);
        
        return [
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_type' => $fileExtension,
            'file_size' => filesize($filePath),
            'file_path' => $filePath,
            'content' => $content
        ];
    }

    private function extractText($filePath, $extension)
    {
        switch ($extension) {
            case 'pdf':
                return $this->extractFromPdf($filePath);
            
            case 'txt':
            case 'md':
                return file_get_contents($filePath);
            
            case 'docx':
                return $this->extractFromDocx($filePath);
            
            case 'ppt':
            case 'pptx':
                return $this->extractFromPowerPoint($filePath);
            
            case 'xlsx':
            case 'csv':
                return $this->extractFromSpreadsheet($filePath);
            
            default:
                throw new Exception("Unsupported file type: {$extension}");
        }
    }

    private function extractFromPdf($filePath)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (Exception $e) {
            throw new Exception("Failed to parse PDF: " . $e->getMessage());
        }
    }

    private function extractFromDocx($filePath)
    {
        try {
            if (!class_exists('ZipArchive')) {
                throw new Exception("ZipArchive class not available. Install php-zip extension.");
            }
            
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === TRUE) {
                $content = $zip->getFromName('word/document.xml');
                $zip->close();
                
                if ($content) {
                    // Remove XML tags and decode entities
                    $content = preg_replace('/<[^>]+>/', ' ', $content);
                    $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    // Clean up multiple spaces
                    $content = preg_replace('/\s+/', ' ', $content);
                    return trim($content);
                }
            }
            throw new Exception("Failed to extract DOCX content");
        } catch (Exception $e) {
            throw new Exception("Failed to parse DOCX: " . $e->getMessage());
        }
    }

    private function extractFromPowerPoint($filePath)
    {
        try {
            // Check if it's PPTX (ZIP-based) or old PPT (binary)
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if ($fileExtension === 'ppt') {
                // Old PPT format (binary) - not easily extractable without special libraries
                // For now, we'll try to detect if it's actually a PPTX file with wrong extension
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    // It's actually a ZIP file (PPTX with wrong extension)
                    $zip->close();
                    // Fall through to PPTX extraction
                } else {
                    throw new Exception("Old PPT format (.ppt) is not supported. Please convert to PPTX format or use a newer PowerPoint file.");
                }
            }
            
            // PPTX files are ZIP archives
            if (!class_exists('ZipArchive')) {
                throw new Exception("ZipArchive class not available. Install php-zip extension.");
            }
            
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === TRUE) {
                $text = '';
                
                // PPTX files contain slides in ppt/slides/slide*.xml
                // Extract text from all slides
                $slideIndex = 1;
                while (true) {
                    $slidePath = "ppt/slides/slide{$slideIndex}.xml";
                    $content = $zip->getFromName($slidePath);
                    
                    if ($content === false) {
                        // No more slides
                        break;
                    }
                    
                    // Remove XML tags and decode entities
                    $slideText = preg_replace('/<[^>]+>/', ' ', $content);
                    $slideText = html_entity_decode($slideText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    // Clean up multiple spaces and newlines
                    $slideText = preg_replace('/\s+/', ' ', $slideText);
                    $text .= trim($slideText) . "\n\n";
                    
                    $slideIndex++;
                }
                
                $zip->close();
                
                if (empty(trim($text))) {
                    throw new Exception("No text content found in PowerPoint file");
                }
                
                return trim($text);
            }
            
            throw new Exception("Failed to extract PowerPoint content. File may be corrupted or in an unsupported format.");
        } catch (Exception $e) {
            throw new Exception("Failed to parse PowerPoint: " . $e->getMessage());
        }
    }

    private function extractFromSpreadsheet($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $text = '';
            
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cellValues = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $cellValues[] = $cell->getFormattedValue();
                    }
                    $text .= implode(' ', $cellValues) . "\n";
                }
            }
            
            return trim($text);
        } catch (Exception $e) {
            throw new Exception("Failed to parse spreadsheet: " . $e->getMessage());
        }
    }

    public function chunkText($text, $chunkSize = 1000, $overlap = 200)
    {
        $chunks = [];
        $words = preg_split('/\s+/', $text);
        $currentChunk = [];
        $currentLength = 0;

        foreach ($words as $word) {
            $wordLength = strlen($word) + 1; // +1 for space
            
            if ($currentLength + $wordLength > $chunkSize && !empty($currentChunk)) {
                $chunks[] = implode(' ', $currentChunk);
                
                // Overlap: keep last N words
                $overlapWords = array_slice($currentChunk, -$overlap);
                $currentChunk = $overlapWords;

                // ⚡ Bolt: Fast recalculation using a simple loop instead of imploding and strlen
                // which creates unnecessary large temporary strings in memory on every overlap.
                $currentLength = 0;
                foreach ($overlapWords as $w) {
                    $currentLength += strlen($w) + 1; // +1 for space
                }
                if ($currentLength > 0) {
                    $currentLength -= 1; // Match `implode` behavior which doesn't add a trailing space
                }
            }
            
            $currentChunk[] = $word;
            $currentLength += $wordLength;
        }
        
        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }
        
        return $chunks;
    }
}
