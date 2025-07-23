<?php
/**
 * System Service
 * Handles system configuration, prompts, and application settings
 */

require_once __DIR__ . '/database_service.php';

class SystemService {
    
    /**
     * Get active system prompt from database
     * 
     * @param string $prompt_key The prompt key to retrieve (default: 'main')
     * @return string The system prompt content
     */
    public static function get_system_prompt($prompt_key = 'main') {
        try {
            if (!DatabaseService::is_available()) {
                return self::get_default_system_prompt();
            }
            
            if (!DatabaseService::table_exists('system_prompts')) {
                error_log("get_system_prompt: system_prompts table does not exist");
                return self::get_default_system_prompt();
            }
            
            $stmt = DatabaseService::execute_query("
                SELECT prompt_content 
                FROM system_prompts 
                WHERE prompt_key LIKE :key 
                AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ", ['key' => $prompt_key . '%']);
            
            if (!$stmt) {
                return self::get_default_system_prompt();
            }
            
            $result = $stmt->fetch();
            
            return $result ? $result['prompt_content'] : self::get_default_system_prompt();
            
        } catch (Exception $e) {
            error_log("Error fetching system prompt: " . $e->getMessage());
            return self::get_default_system_prompt();
        }
    }
    
    /**
     * Get the default system prompt
     * 
     * @return string Default system prompt
     */
    public static function get_default_system_prompt() {
        return defined('DEFAULT_SYSTEM_PROMPT') ? DEFAULT_SYSTEM_PROMPT : 
            'You are an AI Assistant designed to help users with their questions and tasks. You provide comprehensive information and assistance on a wide range of topics. You help users by answering questions, providing explanations, and offering guidance. Always be professional, accurate, and helpful in your responses.';
    }
    
    /**
     * Save a new system prompt
     * 
     * @param string $prompt_key The prompt key
     * @param string $prompt_name The prompt name/title
     * @param string $prompt_content The prompt content
     * @param bool $is_active Whether this prompt should be active
     * @return bool Success status
     */
    public static function save_system_prompt($prompt_key, $prompt_name, $prompt_content, $is_active = true) {
        try {
            if (!DatabaseService::is_available()) {
                return false;
            }
            
            // Deactivate other prompts with the same base key if this one is active
            if ($is_active) {
                $base_key = explode('_', $prompt_key)[0];
                DatabaseService::execute_query("
                    UPDATE system_prompts 
                    SET is_active = 0 
                    WHERE prompt_key LIKE :key
                ", ['key' => $base_key . '%']);
            }
            
            // Insert new prompt
            $stmt = DatabaseService::execute_query("
                INSERT INTO system_prompts (prompt_key, prompt_name, prompt_content, is_active, created_at) 
                VALUES (:key, :name, :content, :active, NOW())
            ", [
                'key' => $prompt_key,
                'name' => $prompt_name,
                'content' => $prompt_content,
                'active' => $is_active ? 1 : 0
            ]);
            
            return $stmt !== false;
            
        } catch (Exception $e) {
            error_log("Error saving system prompt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all system prompts
     * 
     * @param string $base_key Optional base key to filter by
     * @return array Array of prompts
     */
    public static function get_all_system_prompts($base_key = null) {
        try {
            if (!DatabaseService::is_available()) {
                return [];
            }
            
            if (!DatabaseService::table_exists('system_prompts')) {
                return [];
            }
            
            $sql = "SELECT * FROM system_prompts";
            $params = [];
            
            if ($base_key) {
                $sql .= " WHERE prompt_key LIKE :key";
                $params['key'] = $base_key . '%';
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = DatabaseService::execute_query($sql, $params);
            
            if (!$stmt) {
                return [];
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error fetching all system prompts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a system prompt
     * 
     * @param string $prompt_key The prompt key to delete
     * @return bool Success status
     */
    public static function delete_system_prompt($prompt_key) {
        try {
            if (!DatabaseService::is_available()) {
                return false;
            }
            
            $stmt = DatabaseService::execute_query("
                DELETE FROM system_prompts WHERE prompt_key = :key
            ", ['key' => $prompt_key]);
            
            return $stmt !== false;
            
        } catch (Exception $e) {
            error_log("Error deleting system prompt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activate a specific system prompt
     * 
     * @param string $prompt_key The prompt key to activate
     * @return bool Success status
     */
    public static function activate_system_prompt($prompt_key) {
        try {
            if (!DatabaseService::is_available()) {
                return false;
            }
            
            // Deactivate all prompts with the same base key
            $base_key = explode('_', $prompt_key)[0];
            DatabaseService::execute_query("
                UPDATE system_prompts 
                SET is_active = 0 
                WHERE prompt_key LIKE :key
            ", ['key' => $base_key . '%']);
            
            // Activate the specified prompt
            $stmt = DatabaseService::execute_query("
                UPDATE system_prompts 
                SET is_active = 1 
                WHERE prompt_key = :key
            ", ['key' => $prompt_key]);
            
            return $stmt !== false;
            
        } catch (Exception $e) {
            error_log("Error activating system prompt: " . $e->getMessage());
            return false;
        }
    }
}

