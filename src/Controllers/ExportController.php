<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Database\Database;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        switch ($action) {
            case 'export':
                $sessionId = $_POST['session_id'] ?? null;
                
                if ($sessionId) {
                    // Export single session
                    $stmt = $db->prepare("
                        SELECT m.*, s.title as session_title
                        FROM chat_messages m
                        JOIN chat_sessions s ON m.session_id = s.id
                        WHERE m.session_id = :session_id
                        ORDER BY m.created_at ASC
                    ");
                    $stmt->execute([':session_id' => $sessionId]);
                    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $exportData = [
                        'version' => '1.0',
                        'exported_at' => date('Y-m-d H:i:s'),
                        'session' => [
                            'id' => $sessionId,
                            'title' => $messages[0]['session_title'] ?? 'Exported Chat',
                            'messages' => $messages
                        ]
                    ];
                } else {
                    // Export all sessions
                    $stmt = $db->query("
                        SELECT s.*, COUNT(m.id) as message_count
                        FROM chat_sessions s
                        LEFT JOIN chat_messages m ON s.id = m.session_id
                        GROUP BY s.id
                        ORDER BY s.updated_at DESC
                    ");
                    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $exportData = [
                        'version' => '1.0',
                        'exported_at' => date('Y-m-d H:i:s'),
                        'sessions' => []
                    ];
                    
                    foreach ($sessions as $session) {
                        $stmt = $db->prepare("
                            SELECT * FROM chat_messages
                            WHERE session_id = :session_id
                            ORDER BY created_at ASC
                        ");
                        $stmt->execute([':session_id' => $session['id']]);
                        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $exportData['sessions'][] = [
                            'id' => $session['id'],
                            'title' => $session['title'],
                            'created_at' => $session['created_at'],
                            'updated_at' => $session['updated_at'],
                            'messages' => $messages
                        ];
                    }
                }
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="chat-export-' . date('Y-m-d') . '.json"');
                echo json_encode($exportData, JSON_PRETTY_PRINT);
                exit;
                
            case 'import':
                if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $fileContent = file_get_contents($_FILES['file']['tmp_name']);
                    $importData = json_decode($fileContent, true);
                    
                    if (!$importData || !isset($importData['version'])) {
                        throw new Exception('Invalid export file format');
                    }
                    
                    $importedCount = 0;
                    
                    if (isset($importData['session'])) {
                        // Import single session
                        $session = $importData['session'];
                        $stmt = $db->prepare("
                            INSERT INTO chat_sessions (title, created_at, updated_at)
                            VALUES (:title, :created_at, :updated_at)
                        ");
                        $stmt->execute([
                            ':title' => $session['title'] ?? 'Imported Chat',
                            ':created_at' => $session['messages'][0]['created_at'] ?? date('Y-m-d H:i:s'),
                            ':updated_at' => end($session['messages'])['created_at'] ?? date('Y-m-d H:i:s')
                        ]);
                        $newSessionId = $db->lastInsertId();
                        
                        foreach ($session['messages'] ?? [] as $message) {
                            $stmt = $db->prepare("
                                INSERT INTO chat_messages (session_id, role, content, model_used, context_chunks, created_at)
                                VALUES (:session_id, :role, :content, :model_used, :context_chunks, :created_at)
                            ");
                            $stmt->execute([
                                ':session_id' => $newSessionId,
                                ':role' => $message['role'],
                                ':content' => $message['content'],
                                ':model_used' => $message['model_used'] ?? null,
                                ':context_chunks' => $message['context_chunks'] ?? null,
                                ':created_at' => $message['created_at'] ?? date('Y-m-d H:i:s')
                            ]);
                            $importedCount++;
                        }
                    } elseif (isset($importData['sessions'])) {
                        // Import multiple sessions
                        foreach ($importData['sessions'] as $session) {
                            $stmt = $db->prepare("
                                INSERT INTO chat_sessions (title, created_at, updated_at)
                                VALUES (:title, :created_at, :updated_at)
                            ");
                            $stmt->execute([
                                ':title' => $session['title'] ?? 'Imported Chat',
                                ':created_at' => $session['created_at'] ?? date('Y-m-d H:i:s'),
                                ':updated_at' => $session['updated_at'] ?? date('Y-m-d H:i:s')
                            ]);
                            $newSessionId = $db->lastInsertId();
                            
                            foreach ($session['messages'] ?? [] as $message) {
                                $stmt = $db->prepare("
                                    INSERT INTO chat_messages (session_id, role, content, model_used, context_chunks, created_at)
                                    VALUES (:session_id, :role, :content, :model_used, :context_chunks, :created_at)
                                ");
                                $stmt->execute([
                                    ':session_id' => $newSessionId,
                                    ':role' => $message['role'],
                                    ':content' => $message['content'],
                                    ':model_used' => $message['model_used'] ?? null,
                                    ':context_chunks' => $message['context_chunks'] ?? null,
                                    ':created_at' => $message['created_at'] ?? date('Y-m-d H:i:s')
                                ]);
                                $importedCount++;
                            }
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'imported_count' => $importedCount,
                        'message' => "Successfully imported {$importedCount} messages"
                    ]);
                } else {
                    throw new Exception('No file uploaded');
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
