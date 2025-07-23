<?php
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../config.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['thread_id']) || !isset($input['title'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$thread_id = $input['thread_id'];
$title = trim($input['title']);

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Title cannot be empty']);
    exit;
}

try {
    $pdo = getDB();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE threads SET title = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$title, $thread_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update thread']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
