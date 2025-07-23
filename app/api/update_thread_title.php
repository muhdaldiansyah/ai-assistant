<?php
/**
 * api/update_thread_title.php
 * Update thread title based on first message
 */

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$threadId = $input['thread_id'] ?? '';
$title = $input['title'] ?? '';

if (empty($threadId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thread ID is required']);
    exit;
}

try {
    $pdo = getDB();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // If no title provided, generate from first message
    if (empty($title)) {
        $stmt = $pdo->prepare("
            SELECT message 
            FROM messages 
            WHERE thread_id = :thread_id AND role = 'user' 
            ORDER BY created_at ASC 
            LIMIT 1
        ");
        $stmt->execute(['thread_id' => $threadId]);
        $firstMessage = $stmt->fetchColumn();
        
        if ($firstMessage) {
            $title = substr($firstMessage, 0, 50);
            if (strlen($firstMessage) > 50) {
                $title .= '...';
            }
        } else {
            $title = 'New Chat';
        }
    }
    
    // Update thread title
    $stmt = $pdo->prepare("
        UPDATE threads 
        SET title = :title, updated_at = NOW() 
        WHERE id = :thread_id
    ");
    $stmt->execute([
        'title' => $title,
        'thread_id' => $threadId
    ]);
    
    echo json_encode([
        'success' => true,
        'title' => $title
    ]);
    
} catch (Exception $e) {
    error_log("Error in update_thread_title.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
