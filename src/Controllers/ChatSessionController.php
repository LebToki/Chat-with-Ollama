<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Database\Database;
use App\Http\RequestHelper;

header('Content-Type: application/json');
$db = Database::getInstance()->getConnection();

if (RequestHelper::isMethod('POST')) {
    $action = RequestHelper::getInput('action', '');
    
    switch ($action) {
        case 'create':
            try {
                $title = RequestHelper::getInput('title', 'New Chat');
                $documentId = RequestHelper::getInput('document_id');
                
                // Check if document_id column exists
                $stmt = $db->query("PRAGMA table_info(chat_sessions)");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $hasDocumentId = false;
                foreach ($columns as $col) {
                    if ($col['name'] === 'document_id') {
                        $hasDocumentId = true;
                        break;
                    }
                }
                
                // Add document_id column if it doesn't exist
                if (!$hasDocumentId) {
                    try {
                        $db->exec("ALTER TABLE chat_sessions ADD COLUMN document_id INTEGER");
                        $db->exec("CREATE INDEX IF NOT EXISTS idx_chat_sessions_document_id ON chat_sessions(document_id)");
                    } catch (Exception $e) {
                        error_log("Failed to add document_id column: " . $e->getMessage());
                    }
                }
                
                if ($documentId) {
                    $stmt = $db->prepare("INSERT INTO chat_sessions (title, document_id) VALUES (:title, :document_id)");
                    $stmt->execute([':title' => $title, ':document_id' => $documentId]);
                } else {
                    $stmt = $db->prepare("INSERT INTO chat_sessions (title) VALUES (:title)");
                    $stmt->execute([':title' => $title]);
                }
                
                $sessionId = $db->lastInsertId();
                if (!$sessionId) {
                    throw new Exception('Failed to create session: No session ID returned');
                }
                
                echo json_encode(['success' => true, 'session_id' => $sessionId]);
            } catch (Exception $e) {
                error_log("ChatSessionController create error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'list':
            // Get sessions, prioritizing those with messages, including document info
            $stmt = $db->query("
                SELECT cs.*, 
                       COUNT(cm.id) as message_count,
                       MAX(cm.created_at) as last_message_at,
                       d.original_filename as document_name,
                       d.file_type as document_type
                FROM chat_sessions cs
                LEFT JOIN chat_messages cm ON cs.id = cm.session_id
                LEFT JOIN documents d ON cs.document_id = d.id
                GROUP BY cs.id
                ORDER BY 
                    CASE WHEN COUNT(cm.id) > 0 THEN 0 ELSE 1 END,
                    cs.updated_at DESC
                LIMIT 50
            ");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'sessions' => $sessions]);
            break;
            
        case 'get':
            $sessionId = RequestHelper::getInput('session_id');
            if ($sessionId) {
                $stmt = $db->prepare("
                    SELECT * FROM chat_messages 
                    WHERE session_id = :session_id 
                    ORDER BY created_at ASC
                ");
                $stmt->execute([':session_id' => $sessionId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'messages' => $messages]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Session ID required']);
            }
            break;
            
        case 'delete':
            $sessionId = RequestHelper::getInput('session_id') ?? RequestHelper::getInput('document_id');
            if ($sessionId) {
                // First delete all messages in the session
                $stmt = $db->prepare("DELETE FROM chat_messages WHERE session_id = :id");
                $stmt->execute([':id' => $sessionId]);
                // Then delete the session
                $stmt = $db->prepare("DELETE FROM chat_sessions WHERE id = :id");
                $stmt->execute([':id' => $sessionId]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Session ID required']);
            }
            break;
            
        case 'cleanup_empty':
            // Delete all sessions with 0 messages
            $stmt = $db->query("
                DELETE cs FROM chat_sessions cs
                LEFT JOIN chat_messages cm ON cs.id = cm.session_id
                WHERE cm.id IS NULL
            ");
            $deletedCount = $stmt->rowCount();
            echo json_encode(['success' => true, 'deleted_count' => $deletedCount]);
            break;
            
        case 'rename':
            $sessionId = RequestHelper::getInput('session_id');
            $newTitle = RequestHelper::getInput('title', '');
            
            if (!$sessionId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Session ID required']);
                break;
            }
            
            if (empty(trim($newTitle))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Title cannot be empty']);
                break;
            }
            
            // Limit title length
            $newTitle = substr(trim($newTitle), 0, 100);
            
            $stmt = $db->prepare("UPDATE chat_sessions SET title = :title, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':title' => $newTitle, ':id' => $sessionId]);
            
            echo json_encode(['success' => true, 'title' => $newTitle]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
