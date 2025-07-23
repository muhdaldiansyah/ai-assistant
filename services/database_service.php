<?php
/**
 * Database Service
 * Handles all database connection and management operations
 */

class DatabaseService {
    private static $instance = null;
    private static $pdo = null;
    private static $db_available = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * Replaces the global getDB() function
     */
    public static function get_db() {
        if (self::$db_available === false) {
            return null; // Skip if we already know DB is not available
        }
        
        if (self::$pdo === null) {
            self::create_database_if_needed();
            self::establish_connection();
        }
        
        return self::$pdo;
    }
    
    /**
     * Create database if it doesn't exist (for default configuration)
     */
    private static function create_database_if_needed() {
        // Check if database is configured with defaults
        if (DB_HOST === 'localhost' && DB_USER === 'root' && DB_PASSWORD === '' && DB_NAME === 'ai_assistant') {
            try {
                $temp_pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASSWORD);
                $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $temp_pdo = null;
            } catch (PDOException $e) {
                error_log("Database creation check failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Establish database connection
     */
    private static function establish_connection() {
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        
        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            self::$db_available = true;
        } catch (PDOException $e) {
            self::$db_available = false;
            error_log("Database connection failed: " . $e->getMessage());
            self::$pdo = null;
        }
    }
    
    /**
     * Check if database is available
     */
    public static function is_available() {
        self::get_db(); // Trigger connection attempt
        return self::$db_available === true;
    }
    
    /**
     * Execute a prepared statement with error handling
     */
    public static function execute_query($sql, $params = []) {
        try {
            $pdo = self::get_db();
            if (!$pdo) {
                return false;
            }
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result ? $stmt : false;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a table exists
     */
    public static function table_exists($table_name) {
        try {
            $pdo = self::get_db();
            if (!$pdo) {
                return false;
            }
            
            $stmt = $pdo->query("SELECT 1 FROM {$table_name} LIMIT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

