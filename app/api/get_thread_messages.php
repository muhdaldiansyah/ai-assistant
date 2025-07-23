<?php
/**
 * api/get_thread_messages.php
 * Get messages for a specific thread
 */

// Include the database connection
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$threadId = $_GET['thread_id'] ?? '';

if (empty($threadId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thread ID is required']);
    exit;
}

try {
    // Get database connection
    $pdo = getDB();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Get thread information
    $stmt = $pdo->prepare("
        SELECT t.id, t.title, t.created_at, t.updated_at, t.user_id,
               t.full_name, t.email, t.phone_number, t.nationality
        FROM threads t
        WHERE t.id = :thread_id
    ");
    $stmt->execute(['thread_id' => $threadId]);
    $thread = $stmt->fetch();
    
    if (!$thread) {
        http_response_code(404);
        echo json_encode(['error' => 'Thread not found']);
        exit;
    }
    
    // Get messages for the thread
    $stmt = $pdo->prepare("
        SELECT id, thread_id, role, message, created_at
        FROM messages 
        WHERE thread_id = :thread_id 
        ORDER BY created_at ASC
    ");
    $stmt->execute(['thread_id' => $threadId]);
    $messages = $stmt->fetchAll();
    
    // Format the response
    $response = [
        'success' => true,
        'thread' => [
            'id' => $thread['id'],
            'title' => $thread['title'] ?: 'Chat Session',
            'created_at' => $thread['created_at'],
            'updated_at' => $thread['updated_at'],
            'user_name' => $thread['full_name'] ?: 'Anonymous',
            'full_name' => $thread['full_name'],
            'email' => $thread['email'],
            'phone_number' => $thread['phone_number'],
            'nationality' => $thread['nationality']
        ],
        'messages' => $messages,
        'message_count' => count($messages),
        'title' => $thread['title'] ?: 'Chat Session'
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database Error in get_thread_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error occurred',
        'message' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("General Error in get_thread_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred',
        'message' => $e->getMessage()
    ]);
}
