<?php
/**
 * Environment Service
 * Handles environment variable loading and configuration management
 */

class EnvironmentService {
    private static $loaded = false;
    private static $env_vars = [];
    
    /**
     * Load environment variables from .env file
     * 
     * @param string $file_path Path to the .env file
     * @return bool Success status
     */
    public static function load_env($file_path = '.env') {
        if (self::$loaded) {
            return true; // Already loaded
        }
        
        // Make path absolute if relative
        if (!str_starts_with($file_path, '/') && !preg_match('/^[A-Z]:\\\\/i', $file_path)) {
            $file_path = dirname(__DIR__) . '/' . $file_path;
        }
        
        if (!file_exists($file_path)) {
            error_log("Environment file not found: " . $file_path);
            self::$loaded = true; // Mark as loaded even if file doesn't exist
            return false;
        }
        
        try {
            $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip comments and empty lines
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    
                    // Only set if not already defined
                    if (!array_key_exists($name, $_ENV) && !getenv($name)) {
                        putenv(sprintf('%s=%s', $name, $value));
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                        self::$env_vars[$name] = $value;
                    }
                }
            }
            
            self::$loaded = true;
            return true;
            
        } catch (Exception $e) {
            error_log("Error loading environment file: " . $e->getMessage());
            self::$loaded = true; // Mark as loaded to prevent repeated attempts
            return false;
        }
    }
    
    /**
     * Get environment variable with fallback
     * 
     * @param string $key The environment variable key
     * @param mixed $default Default value if not found
     * @return mixed The environment variable value or default
     */
    public static function env($key, $default = null) {
        // Try different sources in order of preference
        $value = getenv($key);
        
        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }
        
        if ($value === null) {
            $value = $_SERVER[$key] ?? null;
        }
        
        if ($value === null) {
            $value = self::$env_vars[$key] ?? null;
        }
        
        // Return default if still null or false
        if ($value === null || $value === false) {
            return $default;
        }
        
        // Convert string representations of boolean values
        if (is_string($value)) {
            $lower_value = strtolower($value);
            if (in_array($lower_value, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($lower_value, ['false', '0', 'no', 'off', ''])) {
                return false;
            }
        }
        
        return $value;
    }
    
    /**
     * Set environment variable
     * 
     * @param string $key The environment variable key
     * @param mixed $value The value to set
     * @return bool Success status
     */
    public static function set_env($key, $value) {
        try {
            $string_value = (string) $value;
            putenv(sprintf('%s=%s', $key, $string_value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $string_value;
            self::$env_vars[$key] = $value;
            return true;
        } catch (Exception $e) {
            error_log("Error setting environment variable: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if environment variable exists
     * 
     * @param string $key The environment variable key
     * @return bool True if exists
     */
    public static function has_env($key) {
        return getenv($key) !== false || 
               isset($_ENV[$key]) || 
               isset($_SERVER[$key]) || 
               isset(self::$env_vars[$key]);
    }
    
    /**
     * Get all loaded environment variables
     * 
     * @return array All environment variables
     */
    public static function get_all_env() {
        return array_merge($_ENV, self::$env_vars);
    }
    
    /**
     * Clear all loaded environment variables (for testing)
     * 
     * @return void
     */
    public static function clear_env() {
        self::$env_vars = [];
        self::$loaded = false;
    }
    
    /**
     * Validate required environment variables
     * 
     * @param array $required_vars Array of required variable names
     * @return array Array of missing variables
     */
    public static function validate_required_env($required_vars) {
        $missing = [];
        
        foreach ($required_vars as $var) {
            if (!self::has_env($var) || empty(self::env($var))) {
                $missing[] = $var;
            }
        }
        
        return $missing;
    }
    
    /**
     * Get configuration array for a specific prefix
     * 
     * @param string $prefix The environment variable prefix (e.g., 'DB_')
     * @return array Configuration array
     */
    public static function get_config_by_prefix($prefix) {
        $config = [];
        $all_env = self::get_all_env();
        
        foreach ($all_env as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $config_key = strtolower(substr($key, strlen($prefix)));
                $config[$config_key] = $value;
            }
        }
        
        return $config;
    }
}

