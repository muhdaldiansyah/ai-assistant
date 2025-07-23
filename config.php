<?php
/**
 * Configuration File for AI Assistant
 * Contains only configuration constants and service includes
 * Business logic has been moved to dedicated service classes
 */

// Load services
require_once __DIR__ . '/services/environment_service.php';
require_once __DIR__ . '/services/database_service.php';
require_once __DIR__ . '/services/vector_service.php';
require_once __DIR__ . '/services/system_service.php';

// Load environment variables from .env file
EnvironmentService::load_env(__DIR__ . '/.env');

// OpenAI Configuration
define('OPENAI_API_KEY', EnvironmentService::env('OPENAI_API_KEY', 'your-openai-api-key-here'));
define('OPENAI_MODEL', EnvironmentService::env('OPENAI_MODEL', 'gpt-4o-mini'));
define('OPENAI_EMBED_MODEL', EnvironmentService::env('OPENAI_EMBED_MODEL', 'text-embedding-3-small'));
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_EMBED_URL', 'https://api.openai.com/v1/embeddings');
define('MAX_TOKENS', (int)EnvironmentService::env('MAX_TOKENS', 1000));
define('TEMPERATURE', (float)EnvironmentService::env('TEMPERATURE', 0.3));
define('TOP_K_DOCUMENTS', (int)EnvironmentService::env('TOP_K_DOCUMENTS', 5));

// Database Configuration
define('DB_HOST', EnvironmentService::env('DB_HOST', 'localhost'));
define('DB_PORT', EnvironmentService::env('DB_PORT', '3306'));
define('DB_NAME', EnvironmentService::env('DB_NAME', 'ai_assistant'));
define('DB_USER', EnvironmentService::env('DB_USER', 'root'));
define('DB_PASSWORD', EnvironmentService::env('DB_PASSWORD', ''));

// Application Configuration
define('DEBUG_MODE', EnvironmentService::env('DEBUG_MODE', 'true') === 'true');
define('APP_NAME', EnvironmentService::env('APP_NAME', 'AI Assistant'));
define('APP_VERSION', EnvironmentService::env('APP_VERSION', '1.0.0'));
define('APP_URL', EnvironmentService::env('APP_URL', 'http://localhost'));

// Security Configuration
define('SESSION_LIFETIME', (int)EnvironmentService::env('SESSION_LIFETIME', 3600)); // 1 hour
define('BCRYPT_ROUNDS', (int)EnvironmentService::env('BCRYPT_ROUNDS', 12));
define('SESSION_NAME', EnvironmentService::env('SESSION_NAME', 'ai_assistant_session'));

// File Upload Configuration
define('MAX_UPLOAD_SIZE', (int)EnvironmentService::env('MAX_UPLOAD_SIZE', 10485760)); // 10MB
define('ALLOWED_FILE_TYPES', EnvironmentService::env('ALLOWED_FILE_TYPES', 'pdf,txt,doc,docx'));
define('UPLOAD_PATH', EnvironmentService::env('UPLOAD_PATH', __DIR__ . '/uploads'));

// RAG Configuration
define('CHUNK_SIZE', (int)EnvironmentService::env('CHUNK_SIZE', 500)); // tokens per chunk
define('CHUNK_OVERLAP', (int)EnvironmentService::env('CHUNK_OVERLAP', 50)); // token overlap
define('MIN_SIMILARITY_SCORE', (float)EnvironmentService::env('MIN_SIMILARITY_SCORE', 0.7));

// System prompt for the assistant (fallback if database is not available)
define('DEFAULT_SYSTEM_PROMPT', EnvironmentService::env('DEFAULT_SYSTEM_PROMPT', 
    'You are an AI Assistant designed to help users with their questions and tasks. ' .
    'You provide comprehensive information and assistance on a wide range of topics. ' .
    'You help users by answering questions, providing explanations, and offering guidance. ' .
    'Always be professional, accurate, and helpful in your responses.'
));

// Rate Limiting Configuration
define('RATE_LIMIT_REQUESTS', (int)EnvironmentService::env('RATE_LIMIT_REQUESTS', 100));
define('RATE_LIMIT_WINDOW', (int)EnvironmentService::env('RATE_LIMIT_WINDOW', 3600)); // 1 hour

// Logging Configuration
define('LOG_LEVEL', EnvironmentService::env('LOG_LEVEL', 'INFO'));
define('LOG_PATH', EnvironmentService::env('LOG_PATH', __DIR__ . '/logs'));

// Enable error reporting for debugging (set to false in production)
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . '/php_errors.log');
}

// Set timezone
date_default_timezone_set(EnvironmentService::env('TIMEZONE', 'UTC'));

// Session Configuration - only set if no session is active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name', SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', EnvironmentService::env('HTTPS_ONLY', 'false') === 'true' ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
}

// Create necessary directories
$directories_to_create = [
    LOG_PATH,
    UPLOAD_PATH,
    dirname(LOG_PATH . '/app.log')
];

foreach ($directories_to_create as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Backwards compatibility functions - maintain existing function names
// These now delegate to the appropriate service classes

function loadEnv($filePath = '.env') {
    return EnvironmentService::load_env($filePath);
}

function env($key, $default = null) {
    return EnvironmentService::env($key, $default);
}

function getDB() {
    return DatabaseService::get_db();
}

function getSystemPrompt($promptKey = 'main') {
    return SystemService::get_system_prompt($promptKey);
}

function calculateCosineSimilarity($vector1, $vector2) {
    return VectorService::calculate_cosine_similarity($vector1, $vector2);
}

function retrieveDocumentsByVector($queryEmbedding, $limit = null) {
    return VectorService::retrieve_documents_by_vector($queryEmbedding, $limit);
}

// Utility function to validate configuration
function validate_config() {
    $missing = [];
    
    // Check critical configuration values (not just env vars, but actual constants)
    if (OPENAI_API_KEY === 'your-openai-api-key-here') {
        $missing[] = 'OPENAI_API_KEY (set to default placeholder)';
    }
    
    // Database settings are OK to use defaults for development
    // Only warn if they're still at defaults
    if (DEBUG_MODE) {
        if (DB_HOST === 'localhost' && DB_USER === 'root' && DB_PASSWORD === '' && DB_NAME === 'ai_assistant') {
            error_log("Using default database configuration. Consider creating a .env file for production.");
        }
        
        if (!empty($missing)) {
            error_log("Configuration warnings: " . implode(', ', $missing));
            // Don't throw exception, just log warnings
        }
    }
    
    return empty($missing);
}

// Initialize configuration validation
if (DEBUG_MODE) {
    validate_config();
}