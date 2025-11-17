<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Database\Database;

header('Content-Type: application/json');
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $title = $_POST['title'] ?? 'New Chat';
            $stmt = $db->prepare("INSERT INTO chat_sessions (title) VALUES (:title)");
            $stmt->execute([':title' => $title]);
            $sessionId = $db->lastInsertId();
            echo json_encode(['success' => true, 'session_id' => $sessionId]);
            break;
            
        case 'list':
            $stmt = $db->query("
                SELECT cs.*, 
                       COUNT(cm.id) as message_count,
                       MAX(cm.created_at) as last_message_at
                FROM chat_sessions cs
                LEFT JOIN chat_messages cm ON cs.id = cm.session_id
                GROUP BY cs.id
                ORDER BY cs.updated_at DESC
                LIMIT 50
            ");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'sessions' => $sessions]);
            break;
            
        case 'get':
            $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? null;
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
            $sessionId = $_POST['session_id'] ?? null;
            if ($sessionId) {
                $stmt = $db->prepare("DELETE FROM chat_sessions WHERE id = :id");
                $stmt->execute([':id' => $sessionId]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Session ID required']);
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
