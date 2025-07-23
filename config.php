<?php
/**
 * Configuration File for AI Assistant
 * Uses environment variables for security
 * Copy .env.example to .env and update with your values
 */

// Load environment variables from .env file
function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) {
        return; // .env file is optional
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV) && !getenv($name)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env file
loadEnv(__DIR__ . '/.env');

// Helper function to get environment variable with fallback
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// OpenAI Configuration
define('OPENAI_API_KEY', env('OPENAI_API_KEY', 'your-openai-api-key-here'));
define('OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini'));
define('OPENAI_EMBED_MODEL', env('OPENAI_EMBED_MODEL', 'text-embedding-3-small'));
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_EMBED_URL', 'https://api.openai.com/v1/embeddings');
define('MAX_TOKENS', (int)env('MAX_TOKENS', 1000));
define('TEMPERATURE', (float)env('TEMPERATURE', 0.3));
define('TOP_K_DOCUMENTS', (int)env('TOP_K_DOCUMENTS', 5));

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'ai_assistant'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// System prompt for the assistant (fallback if database is not available)
define('DEFAULT_SYSTEM_PROMPT', 'You are an AI Assistant designed to help users with their questions and tasks. You provide comprehensive information and assistance on a wide range of topics. You help users by answering questions, providing explanations, and offering guidance. Always be professional, accurate, and helpful in your responses.');

// Function to get active system prompt from database
function getSystemPrompt($promptKey = 'main') {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return DEFAULT_SYSTEM_PROMPT;
        }
        
        $stmt = $pdo->prepare("
            SELECT prompt_content 
            FROM system_prompts 
            WHERE prompt_key LIKE :key 
            AND is_active = 1 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['key' => $promptKey . '%']);
        $result = $stmt->fetch();
        
        return $result ? $result['prompt_content'] : DEFAULT_SYSTEM_PROMPT;
    } catch (Exception $e) {
        error_log("Error fetching system prompt: " . $e->getMessage());
        return DEFAULT_SYSTEM_PROMPT;
    }
}

// Enable error reporting for debugging (set to false in production)
define('DEBUG_MODE', env('DEBUG_MODE', 'true') === 'true');

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database connection function
function getDB() {
    static $pdo = null;
    static $dbAvailable = null;
    
    if ($dbAvailable === false) {
        return null; // Skip if we already know DB is not available
    }
    
    if ($pdo === null) {
        // Check if database is configured
        if (DB_HOST === 'localhost' && DB_USER === 'root' && DB_PASSWORD === '' && DB_NAME === 'ai_assistant') {
            // Default configuration - check if database exists
            try {
                $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASSWORD);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $tempPdo = null;
            } catch (PDOException $e) {
                error_log("Database creation check failed: " . $e->getMessage());
            }
        }
        
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            $dbAvailable = true;
        } catch (PDOException $e) {
            $dbAvailable = false;
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

// Vector similarity calculation using cosine similarity
function calculateCosineSimilarity($vector1, $vector2) {
    if (count($vector1) !== count($vector2)) {
        return 0;
    }
    
    $dotProduct = 0;
    $magnitude1 = 0;
    $magnitude2 = 0;
    
    for ($i = 0; $i < count($vector1); $i++) {
        $dotProduct += $vector1[$i] * $vector2[$i];
        $magnitude1 += $vector1[$i] * $vector1[$i];
        $magnitude2 += $vector2[$i] * $vector2[$i];
    }
    
    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);
    
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0;
    }
    
    return $dotProduct / ($magnitude1 * $magnitude2);
}

// Function to retrieve documents using vector similarity in MariaDB
function retrieveDocumentsByVector($queryEmbedding, $limit = TOP_K_DOCUMENTS) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            error_log("retrieveDocumentsByVector: No database connection");
            return [];
        }
        
        // Check if query embedding is valid
        if (!is_array($queryEmbedding) || empty($queryEmbedding)) {
            error_log("retrieveDocumentsByVector: Invalid query embedding");
            return [];
        }
        
        // Check if documents table exists
        try {
            $stmt = $pdo->query("SELECT 1 FROM documents LIMIT 1");
        } catch (PDOException $e) {
            error_log("retrieveDocumentsByVector: Documents table might not exist - " . $e->getMessage());
            return [];
        }
        
        // Get all documents with embeddings
        $stmt = $pdo->prepare("
            SELECT id, content, metadata, embedding 
            FROM documents 
            WHERE embedding IS NOT NULL 
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        $stmt->execute();
        $documents = $stmt->fetchAll();
        
        if (empty($documents)) {
            error_log("retrieveDocumentsByVector: No documents found in database");
            return [];
        }
        
        // Calculate similarities
        $similarities = [];
        foreach ($documents as $doc) {
            $docEmbedding = json_decode($doc['embedding'], true);
            if (is_array($docEmbedding)) {
                $similarity = calculateCosineSimilarity($queryEmbedding, $docEmbedding);
                $similarities[] = [
                    'id' => $doc['id'],
                    'content' => $doc['content'],
                    'metadata' => $doc['metadata'],
                    'similarity' => $similarity
                ];
            }
        }
        
        // Sort by similarity descending
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Return top results
        return array_slice($similarities, 0, $limit);
        
    } catch (Exception $e) {
        error_log("Document retrieval error: " . $e->getMessage());
        return [];
    }
}
