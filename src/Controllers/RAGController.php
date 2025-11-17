<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\RAGService;
use App\Services\EmbeddingService;

$config = require __DIR__ . '/../config.php';
$embeddingService = new EmbeddingService($config['ollamaApiUrl'], $config['jwtToken']);
$ragService = new RAGService($embeddingService);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'upload':
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $documentId = $ragService->uploadDocument($_FILES['file']);
                    echo json_encode([
                        'success' => true,
                        'document_id' => $documentId,
                        'message' => 'Document uploaded successfully'
                    ]);
                } catch (Exception $e) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            }
            break;
            
        case 'list':
            try {
                $documents = $ragService->getDocuments();
                echo json_encode(['success' => true, 'documents' => $documents]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'delete':
            $documentId = $_POST['document_id'] ?? null;
            if ($documentId) {
                try {
                    $ragService->deleteDocument($documentId);
                    echo json_encode(['success' => true, 'message' => 'Document deleted']);
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Document ID required']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
