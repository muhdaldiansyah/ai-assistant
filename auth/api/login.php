<?php
// Start the session at the absolute beginning of the script.
session_start();

// Set the content type to JSON for all responses.
header('Content-Type: application/json');

// Include the central configuration file
require_once __DIR__ . '/../../config.php';

// Main execution block with error handling
try {

    // --- 1. Request Validation ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Method not allowed.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid JSON data sent.']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required.']);
        exit;
    }
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username must be at least 3 characters.']);
        exit;
    }

    // --- 2. Database Connection ---
    $pdo = getDB();
    if ($pdo === null) {
        throw new Exception("Database connection failed.");
    }

    // --- 3. Fetch User and Verify ---
    $stmt = $pdo->prepare(
        "SELECT id, username, password_hash, name, is_active, email_verified
         FROM users 
         WHERE LOWER(username) = LOWER(:username) 
         LIMIT 1"
    );
    $stmt->execute(['username' => $username]);
    $userRow = $stmt->fetch();

    // Verify user existence and password
    if (!$userRow || md5($password) !== $userRow['password_hash']) {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Invalid username or password.']);
        exit;
    }

    // --- 4. Post-Authentication Checks ---
    if (!$userRow['is_active']) {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'This account has been deactivated.']);
        exit;
    }

    // --- 5. Create Session on Success ---
    session_regenerate_id(true); // Security: prevent session fixation
    $_SESSION['user_id'] = $userRow['id'];
    $_SESSION['username'] = $userRow['username'];
    $_SESSION['user_name'] = $userRow['name'];
    $_SESSION['login_time'] = time();

    // --- 6. Success Response ---
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'redirect' => '../../admin/knowledge/'
    ]);

} catch (PDOException $e) {
    // Log the detailed database error for the developer
    error_log("Database Error: " . $e->getMessage());
    // Return a generic error to the user
    http_response_code(500);
    echo json_encode(['error' => 'A server-side database error occurred.']);
    exit;
} catch (Exception $e) {
    // Catch any other errors (like missing .env file)
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected server error occurred.']);
    exit;
}
