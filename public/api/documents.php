<?php

require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Services/EnhancedDocumentService.php';
require __DIR__ . '/../src/Http/RequestHelper.php';
require __DIR__ . '/../src/Http/Response.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$documentService = new \App\Services\EnhancedDocumentService();

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'upload':
            if (!isset($_FILES['document'])) {
                Response::json(['success' => false, 'error' => 'Document file is required'], 400);
                exit;
            }
            
            $file = $_FILES['document'];
            // Security Fix: Prevent path traversal in file uploads
            $originalName = basename($file['name']);
            $tempPath = $file['tmp_name'];
            
            // Validate file
            $validation = $documentService->validateFile($tempPath, $originalName);
            
            if (!$validation['valid']) {
                Response::json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'validation' => $validation
                ], 400);
                exit;
            }
            
            // Move file to permanent location
            $uploadDir = __DIR__ . '/../../data/uploads';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = uniqid() . '_' . $originalName;
            $filePath = $uploadDir . '/' . $fileName;
            
            if (!move_uploaded_file($tempPath, $filePath)) {
                Response::json(['success' => false, 'error' => 'Failed to save file'], 500);
                exit;
            }
            
            try {
                $result = $documentService->processDocument($filePath, $originalName);
                Response::json(['success' => true, 'result' => $result]);
            } catch (Exception $e) {
                // Clean up on failure
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        case 'validate':
            if (!isset($_FILES['document'])) {
                Response::json(['success' => false, 'error' => 'Document file is required'], 400);
                exit;
            }
            
            $file = $_FILES['document'];
            // Security Fix: Prevent path traversal
            $originalName = basename($file['name']);
            $tempPath = $file['tmp_name'];
            
            $validation = $documentService->validateFile($tempPath, $originalName);
            Response::json(['success' => true, 'validation' => $validation]);
            break;
            
        case 'extract_text':
            if (!isset($_FILES['document'])) {
                Response::json(['success' => false, 'error' => 'Document file is required'], 400);
                exit;
            }
            
            $file = $_FILES['document'];
            // Security Fix: Prevent path traversal
            $originalName = basename($file['name']);
            $tempPath = $file['tmp_name'];
            
            $validation = $documentService->validateFile($tempPath, $originalName);
            
            if (!$validation['valid']) {
                Response::json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'validation' => $validation
                ], 400);
                exit;
            }
            
            try {
                $fileInfo = $validation['file_info'];
                $textContent = $documentService->extractText($tempPath, $fileInfo['extension']);
                
                Response::json([
                    'success' => true,
                    'text_content' => $textContent,
                    'file_info' => $fileInfo,
                    'content_length' => strlen($textContent)
                ]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('GET')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'list':
            $stmt = $db->query("
                SELECT * FROM documents 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $documents = $stmt->fetchAll();
            Response::json(['success' => true, 'documents' => $documents]);
            break;
            
        case 'get':
            $documentId = RequestHelper::getInput('document_id', '');
            
            if (empty($documentId)) {
                Response::json(['success' => false, 'error' => 'Document ID is required'], 400);
                exit;
            }
            
            $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();
            
            if ($document) {
                Response::json(['success' => true, 'document' => $document]);
            } else {
                Response::json(['success' => false, 'error' => 'Document not found'], 404);
            }
            break;
            
        case 'formats':
            $formats = $documentService->getSupportedFormats();
            Response::json(['success' => true, 'formats' => $formats]);
            break;
            
        case 'status':
            $status = [
                'supported_formats' => $documentService->getSupportedFormats(),
                'extractors_available' => array_keys($documentService->getTextExtractors()),
                'upload_dir' => __DIR__ . '/../../data/uploads',
                'max_file_size' => '50MB'
            ];
            
            Response::json(['success' => true, 'status' => $status]);
            break;
            
        default:
            Response::json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} elseif (RequestHelper::isMethod('DELETE')) {
    $documentId = RequestHelper::getInput('document_id', '');
    
    if (empty($documentId)) {
        Response::json(['success' => false, 'error' => 'Document ID is required'], 400);
        exit;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $result = $stmt->execute([$documentId]);
        
        if ($result && $stmt->rowCount() > 0) {
            Response::json(['success' => true, 'message' => 'Document deleted successfully']);
        } else {
            Response::json(['success' => false, 'error' => 'Document not found'], 404);
        }
    } catch (Exception $e) {
        Response::json(['success' => false, 'error' => $e->getMessage()], 500);
    }
} else {
    Response::json(['success' => false, 'error' => 'Method not allowed'], 405);
}