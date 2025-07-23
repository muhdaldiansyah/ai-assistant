<?php
/**
 * Standalone API Handler with RAG, Database Storage, and Document Search
 * Updated for MariaDB 10.6 with User Information Collection
 */

require_once '../../config.php';

// Only set CORS headers globally
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Helper function to get embeddings from OpenAI
function getEmbedding($text) {
    $ch = curl_init(OPENAI_EMBED_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => OPENAI_EMBED_MODEL,
        'input' => $text
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to get embedding');
    }
    
    $result = json_decode($response, true);
    return $result['data'][0]['embedding'];
}

// Retrieve relevant documents using vector similarity
function retrieveDocuments($query, $limit = TOP_K_DOCUMENTS) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("retrieveDocuments: No database connection available");
            return []; // No database available
        }
        
        // Skip embedding for very short queries or special cases
        if (strlen($query) < 3 || $query === 'init') {
            return [];
        }
        
        // Check if OpenAI is configured for embeddings
        if (OPENAI_API_KEY === 'sk-your-openai-api-key-here' || empty(OPENAI_API_KEY)) {
            error_log("retrieveDocuments: OpenAI API key not configured");
            return [];
        }
        
        // Check if documents table exists
        try {
            $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM documents");
            $result = $checkStmt->fetch();
            if ($result['count'] == 0) {
                error_log("retrieveDocuments: No documents in database");
                return [];
            }
        } catch (PDOException $e) {
            error_log("retrieveDocuments: Documents table might not exist - " . $e->getMessage());
            return [];
        }
        
        // Try to get embedding
        try {
            $embedding = getEmbedding($query);
        } catch (Exception $e) {
            error_log("retrieveDocuments: Failed to get embedding - " . $e->getMessage());
            // Fall back to text search below
            $embedding = null;
        }
        
        $documents = [];
        
        // Use vector similarity if embedding is available
        if ($embedding !== null) {
            $documents = retrieveDocumentsByVector($embedding, $limit);
        }
        
        // If no vector results, try text search
        if (empty($documents)) {
            try {
                // First check if content column has FULLTEXT index
                $stmt = $pdo->prepare("
                    SELECT id, content, metadata
                    FROM documents 
                    WHERE content LIKE :query
                    ORDER BY created_at DESC 
                    LIMIT :limit
                ");
                $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $documents = $stmt->fetchAll();
                
                error_log("retrieveDocuments: Found " . count($documents) . " documents using text search");
            } catch (PDOException $e) {
                error_log("retrieveDocuments: Text search failed - " . $e->getMessage());
                // Try without FULLTEXT search
                try {
                    $stmt = $pdo->prepare("
                        SELECT id, content, metadata
                        FROM documents 
                        WHERE content LIKE :query
                        ORDER BY created_at DESC 
                        LIMIT :limit
                    ");
                    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    $stmt->execute();
                    $documents = $stmt->fetchAll();
                } catch (Exception $e2) {
                    error_log("retrieveDocuments: Fallback search also failed - " . $e2->getMessage());
                    return [];
                }
            }
        }
        
        return $documents;
    } catch (Exception $e) {
        error_log("Document retrieval error: " . $e->getMessage());
        return [];
    }
}

// Save message to database
function saveMessage($threadId, $role, $message) {
    try {
        // Don't save init messages
        if ($message === 'init') {
            return true;
        }
        
        $pdo = getDB();
        if (!$pdo) {
            return true; // No database, but don't fail
        }
        
        $stmt = $pdo->prepare(
            "INSERT INTO messages (thread_id, role, message, created_at) 
             VALUES (:thread_id, :role, :message, NOW())"
        );
        $stmt->execute([
            'thread_id' => $threadId,
            'role' => $role,
            'message' => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Save message error: " . $e->getMessage());
        return false;
    }
}

// Update thread timestamp
function updateThreadTimestamp($threadId) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return; // No database available
        }
        $stmt = $pdo->prepare("UPDATE threads SET updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $threadId]);
    } catch (Exception $e) {
        error_log("Update thread error: " . $e->getMessage());
    }
}

// Create new thread with user information
function createThread($threadId, $title = null, $userId = null, $userInfo = null) {
    try {
        // Validate thread ID
        if (!$threadId || strlen($threadId) < 10) {
            error_log("Invalid thread ID: $threadId");
            return false;
        }
        
        $pdo = getDB();
        if (!$pdo) {
            error_log("createThread: No database connection available");
            return false; // Return false for database errors
        }
        
        error_log("createThread called - threadId: $threadId");
        error_log("userInfo received: " . print_r($userInfo, true));
        
        // Check if thread already exists with proper error handling
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM threads WHERE id = :id");
            $stmt->execute(['id' => $threadId]);
            $existingThread = $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error checking thread existence: " . $e->getMessage());
            return false;
        }
        
        if (!$existingThread) {
            // Create new thread
            error_log("Creating new thread with ID: $threadId");
            
            $sql = "INSERT INTO threads (id, title, user_id, full_name, email, phone_number, nationality, created_at, updated_at) 
                    VALUES (:id, :title, :user_id, :full_name, :email, :phone_number, :nationality, NOW(), NOW())";
            
            $params = [
                'id' => $threadId,
                'title' => $title,
                'user_id' => $userId,
                'full_name' => null,
                'email' => null,
                'phone_number' => null,
                'nationality' => null
            ];
            
            // Add user information if provided
            if ($userInfo && is_array($userInfo)) {
                $params['full_name'] = !empty($userInfo['full_name']) ? trim($userInfo['full_name']) : null;
                $params['email'] = !empty($userInfo['email']) ? trim($userInfo['email']) : null;
                $params['phone_number'] = !empty($userInfo['phone_number']) ? trim($userInfo['phone_number']) : null;
                $params['nationality'] = !empty($userInfo['nationality']) ? trim($userInfo['nationality']) : null;
                
                error_log("Inserting user info into database:");
                error_log("- full_name: " . ($params['full_name'] ?? 'NULL'));
                error_log("- email: " . ($params['email'] ?? 'NULL'));
                error_log("- phone_number: " . ($params['phone_number'] ?? 'NULL'));
                error_log("- nationality: " . ($params['nationality'] ?? 'NULL'));
            } else {
                error_log("No valid user info provided for new thread");
            }
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("Thread created successfully with user info");
                
                // Verify data was inserted
                $verifyStmt = $pdo->prepare("SELECT full_name, email, phone_number, nationality FROM threads WHERE id = :id");
                $verifyStmt->execute(['id' => $threadId]);
                $inserted = $verifyStmt->fetch();
                error_log("Verification - inserted data: " . print_r($inserted, true));
            } else {
                error_log("Failed to create thread. Error info: " . print_r($stmt->errorInfo(), true));
            }
            
        } else {
            error_log("Thread exists, checking if user info needs updating");
            
            // Thread exists, update user info if provided and current values are empty
            if ($userInfo && is_array($userInfo)) {
                $updateFields = [];
                $updateParams = ['id' => $threadId];
                
                if (empty($existingThread['full_name']) && !empty($userInfo['full_name'])) {
                    $updateFields[] = "full_name = :full_name";
                    $updateParams['full_name'] = trim($userInfo['full_name']);
                }
                
                if (empty($existingThread['email']) && !empty($userInfo['email'])) {
                    $updateFields[] = "email = :email";
                    $updateParams['email'] = trim($userInfo['email']);
                }
                
                if (!empty($userInfo['phone_number'])) {
                    $updateFields[] = "phone_number = :phone_number";
                    $updateParams['phone_number'] = trim($userInfo['phone_number']);
                }
                
                if (!empty($userInfo['nationality'])) {
                    $updateFields[] = "nationality = :nationality";
                    $updateParams['nationality'] = trim($userInfo['nationality']);
                }
                
                if (!empty($updateFields)) {
                    $updateFields[] = "updated_at = NOW()";
                    $sql = "UPDATE threads SET " . implode(", ", $updateFields) . " WHERE id = :id";
                    
                    error_log("Updating thread with SQL: $sql");
                    error_log("Update params: " . print_r($updateParams, true));
                    
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute($updateParams);
                    
                    if ($result) {
                        error_log("Thread updated successfully");
                    } else {
                        error_log("Failed to update thread. Error info: " . print_r($stmt->errorInfo(), true));
                    }
                } else {
                    error_log("No fields to update - existing thread already has user info");
                }
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("Create thread error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Get thread messages
function getThreadMessages($threadId) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return []; // No database available
        }
        $stmt = $pdo->prepare(
            "SELECT role, message, created_at 
             FROM messages 
             WHERE thread_id = :thread_id 
             ORDER BY created_at ASC"
        );
        $stmt->execute(['thread_id' => $threadId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get messages error: " . $e->getMessage());
        return [];
    }
}

// Search documents
function searchDocuments($query, $limit = 10) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return []; // No database available
        }
        
        // Try vector search first
        $documents = retrieveDocuments($query, $limit);
        
        // If no results, try text search
        if (empty($documents)) {
            $stmt = $pdo->prepare(
                "SELECT id, content, metadata 
                 FROM documents 
                 WHERE content LIKE :query 
                 ORDER BY created_at DESC
                 LIMIT :limit"
            );
            $stmt->execute([
                'query' => '%' . $query . '%',
                'limit' => $limit
            ]);
            $documents = $stmt->fetchAll();
        }
        
        return $documents;
    } catch (Exception $e) {
        error_log("Search documents error: " . $e->getMessage());
        return [];
    }
}

// Parse user info from request
function parseUserInfo($input) {
    if (empty($input)) {
        error_log("parseUserInfo: Empty input received");
        return null;
    }
    
    if (is_string($input)) {
        $decoded = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("parseUserInfo: JSON decode error - " . json_last_error_msg());
            return null;
        }
        error_log("parseUserInfo: Successfully parsed from JSON string: " . print_r($decoded, true));
        return $decoded;
    }
    
    if (is_array($input)) {
        error_log("parseUserInfo: Input is already an array: " . print_r($input, true));
        return $input;
    }
    
    error_log("parseUserInfo: Unknown input type: " . gettype($input));
    return null;
}

// Main request handling
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null; // Default to null for GET

if ($method === 'GET') {
    switch ($action) {
        case 'thread':
            // Set JSON header for this endpoint
            header('Content-Type: application/json');
            
            $threadId = $_GET['thread_id'] ?? '';
            if (empty($threadId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Thread ID required']);
                exit;
            }
            
            $messages = getThreadMessages($threadId);
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'search':
            // Set JSON header for this endpoint
            header('Content-Type: application/json');
            
            $query = $_GET['q'] ?? '';
            if (empty($query)) {
                http_response_code(400);
                echo json_encode(['error' => 'Search query required']);
                exit;
            }
            
            $documents = searchDocuments($query);
            echo json_encode(['success' => true, 'documents' => $documents]);
            break;
            
        case 'stream':
            // SSE streaming chat with RAG
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable Nginx buffering
            
            // Disable output buffering
            while (ob_get_level()) {
                ob_end_flush();
            }
            flush();
            
            $threadId = $_GET['thread_id'] ?? uniqid('thread_');
            $message = $_GET['message'] ?? '';
            $userInfoRaw = $_GET['user_info'] ?? null;
            
            error_log("=== STREAM REQUEST START ===");
            error_log("Raw user_info parameter: " . var_export($userInfoRaw, true));
            
            $userInfo = parseUserInfo($userInfoRaw);
            
            error_log("Stream request received:");
            error_log("- threadId: $threadId");
            error_log("- message: $message");
            error_log("- userInfo after parsing: " . print_r($userInfo, true));
            
            if (empty($message)) {
                echo "event: error\n";
                echo "data: Message required\n\n";
                flush();
                exit;
            }
            
            try {
                // Create thread with user info if needed
                $threadCreated = createThread($threadId, null, null, $userInfo);
                error_log("Thread creation result: " . ($threadCreated ? 'SUCCESS' : 'FAILED'));
                
                // Save user message
                saveMessage($threadId, 'user', $message);
                
                // Check if OpenAI is configured
                if (OPENAI_API_KEY === 'sk-your-openai-api-key-here' || empty(OPENAI_API_KEY)) {
                    echo "event: chunk\n";
                    echo "data: Please configure your OpenAI API key in config.php\n\n";
                    echo "event: done\n";
                    echo "data: complete\n\n";
                    flush();
                    exit;
                }
                
                // Retrieve relevant documents
                $documents = retrieveDocuments($message);
                $context = array_map(function($doc) {
                    return $doc['content'] ?? '';
                }, $documents);
                
                // Log document retrieval status
                if (empty($documents)) {
                    error_log("No documents retrieved for query: $message");
                } else {
                    error_log("Retrieved " . count($documents) . " documents for query: $message");
                }
                
                // Build messages for OpenAI
                $messages = [
                    ['role' => 'system', 'content' => getSystemPrompt()]
                ];
                
                // Add user context if available
                if ($userInfo && is_array($userInfo)) {
                    $userContext = "User Information:\n";
                    if (!empty($userInfo['full_name'])) $userContext .= "Name: " . $userInfo['full_name'] . "\n";
                    if (!empty($userInfo['nationality'])) $userContext .= "Nationality: " . $userInfo['nationality'] . "\n";
                    $userContext .= "\nPlease personalize your responses appropriately based on this information.";
                    
                    $messages[] = [
                        'role' => 'system',
                        'content' => $userContext
                    ];
                }
                
                if (!empty($context)) {
                    $messages[] = [
                        'role' => 'system', 
                        'content' => "Relevant information:\n\n" . implode("\n\n", $context)
                    ];
                } else {
                    // Inform AI that no documents are available
                    $messages[] = [
                        'role' => 'system',
                        'content' => "Note: No documents are currently available in the knowledge base. Please provide general assistance based on your training."
                    ];
                }
                
                // Add conversation history (limit to last 10 messages to avoid token limits)
                $history = getThreadMessages($threadId);
                $recentHistory = array_slice($history, -10);
                
                foreach ($recentHistory as $hist) {
                    if ($hist['role'] !== 'user' || $hist['message'] !== $message) {
                        $messages[] = [
                            'role' => $hist['role'],
                            'content' => $hist['message']
                        ];
                    }
                }
                
                // Add current message
                $messages[] = ['role' => 'user', 'content' => $message];
                
                // Stream response from OpenAI
                $assistantReply = '';
                
                $ch = curl_init(OPENAI_API_URL);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => OPENAI_MODEL,
                    'messages' => $messages,
                    'max_tokens' => MAX_TOKENS,
                    'temperature' => TEMPERATURE,
                    'stream' => true
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY
                ]);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$assistantReply) {
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        if (strpos($line, 'data: ') === 0) {
                            $json = substr($line, 6);
                            if ($json === '[DONE]') {
                                echo "event: done\n";
                                echo "data: complete\n\n";
                                flush();
                            } else {
                                $decoded = json_decode($json, true);
                                if (isset($decoded['choices'][0]['delta']['content'])) {
                                    $content = $decoded['choices'][0]['delta']['content'];
                                    $assistantReply .= $content;
                                    echo "event: chunk\n";
                                    echo "data: " . str_replace("\n", "\ndata: ", $content) . "\n\n";
                                    flush();
                                }
                            }
                        }
                    }
                    return strlen($data);
                });
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    echo "event: error\n";
                    echo "data: API Error: " . $error . "\n\n";
                    flush();
                }
                
                // Save assistant reply
                if (!empty($assistantReply)) {
                    saveMessage($threadId, 'assistant', $assistantReply);
                    updateThreadTimestamp($threadId);
                }
                
                error_log("=== STREAM REQUEST END ===");
                
            } catch (Exception $e) {
                error_log("Stream error: " . $e->getMessage());
                echo "event: error\n";
                echo "data: " . $e->getMessage() . "\n\n";
                echo "event: done\n";
                echo "data: complete\n\n";
                flush();
            }
            
            break;
            
        default:
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} elseif ($method === 'POST') {
    // Set JSON header for POST endpoints
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    // Extract action from JSON body for POST requests
    $action = $input['action'] ?? null;
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['error' => 'Action required in request body']);
        exit;
    }
    
    switch ($action) {
        case 'create_thread':
            $threadId = $input['thread_id'] ?? null;
            $userInfo = $input['user_info'] ?? null;
            
            if (empty($threadId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Thread ID required']);
                exit;
            }
            
            try {
                $success = createThread($threadId, null, null, $userInfo);
                if ($success) {
                    echo json_encode(['success' => true, 'thread_id' => $threadId]);
                } else {
                    throw new Exception('Failed to create or update thread');
                }
            } catch (Exception $e) {
                error_log("Create thread error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
            
        case 'chat':
            // Non-streaming chat with RAG
            $threadId = $input['thread_id'] ?? uniqid('thread_');
            $message = $input['message'] ?? '';
            $userInfo = $input['user_info'] ?? null;
            
            // Handle init message
            if ($message === 'init') {
                createThread($threadId, null, null, $userInfo);
                echo json_encode([
                    'success' => true,
                    'thread_id' => $threadId,
                    'response' => 'Thread initialized',
                    'documents_used' => 0
                ]);
                exit;
            }
            
            if (empty($message)) {
                http_response_code(400);
                echo json_encode(['error' => 'Message required']);
                exit;
            }
            
            try {
                // Create thread with user info if needed
                createThread($threadId, null, null, $userInfo);
                
                // Save user message
                saveMessage($threadId, 'user', $message);
                
                // Check if OpenAI API key is configured
                if (OPENAI_API_KEY === 'sk-your-openai-api-key-here' || empty(OPENAI_API_KEY)) {
                    echo json_encode([
                        'success' => true,
                        'thread_id' => $threadId,
                        'response' => 'Please configure your OpenAI API key in config.php',
                        'documents_used' => 0
                    ]);
                    exit;
                }
                
                // Retrieve relevant documents
                $documents = retrieveDocuments($message);
                $context = array_map(function($doc) {
                    return $doc['content'] ?? '';
                }, $documents);
                
                // Build messages
                $messages = [
                    ['role' => 'system', 'content' => getSystemPrompt()]
                ];
                
                // Add user context if available
                if ($userInfo && is_array($userInfo)) {
                    $userContext = "User Information:\n";
                    if (!empty($userInfo['full_name'])) $userContext .= "Name: " . $userInfo['full_name'] . "\n";
                    if (!empty($userInfo['nationality'])) $userContext .= "Nationality: " . $userInfo['nationality'] . "\n";
                    $userContext .= "\nPlease personalize your responses appropriately based on this information.";
                    
                    $messages[] = [
                        'role' => 'system',
                        'content' => $userContext
                    ];
                }
                
                if (!empty($context)) {
                    $messages[] = [
                        'role' => 'system', 
                        'content' => "Relevant information:\n\n" . implode("\n\n", $context)
                    ];
                } else {
                    // Inform AI that no documents are available
                    $messages[] = [
                        'role' => 'system',
                        'content' => "Note: No documents are currently available in the knowledge base. Please provide general assistance based on your training."
                    ];
                }
                
                // Add history
                $history = getThreadMessages($threadId);
                $recentHistory = array_slice($history, -10);
                
                foreach ($recentHistory as $hist) {
                    if ($hist['role'] !== 'user' || $hist['message'] !== $message) {
                        $messages[] = [
                            'role' => $hist['role'],
                            'content' => $hist['message']
                        ];
                    }
                }
                
                $messages[] = ['role' => 'user', 'content' => $message];
                
                // Get response from OpenAI
                $ch = curl_init(OPENAI_API_URL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => OPENAI_MODEL,
                    'messages' => $messages,
                    'max_tokens' => MAX_TOKENS,
                    'temperature' => TEMPERATURE
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENAI_API_KEY
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new Exception('Curl error: ' . $error);
                }
                
                if ($httpCode !== 200) {
                    $errorData = json_decode($response, true);
                    throw new Exception('OpenAI API error: ' . ($errorData['error']['message'] ?? 'Unknown error'));
                }
                
                $result = json_decode($response, true);
                $assistantReply = $result['choices'][0]['message']['content'] ?? '';
                
                // Save assistant reply
                if (!empty($assistantReply)) {
                    saveMessage($threadId, 'assistant', $assistantReply);
                    updateThreadTimestamp($threadId);
                }
                
                echo json_encode([
                    'success' => true,
                    'thread_id' => $threadId,
                    'response' => $assistantReply,
                    'documents_used' => count($documents)
                ]);
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
            
        case 'upload':
            // Upload and embed document
            $content = $input['content'] ?? '';
            $metadata = $input['metadata'] ?? [];
            
            if (empty($content)) {
                http_response_code(400);
                echo json_encode(['error' => 'Content required']);
                exit;
            }
            
            try {
                $embedding = getEmbedding($content);
                
                $pdo = getDB();
                if (!$pdo) {
                    throw new Exception('Database not available');
                }
                
                $stmt = $pdo->prepare(
                    "INSERT INTO documents (content, metadata, embedding) 
                     VALUES (:content, :metadata, :embedding)"
                );
                $stmt->execute([
                    'content' => $content,
                    'metadata' => json_encode($metadata),
                    'embedding' => json_encode($embedding)
                ]);
                
                echo json_encode([
                    'success' => true,
                    'document_id' => $pdo->lastInsertId()
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload document: ' . $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}