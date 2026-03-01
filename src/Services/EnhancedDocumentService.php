<?php

namespace App\Services;

use Exception;

class EnhancedDocumentService extends DocumentService
{
    private $supportedFormats = [
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'doc' => ['application/msword'],
        'txt' => ['text/plain', 'text/csv'],
        'md' => ['text/markdown'],
        'rtf' => ['application/rtf'],
        'epub' => ['application/epub+zip'],
        'html' => ['text/html', 'application/xhtml+xml'],
        'xml' => ['application/xml', 'text/xml'],
        'json' => ['application/json'],
        'xml' => ['application/xml'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xls' => ['application/vnd.ms-excel']
    ];
    
    private $textExtractors = [];
    
    public function __construct()
    {
        parent::__construct();
        $this->initTextExtractors();
    }
    
    private function initTextExtractors()
    {
        // PDF extractor using pdftotext (if available)
        if ($this->isCommandAvailable('pdftotext')) {
            $this->textExtractors['pdf'] = [$this, 'extractPDFWithCLI'];
        } elseif (class_exists('Smalot\\PdfParser\\Parser')) {
            $this->textExtractors['pdf'] = [$this, 'extractPDFWithLibrary'];
        }
        
        // DOCX extractor using PHPWord
        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            $this->textExtractors['docx'] = [$this, 'extractDOCX'];
            $this->textExtractors['doc'] = [$this, 'extractDOC'];
        }
        
        // EPUB extractor
        if (class_exists('Easybook\\Libraries\\Epub\\Epub')) {
            $this->textExtractors['epub'] = [$this, 'extractEPUB'];
        }
        
        // HTML extractor
        $this->textExtractors['html'] = [$this, 'extractHTML'];
        $this->textExtractors['xml'] = [$this, 'extractXML'];
        
        // Default text extractors
        $this->textExtractors['txt'] = [$this, 'extractPlainText'];
        $this->textExtractors['md'] = [$this, 'extractPlainText'];
        $this->textExtractors['rtf'] = [$this, 'extractRTF'];
    }
    
    private function isCommandAvailable($command)
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        return shell_exec("$which $command") !== null;
    }
    
    /**
     * Enhanced document processing with format detection
     */
    public function processDocument(string $filePath, string $originalName): array
    {
        // Detect file format
        $fileInfo = $this->detectFileFormat($filePath, $originalName);
        
        if (!$fileInfo['supported']) {
            throw new Exception("Unsupported file format: {$fileInfo['extension']}");
        }
        
        // Extract text content
        $textContent = $this->extractText($filePath, $fileInfo['extension']);
        
        if (empty($textContent)) {
            throw new Exception("Failed to extract text from document");
        }
        
        // Generate enhanced metadata
        $metadata = $this->generateEnhancedMetadata($filePath, $originalName, $fileInfo);
        
        // Process with RAG
        $ragResult = $this->ragService->processDocument($textContent, $metadata);
        
        // Save to database with enhanced information
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                original_filename, file_path, file_type, file_size, 
                content_length, extracted_text, metadata, 
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $originalName,
            $filePath,
            $fileInfo['extension'],
            $metadata['file_size'],
            strlen($textContent),
            $textContent,
            json_encode($metadata)
        ]);
        
        $documentId = $this->db->lastInsertId();
        
        return [
            'document_id' => $documentId,
            'file_info' => $fileInfo,
            'metadata' => $metadata,
            'rag_result' => $ragResult
        ];
    }
    
    /**
     * Detect file format and validate
     */
    private function detectFileFormat(string $filePath, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($filePath);
        
        $supported = false;
        $detectedType = '';
        
        foreach ($this->supportedFormats as $format => $mimeTypes) {
            if ($extension === $format || in_array($mimeType, $mimeTypes)) {
                $supported = true;
                $detectedType = $format;
                break;
            }
        }
        
        return [
            'extension' => $detectedType ?: $extension,
            'mime_type' => $mimeType,
            'supported' => $supported,
            'original_extension' => $extension
        ];
    }
    
    /**
     * Extract text based on file format
     */
    private function extractText(string $filePath, string $extension): string
    {
        if (isset($this->textExtractors[$extension])) {
            return call_user_func($this->textExtractors[$extension], $filePath);
        }
        
        // Fallback to plain text extraction
        return $this->extractPlainText($filePath);
    }
    
    /**
     * Extract PDF text using CLI tool
     */
    private function extractPDFWithCLI(string $filePath): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_text_');
        
        $command = sprintf(
            'pdftotext %s %s',
            escapeshellarg($filePath),
            escapeshellarg($tempFile)
        );
        
        shell_exec($command);
        
        if (file_exists($tempFile)) {
            $text = file_get_contents($tempFile);
            unlink($tempFile);
            return $text;
        }
        
        return '';
    }
    
    /**
     * Extract PDF text using PHP library
     */
    private function extractPDFWithLibrary(string $filePath): string
    {
        try {
            $parser = new \Smalot\\PdfParser\\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Extract DOCX/DOC text using PHPWord
     */
    private function extractDOCX(string $filePath): string
    {
        try {
            $phpWord = \PhpOffice\\PhpWord\\IOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            return $text;
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Extract DOC text
     */
    private function extractDOC(string $filePath): string
    {
        return $this->extractDOCX($filePath); // Use same method as DOCX
    }
    
    /**
     * Extract EPUB text
     */
    private function extractEPUB(string $filePath): string
    {
        try {
            $epub = new \Easybook\\Libraries\\Epub\\Epub();
            $epub->open($filePath);
            
            $text = '';
            $chapters = $epub->getChapters();
            
            foreach ($chapters as $chapter) {
                $text .= $chapter->getContent() . "\n\n";
            }
            
            return $text;
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Extract HTML text
     */
    private function extractHTML(string $filePath): string
    {
        $html = file_get_contents($filePath);
        return strip_tags($html);
    }
    
    /**
     * Extract XML text
     */
    private function extractXML(string $filePath): string
    {
        $xml = simplexml_load_file($filePath);
        return strip_tags($xml->asXML());
    }
    
    /**
     * Extract RTF text
     */
    private function extractRTF(string $filePath): string
    {
        $rtf = file_get_contents($filePath);
        
        // Basic RTF to text conversion
        $text = preg_replace('/\\\\[a-z]+\d*/', '', $rtf);
        $text = preg_replace('/{[^}]*}/', '', $text);
        $text = str_replace(['{', '}', '\\\\'], '', $text);
        
        return trim($text);
    }
    
    /**
     * Extract plain text
     */
    private function extractPlainText(string $filePath): string
    {
        return file_get_contents($filePath);
    }
    
    /**
     * Generate enhanced metadata
     */
    private function generateEnhancedMetadata(string $filePath, string $originalName, array $fileInfo): array
    {
        $stat = stat($filePath);
        
        $metadata = [
            'original_filename' => $originalName,
            'file_size' => $stat['size'],
            'file_type' => $fileInfo['extension'],
            'mime_type' => $fileInfo['mime_type'],
            'created_at' => date('Y-m-d H:i:s', $stat['ctime']),
            'modified_at' => date('Y-m-d H:i:s', $stat['mtime']),
            'extracted_with' => $fileInfo['extension'],
            'processing_date' => date('Y-m-d H:i:s'),
            'version' => '2.0'
        ];
        
        // Add format-specific metadata
        switch ($fileInfo['extension']) {
            case 'pdf':
                $metadata = array_merge($metadata, $this->getPDFMetadata($filePath));
                break;
            case 'docx':
            case 'doc':
                $metadata = array_merge($metadata, $this->getDocMetadata($filePath));
                break;
            case 'epub':
                $metadata = array_merge($metadata, $this->getEPUBMetadata($filePath));
                break;
        }
        
        return $metadata;
    }
    
    /**
     * Get PDF-specific metadata
     */
    private function getPDFMetadata(string $filePath): array
    {
        try {
            if ($this->isCommandAvailable('pdfinfo')) {
                $output = shell_exec("pdfinfo " . escapeshellarg($filePath));
                $lines = explode("\n", $output);
                
                $metadata = [];
                foreach ($lines as $line) {
                    if (preg_match('/^(\w+):\s+(.+)$/', $line, $matches)) {
                        $metadata[strtolower($matches[1])] = trim($matches[2]);
                    }
                }
                
                return $metadata;
            }
        } catch (Exception $e) {
            // Fallback
        }
        
        return [];
    }
    
    /**
     * Get DOC-specific metadata
     */
    private function getDocMetadata(string $filePath): array
    {
        try {
            $phpWord = \PhpOffice\\PhpWord\\IOFactory::load($filePath);
            $docProps = $phpWord->getDocInfo();
            
            return [
                'title' => $docProps->getTitle(),
                'author' => $docProps->getCreator(),
                'subject' => $docProps->getSubject(),
                'keywords' => $docProps->getKeywords(),
                'created' => $docProps->getCreated(),
                'modified' => $docProps->getModified()
            ];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get EPUB-specific metadata
     */
    private function getEPUBMetadata(string $filePath): array
    {
        try {
            $epub = new \Easybook\\Libraries\\Epub\\Epub();
            $epub->open($filePath);
            
            return [
                'title' => $epub->getTitle(),
                'author' => $epub->getAuthor(),
                'publisher' => $epub->getPublisher(),
                'description' => $epub->getDescription(),
                'language' => $epub->getLanguage(),
                'chapters' => count($epub->getChapters())
            ];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get supported file formats
     */
    public function getSupportedFormats(): array
    {
        return array_keys($this->supportedFormats);
    }
    
    /**
     * Validate file before processing
     */
    public function validateFile(string $filePath, string $originalName): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'file_info' => null
        ];
        
        // Check file exists
        if (!file_exists($filePath)) {
            $result['errors'][] = 'File does not exist';
            return $result;
        }
        
        // Check file size
        $fileSize = filesize($filePath);
        $maxSize = 50 * 1024 * 1024; // 50MB limit
        
        if ($fileSize > $maxSize) {
            $result['errors'][] = 'File size exceeds maximum limit (50MB)';
            return $result;
        }
        
        // Detect format
        $fileInfo = $this->detectFileFormat($filePath, $originalName);
        $result['file_info'] = $fileInfo;
        
        if (!$fileInfo['supported']) {
            $result['errors'][] = "Unsupported file format: {$fileInfo['extension']}";
            return $result;
        }
        
        // Check if extractor is available
        if (!isset($this->textExtractors[$fileInfo['extension']])) {
            $result['warnings'][] = "No specialized extractor available for {$fileInfo['extension']}, using plain text extraction";
        }
        
        $result['valid'] = true;
        return $result;
    }
}