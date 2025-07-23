<?php
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../config.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['thread_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing thread_id']);
    exit;
}

$thread_id = $input['thread_id'];

try {
    $pdo = getDB();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete messages first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM messages WHERE thread_id = ?");
    $stmt->execute([$thread_id]);
    
    // Delete the thread
    $stmt = $pdo->prepare("DELETE FROM threads WHERE id = ?");
    $stmt->execute([$thread_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
