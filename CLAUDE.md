# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **PHP-based AI chatbot application** with **RAG (Retrieval-Augmented Generation)** capabilities that integrates with OpenAI's GPT models. The application supports document upload/processing, real-time streaming chat, and user authentication with conversation history.

## Development Setup

### Database Setup
- **Database**: MariaDB 10.6+ or MySQL 5.7+
- **Schema**: Import `database.sql` to create required tables
- **Auto-creation**: Database and tables are created automatically on first run if using default config

### Configuration
- **Main config**: `config.php` - Contains OpenAI API key, database credentials, and system settings
- **OpenAI API Key**: Update `OPENAI_API_KEY` constant in `config.php`
- **Database**: Update `DB_*` constants for your database connection
- **Debug mode**: Set `DEBUG_MODE` to false for production

### Required Dependencies
- PHP 7.4+ with curl extension
- MariaDB 10.6+ or MySQL 5.7+
- Optional: `pdftotext` system command for PDF processing
- Optional: Imagick PHP extension for advanced PDF handling

## Architecture Overview

### Core Components
```
index.php            # Main landing page (redirects based on login status)
config.php           # Central configuration and database functions
app/                 # Chatbot interface (public access)
├── index.php        # Main chat interface
└── api/             # Chatbot API endpoints
    ├── api.php      # Main chat API with streaming support
    └── get_thread_messages.php  # Thread message retrieval
admin/               # Admin interface (authentication required)
├── nav.php          # Shared navigation component
├── chat/            # Chat management
│   └── index.php    # Chat history browser
├── knowledge/       # Document management
│   ├── index.php    # Document list and management
│   ├── upload.php   # Document upload interface
│   └── api/         # Document API endpoints
│       ├── list_doc.php
│       └── upload_doc.php
└── prompt/          # System configuration
    └── index.php    # System prompts configuration
auth/                # Authentication system
├── login/index.php  # Login form
├── register/index.php # Registration form
└── api/             # Authentication API endpoints
    ├── login.php
    ├── logout.php
    └── register.php
```

### Database Schema
- **threads**: Chat sessions with user information
- **messages**: Individual chat messages linked to threads
- **documents**: RAG knowledge base with vector embeddings
- **system_prompts**: Configurable AI behavior prompts
- **users**: Authentication system for admin interface

### API Flow
1. **Chat Initiation**: Frontend sends message via EventSource to `app/api/api.php?action=stream`
2. **RAG Processing**: System retrieves relevant documents using vector similarity
3. **AI Response**: OpenAI API called with context (system prompt + user info + documents + history)
4. **Streaming**: Response streamed back via Server-Sent Events

## Key Development Patterns

### Database Operations
- Always use `getDB()` function from `config.php` for database connections
- All database operations use prepared statements via PDO
- Database connection includes automatic error handling and null returns on failure

### API Endpoints
- **Chatbot API**: Located in `app/api/` - handles streaming chat and thread management
- **Document API**: Located in `admin/knowledge/api/` - handles document upload, management, and embedding
- **Auth API**: Located in `auth/api/` - handles login, logout, and registration
- Follow the existing patterns for consistent request/response handling
- Use `action` parameter to route different operations
- Implement proper error handling with JSON responses

### Vector Operations
- Use `calculateCosineSimilarity()` function for embedding comparisons
- Document retrieval via `retrieveDocumentsByVector()` with configurable limits
- Embeddings stored as JSON in database with fallback to text search

### Frontend Patterns
- Real-time streaming using EventSource with progressive content updates
- Session storage for thread persistence across page reloads
- Responsive design using CSS custom properties and modern flexbox/grid

## Common Development Tasks

### Adding New API Endpoints
1. **For chatbot features**: Add to `app/api/api.php` switch statement
2. **For document features**: Add to files in `admin/knowledge/api/` directory
3. **For auth features**: Add to files in `auth/api/` directory
4. Implement request validation and processing
5. Return consistent JSON response format
6. Add error handling following existing patterns

### Modifying System Prompts
- Edit via `admin/prompt/prompt.php` interface or directly in `system_prompts` table
- Use `getSystemPrompt($key)` function to retrieve active prompts
- Prompts support metadata JSON field for additional configuration

### Document Processing
- PDF/TXT files processed via `admin/knowledge/api/upload_doc.php`
- Text chunked into ~500 token segments automatically
- Embeddings generated using OpenAI `text-embedding-3-small`
- Vector similarity search with configurable `TOP_K_DOCUMENTS` limit

### Authentication System
- Session-based authentication with user table
- **Security Note**: Currently uses MD5 hashing - should be upgraded to bcrypt
- Admin interface protected with session checks
- Login via `auth/login/index.php`, Registration via `auth/register/index.php`
- Logout via `auth/api/logout.php` with session destruction

## Configuration Constants

### OpenAI Settings
```php
OPENAI_API_KEY       # Your OpenAI API key
OPENAI_MODEL         # Default: gpt-4o-mini
OPENAI_EMBED_MODEL   # Default: text-embedding-3-small
MAX_TOKENS           # Default: 1000
TEMPERATURE          # Default: 0.3
TOP_K_DOCUMENTS      # Default: 5 (RAG document limit)
```

### Database Settings
```php
DB_HOST              # Default: localhost
DB_PORT              # Default: 3306
DB_NAME              # Default: ai_assistant
DB_USER              # Default: root
DB_PASSWORD          # Default: (empty)
```

## Security Considerations

### Current Security Measures
- Prepared statements for all SQL operations
- Session security with ID regeneration
- CORS headers properly configured
- Input validation on API endpoints

### Known Security Issues
- **Critical**: MD5 password hashing (should use bcrypt/Argon2)
- **Important**: API key hardcoded in config.php (should use environment variables)
- **Minor**: Basic file upload validation (could be enhanced)

## File Structure Notes

### Static Assets
- `assets/css/` - Bootstrap DataTables styling
- `assets/js/` - jQuery 3.7.0 and DataTables libraries
- Main styling is embedded in PHP files, not external CSS

### Authentication Flow
- **Root access**: `index.php` redirects based on login status
- **Public chatbot**: `app/index.php` (no login required)
- **Admin access**: `admin/knowledge/` (requires login via `auth/login/index.php`)
- **Admin sections**: Chat (`admin/chat/`), Knowledge (`admin/knowledge/`), Settings (`admin/prompt/`)
- **Registration**: `auth/register/index.php` for new admin accounts

### Thread Management
- Thread IDs generated as `thread_TIMESTAMP_RANDOM`
- Stored in sessionStorage for persistence
- URL parameter `?thread_id=` supported for direct thread access
- Automatic thread creation on first message