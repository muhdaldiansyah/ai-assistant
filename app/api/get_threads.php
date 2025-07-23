<?php
/**
 * api/get_threads.php
 * Get all threads for sidebar
 */

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDB();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Get threads with their first message for title generation
    $stmt = $pdo->prepare("
        SELECT 
            t.id, 
            t.title,
            t.created_at, 
            t.updated_at,
            (SELECT message FROM messages 
             WHERE thread_id = t.id AND role = 'user' 
             ORDER BY created_at ASC LIMIT 1) as first_message,
            (SELECT COUNT(*) FROM messages WHERE thread_id = t.id) as message_count
        FROM threads t
        ORDER BY t.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $threads = $stmt->fetchAll();
    
    // Format threads
    $formattedThreads = [];
    foreach ($threads as $thread) {
        $title = $thread['title'];
        
        // Generate title from first message if no title exists
        if (!$title && $thread['first_message']) {
            $title = substr($thread['first_message'], 0, 50);
            if (strlen($thread['first_message']) > 50) {
                $title .= '...';
            }
        }
        
        $formattedThreads[] = [
            'id' => $thread['id'],
            'title' => $title ?: 'New Chat',
            'created_at' => $thread['created_at'],
            'updated_at' => $thread['updated_at'],
            'message_count' => $thread['message_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'threads' => $formattedThreads
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_threads.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
