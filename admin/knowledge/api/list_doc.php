<?php
/**
 * Documents API - FINAL, ROBUST & CORRECTED VERSION
 * Handles list, get, save, and delete operations for documents.
 * Fixes "SQLSTATE[HY093]: Invalid parameter number" by using positional placeholders.
 * Includes all previous fixes like JSON validation.
 */

// Start output buffering to prevent header issues
ob_start();

// Include the database connection
require_once __DIR__ . '/../../../config.php';

// Clear any output that might have been generated
ob_end_clean();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Production-safe: Log errors, don't display them.
ini_set('display_errors', 0);
error_reporting(E_ALL);

$op = $_GET['op'] ?? ($_POST['op'] ?? 'list');
$doc = $_GET['doc'] ?? ($_POST['doc'] ?? '');

function generateEmbedding($text) {
    $apiKey = OPENAI_API_KEY;
    if (empty($apiKey)) { throw new Exception('OPENAI_API_KEY is not configured.'); }
    $data = [ 'model' => OPENAI_EMBED_MODEL, 'input' => $text ];
    $options = [ 'http' => [ 'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey", 'method' => 'POST', 'content' => json_encode($data), 'ignore_errors' => true ]];
    $context = stream_context_create($options);
    $response = file_get_contents(OPENAI_EMBED_URL, false, $context);
    if ($response === false) { throw new Exception('Failed to connect to OpenAI API.'); }
    $result = json_decode($response, true);
    if (isset($result['error'])) { throw new Exception('OpenAI API error: ' . $result['error']['message']); }
    if (!isset($result['data'][0]['embedding'])) { throw new Exception('Invalid embedding response from OpenAI.'); }
    return $result['data'][0]['embedding'];
}
function chunkText($text, $maxTokens = 500) {
    $chunkSize = max(200, $maxTokens * 4); $chunks = []; $textLength = mb_strlen($text);
    for ($i = 0; $i < $textLength; $i += $chunkSize) {
        $chunk = mb_substr($text, $i, $chunkSize + 200);
        if (!empty(trim($chunk))) { $chunks[] = $chunk; }
    }
    return $chunks;
}

try {
    // Get database connection
    $pdo = getDB();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    switch ($op) {
        case 'list':
            $stmt = $pdo->query("
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.filename')) AS filename,
                    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.source_file')) AS source_file,
                    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.program_title')) AS program_title,
                    COUNT(*) AS chunks
                FROM documents
                WHERE JSON_VALID(metadata) = 1
                GROUP BY filename, source_file, program_title
                ORDER BY MAX(created_at) DESC
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($results);
            break;

        case 'get':
            if (empty($doc)) { throw new Exception('Document identifier is missing.'); }
            // DIPERBAIKI: Menggunakan placeholder posisi (?) untuk menghindari error HY093
            $stmt = $pdo->prepare("
                SELECT content, metadata
                FROM documents
                WHERE JSON_VALID(metadata) = 1 AND (
                       JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.filename')) = ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.program_title')) = ?
                    OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.source_file')) = ?
                )
                ORDER BY CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.chunk')) AS UNSIGNED) ASC
            ");
            $stmt->execute([$doc, $doc, $doc]); // Memberikan 3 nilai untuk 3 placeholder
            $chunks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($chunks)) { throw new Exception('Document not found or its metadata is corrupt.'); }

            $content = implode("\n\n---\n\n", array_column($chunks, 'content'));
            echo json_encode([ 'success' => true, 'content' => $content, 'chunks' => count($chunks), 'metadata' => json_decode($chunks[0]['metadata'], true) ]);
            break;
        
        case 'save':
            if (empty($doc)) { throw new Exception('Original document identifier is missing.'); }
            $data = json_decode(file_get_contents('php://input'), true);
            $newFilename = $data['filename'] ?? '';
            $content = $data['content'] ?? '';
            if (empty($newFilename) || empty(trim($content))) { throw new Exception('New filename and content cannot be empty.'); }
            
            $pdo->beginTransaction();
            try {
                // DIPERBAIKI: Menggunakan placeholder posisi (?) di sini juga untuk konsistensi
                $deleteStmt = $pdo->prepare("DELETE FROM documents WHERE JSON_VALID(metadata) = 1 AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.filename')) = ?");
                $deleteStmt->execute([$doc]);

                $insertStmt = $pdo->prepare("INSERT INTO documents (content, metadata, embedding) VALUES (:content, :metadata, :embedding)");
                $chunks = chunkText($content);
                foreach ($chunks as $index => $chunk) {
                    $metadata = [ 'filename' => $newFilename, 'chunk' => $index, 'total_chunks' => count($chunks), 'created_at' => date('Y-m-d H:i:s') ];
                    $insertStmt->execute([ 'content' => $chunk, 'metadata' => json_encode($metadata), 'embedding' => json_encode(generateEmbedding($chunk)) ]);
                }
                $pdo->commit();
                echo json_encode([ 'success' => true, 'status' => 'saved', 'filename' => $newFilename, 'chunks' => count($chunks) ]);
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;

        case 'delete':
            if (empty($doc)) { throw new Exception('Document identifier is missing.'); }
            // DIPERBAIKI: Menggunakan placeholder posisi (?)
            $stmt = $pdo->prepare("DELETE FROM documents WHERE JSON_VALID(metadata) = 1 AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.filename')) = ?");
            $stmt->execute([$doc]);
            echo json_encode(['success' => true, 'status' => 'deleted', 'chunks_deleted' => $stmt->rowCount()]);
            break;

        default:
            throw new Exception('Unknown operation: ' . htmlspecialchars($op));
    }

} catch (Exception $e) {
    error_log("API Error in list_doc.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An internal server error occurred. Please check the logs.'
    ]);
}

if (ob_get_level()) { ob_end_flush(); }