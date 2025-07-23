
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ai_assistant
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ai_assistant;

-- 1. Users table
CREATE TABLE IF NOT EXISTS users (
    id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    username       VARCHAR(255) NULL,
    password_hash  VARCHAR(255) NOT NULL,
    NAME           VARCHAR(255) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username),
    INDEX idx_users_username (username),
    INDEX idx_users_active (is_active)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Threads table
CREATE TABLE IF NOT EXISTS threads (
    id           VARCHAR(255) NOT NULL,
    title        VARCHAR(255) NULL,
    user_id      BIGINT(20) UNSIGNED NULL,
    full_name    VARCHAR(255) NULL,
    email        VARCHAR(255) NULL,
    phone_number VARCHAR(50) NULL,
    nationality  VARCHAR(100) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_threads_user_id (user_id),
    INDEX idx_threads_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Messages table
CREATE TABLE IF NOT EXISTS messages (
    id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    thread_id  VARCHAR(255) NOT NULL,
    ROLE       ENUM('user', 'assistant', 'system') NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_messages_thread_id (thread_id),
    INDEX idx_messages_created_at (created_at),
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Documents table
CREATE TABLE IF NOT EXISTS documents (
    id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    content    TEXT NOT NULL,
    metadata   JSON NULL,
    embedding  JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_documents_created_at (created_at),
    FULLTEXT idx_documents_content (content)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. System prompts table
CREATE TABLE IF NOT EXISTS system_prompts (
    id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    prompt_key     VARCHAR(255) NOT NULL,
    prompt_name    VARCHAR(255) NOT NULL,
    prompt_content TEXT NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_prompts_key (prompt_key),
    INDEX idx_prompts_active (is_active),
    INDEX idx_prompts_created_at (created_at)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
