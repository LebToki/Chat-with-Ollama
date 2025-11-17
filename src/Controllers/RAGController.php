<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\RAGService;
use App\Services\EmbeddingService;
use App\Http\RequestHelper;

$config = require __DIR__ . '/../config.php';
$embeddingService = new EmbeddingService($config['ollamaApiUrl'], $config['jwtToken']);
$ragService = new RAGService($embeddingService);

header('Content-Type: application/json');

if (RequestHelper::isMethod('POST')) {
    // For multipart/form-data, check $_POST directly first, then parsed input
    $action = $_POST['action'] ?? RequestHelper::getInput('action', '');
    
    // Log for debugging
    error_log("RAGController: Action = " . $action . ", Content-Type = " . ($_SERVER['CONTENT_TYPE'] ?? 'none'));
    error_log("RAGController: POST keys = " . implode(', ', array_keys($_POST ?? [])));
    error_log("RAGController: FILES keys = " . implode(', ', array_keys($_FILES ?? [])));
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action parameter required']);
        exit;
    }
    
    switch ($action) {
        case 'upload':
            if (RequestHelper::hasFile('file')) {
                try {
                    $file = RequestHelper::getFile('file');
                    $documentId = $ragService->uploadDocument($file);
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
            
        case 'list_for_mentions':
            // Get only processed documents for @ mentions
            try {
                $documents = $ragService->getDocuments();
                // Filter to only processed documents and return minimal data
                $mentionDocs = array_map(function($doc) {
                    if ($doc['status'] === 'processed') {
                        return [
                            'id' => $doc['id'],
                            'name' => $doc['original_filename'],
                            'type' => $doc['file_type']
                        ];
                    }
                    return null;
                }, $documents);
                $mentionDocs = array_filter($mentionDocs); // Remove nulls
                echo json_encode(['success' => true, 'documents' => array_values($mentionDocs)]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'delete':
            $documentId = RequestHelper::getInput('document_id');
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
            
        case 'preview':
            $documentId = RequestHelper::getInput('document_id');
            $limit = (int)RequestHelper::getInput('limit', 50);
            if ($documentId) {
                try {
                    $preview = $ragService->getDocumentPreview($documentId, $limit);
                    if ($preview) {
                        echo json_encode(['success' => true, 'preview' => $preview]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Document not found']);
                    }
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
