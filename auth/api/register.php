<?php
/**
 * api/register.php
 * ------------------------------------------------------------------
 * Registration endpoint for MariaDB 10.6 - standalone without vendor dependencies
 */

// Include the central configuration file
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

// Input validation
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

if (strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Get database connection
    $pdo = getDB();
    if ($pdo === null) {
        throw new Exception("Database connection failed.");
    }
    
    // Check if users table exists, create if not
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Create users table
        $pdo->exec("
            CREATE TABLE users (
                id             BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
                username       VARCHAR(255)    NULL,
                password_hash  VARCHAR(255)    NOT NULL,
                name           VARCHAR(255)    NULL,
                is_active      TINYINT(1)      NOT NULL DEFAULT 1,
                email_verified TINYINT(1)      NOT NULL DEFAULT 0,
                created_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                updated_at     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
                                              ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_users_username (username),
                INDEX idx_users_username (username),
                INDEX idx_users_active (is_active)
            ) ENGINE=INNODB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    // Check if username already exists
    $checkStmt = $pdo->prepare(
        "SELECT id FROM users WHERE LOWER(username) = LOWER(:username) LIMIT 1"
    );
    $checkStmt->execute(['username' => $username]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'An account with this username already exists.']);
        exit;
    }
    
    // Hash password using MD5
    $hashedPassword = md5($password);
    
    // Insert new user
    $insertStmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, is_active, email_verified, created_at, updated_at) 
         VALUES (:username, :password_hash, 1, 1, NOW(), NOW())"
    );
    
    $insertStmt->execute([
        'username' => $username,
        'password_hash' => $hashedPassword
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please login.',
        'user_id' => $userId
    ]);
    
} catch (PDOException $e) {
    error_log("Database error during registration: " . $e->getMessage());
    
    if ($e->getCode() == '23000') { // Duplicate entry error
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'An account with this username already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'A database error occurred during registration.']);
    }
} catch (Exception $e) {
    error_log("Unexpected error during registration: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred.']);
}
