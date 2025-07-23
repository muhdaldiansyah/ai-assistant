# AI Assistant - RAG-Enabled Chatbot

A powerful PHP-based AI chatbot application with **Retrieval-Augmented Generation (RAG)** capabilities, real-time streaming responses, and comprehensive document management. Built with OpenAI's GPT models and featuring a modern, responsive web interface.

## 🚀 Features

### 🤖 **AI Chat System**
- **Real-time streaming responses** from OpenAI GPT models
- **RAG (Retrieval-Augmented Generation)** with document-based context
- **Conversation threading** with persistent chat history
- **Mobile-responsive** chat interface
- **Guest access** - no registration required for chatting

### 📚 **Knowledge Management**
- **Document upload** (PDF, TXT files)
- **Automatic text chunking** and vector embedding generation
- **Intelligent document search** using cosine similarity
- **Document editing** and management interface
- **Vector similarity search** for contextual responses

### 👥 **User Management**
- **Session-based authentication** for admin access
- **User registration and login** system
- **Guest chat tracking** with user information collection
- **Chat history** browsing and management

### ⚙️ **Administration Panel**
- **System prompt configuration** with version history
- **Document library management** with CRUD operations
- **Chat history browser** with detailed conversation views
- **Real-time statistics** and analytics

## 🏗️ Architecture

### **Directory Structure**
```
ai-assistant/
├── index.php                    # Main landing page
├── config.php                   # Central configuration
├── database.sql                 # Database schema
│
├── app/                         # Public Chatbot Interface
│   ├── index.php                # Main chat interface
│   └── api/
│       ├── api.php              # Chat API with streaming
│       └── get_thread_messages.php
│
├── admin/                       # Admin Interface (Login Required)
│   ├── nav.php                  # Shared navigation
│   ├── chat/index.php           # Chat history browser
│   ├── knowledge/               # Document management
│   │   ├── index.php            # Document list
│   │   ├── upload.php           # Upload interface
│   │   └── api/
│   │       ├── list_doc.php     # Document CRUD API
│   │       └── upload_doc.php   # Document processing API
│   └── prompt/index.php         # System prompt configuration
│
├── auth/                        # Authentication System
│   ├── login/index.php          # Login page
│   ├── register/index.php       # Registration page
│   └── api/                     # Auth API endpoints
│
└── assets/                      # Static Resources
    ├── css/                     # Stylesheets
    └── js/                      # JavaScript libraries
```

### **Technology Stack**
- **Backend**: PHP 7.4+ with PDO
- **Database**: MariaDB 10.6+ / MySQL 5.7+
- **AI Integration**: OpenAI GPT-4 and text-embedding-3-small
- **Frontend**: Vanilla JavaScript with jQuery
- **UI Framework**: Custom CSS with Bootstrap DataTables
- **Real-time**: Server-Sent Events (EventSource) for streaming

## 📋 Requirements

### **System Requirements**
- PHP 7.4 or higher
- MariaDB 10.6+ or MySQL 5.7+
- Web server (Apache/Nginx)
- curl PHP extension
- 512MB+ RAM recommended

### **Optional Dependencies**
- `pdftotext` system command (for PDF processing)
- Imagick PHP extension (for advanced PDF handling)
- SSL certificate (recommended for production)

### **API Requirements**
- OpenAI API key with GPT-4 and embeddings access
- Sufficient OpenAI API credits for usage

## 🛠️ Installation

### **1. Clone Repository**
```bash
git clone <repository-url>
cd ai-assistant
```

### **2. Database Setup**
```sql
-- Create database
CREATE DATABASE ai_assistant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p ai_assistant < database.sql
```

### **3. Configuration**
Edit `config.php` with your settings:

```php
// OpenAI Configuration
define('OPENAI_API_KEY', 'your-openai-api-key-here');
define('OPENAI_MODEL', 'gpt-4o-mini');
define('OPENAI_EMBED_MODEL', 'text-embedding-3-small');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ai_assistant');
define('DB_USER', 'your-db-user');
define('DB_PASSWORD', 'your-db-password');

// System Settings
define('MAX_TOKENS', 1000);
define('TEMPERATURE', 0.3);
define('TOP_K_DOCUMENTS', 5);
```

### **4. Web Server Setup**

#### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ $1/index.php [L]
```

#### **Nginx**
```nginx
location / {
    try_files $uri $uri/ $uri/index.php;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### **5. Permissions**
```bash
# Make sure web server can write to necessary directories
chmod 755 ai-assistant/
chmod -R 644 ai-assistant/*
chmod -R 755 ai-assistant/assets/
```

## 🎯 Usage

### **Public Chat Access**
1. Visit your domain: `https://yourdomain.com/ai-assistant/`
2. Start chatting immediately - no registration required
3. The system will ask for basic information (name, email) on first use
4. Chat history is maintained per session

### **Admin Access**
1. Create admin account: `https://yourdomain.com/ai-assistant/auth/register/`
2. Login: `https://yourdomain.com/ai-assistant/auth/login/`
3. Admin dashboard redirects to document management

### **Document Management**
1. **Upload Documents**: Go to Admin → Documents → Upload
2. **Supported Formats**: PDF, TXT files or paste text directly
3. **Auto-Processing**: Documents are automatically chunked and embedded
4. **RAG Integration**: Uploaded documents enhance AI responses with context

### **System Configuration**
1. **Prompts**: Admin → Settings to configure AI behavior
2. **History**: View and restore previous prompt versions
3. **Monitoring**: Chat history and document statistics

## 🔧 API Reference

### **Chat API**
```javascript
// Streaming chat with RAG
const eventSource = new EventSource(
  `app/api/api.php?action=stream&thread_id=${threadId}&message=${message}&user_info=${userInfo}`
);

eventSource.addEventListener('chunk', (e) => {
    // Handle streaming response chunks
    console.log(e.data);
});
```

### **Document API**
```javascript
// Upload document
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('admin/knowledge/api/upload_doc.php', {
    method: 'POST',
    body: formData
});
```

### **Thread Management**
```javascript
// Get thread messages
fetch(`app/api/get_thread_messages.php?thread_id=${threadId}`)
    .then(response => response.json())
    .then(data => console.log(data.messages));
```

## 🔒 Security Features

### **Current Security Measures**
- ✅ **SQL Injection Protection**: Prepared statements throughout
- ✅ **Session Security**: Session ID regeneration on login
- ✅ **CORS Configuration**: Proper headers for API access
- ✅ **Input Validation**: Server-side validation on all inputs
- ✅ **Error Handling**: Graceful error handling with logging

### **Security Recommendations**
- 🔴 **Critical**: Upgrade password hashing from MD5 to bcrypt/Argon2
- 🟡 **Important**: Move API keys to environment variables
- 🟡 **Recommended**: Implement rate limiting for API endpoints
- 🟡 **Recommended**: Add file type validation for uploads
- 🟡 **Recommended**: Enable HTTPS in production

## 🚀 Deployment

### **Production Checklist**
- [ ] Set `DEBUG_MODE = false` in config.php
- [ ] Enable HTTPS/SSL
- [ ] Configure proper backup strategy
- [ ] Set up monitoring and logging
- [ ] Configure proper file permissions
- [ ] Set up database connection pooling
- [ ] Configure reverse proxy (if using Nginx)

### **Environment Variables** (Recommended)
```bash
# .env file
OPENAI_API_KEY=your-openai-api-key
DB_HOST=localhost
DB_NAME=ai_assistant
DB_USER=your-db-user
DB_PASSWORD=your-secure-password
```

### **Performance Optimization**
- Enable PHP OPcache
- Configure MySQL query cache
- Use CDN for static assets
- Implement Redis for session storage
- Enable gzip compression

## 🧪 Development

### **Local Development Setup**
```bash
# Using Laragon (Windows)
1. Install Laragon
2. Place project in www/ai-assistant/
3. Start Apache + MySQL
4. Access via http://ai-assistant.test/

# Using XAMPP
1. Place in htdocs/ai-assistant/
2. Access via http://localhost/ai-assistant/
```

### **Development Commands**
```bash
# Database reset
mysql -u root -p ai_assistant < database.sql

# Check PHP syntax
find . -name "*.php" -exec php -l {} \;

# View error logs
tail -f /var/log/apache2/error.log
```

### **Adding New Features**
1. **Chatbot Features**: Extend `app/api/api.php`
2. **Document Features**: Add to `admin/knowledge/api/`
3. **Auth Features**: Extend `auth/api/`
4. **UI Components**: Follow existing patterns in admin pages

## 📊 Database Schema

### **Key Tables**
- **`threads`**: Chat sessions with user information
- **`messages`**: Individual chat messages
- **`documents`**: Document chunks with vector embeddings
- **`system_prompts`**: Configurable AI prompts with versioning
- **`users`**: Admin user accounts

### **Relationships**
```
threads (1) ──→ (∞) messages
documents (embedding vectors for RAG)
system_prompts (versioned AI configuration)
users (admin authentication)
```

## 🐛 Troubleshooting

### **Common Issues**

#### **"Database connection failed"**
- Check database credentials in config.php
- Ensure MariaDB/MySQL is running
- Verify database exists and has proper permissions

#### **"OpenAI API error"**
- Verify API key is valid and has credits
- Check internet connectivity
- Ensure API key has access to required models

#### **"PDF processing failed"**
- Install pdftotext: `sudo apt-get install poppler-utils`
- Or enable Imagick PHP extension
- Check file permissions and upload limits

#### **"Streaming not working"**
- Verify Server-Sent Events support
- Check if proxy/firewall blocks streaming
- Test with different browsers

## 📈 Monitoring & Analytics

### **Built-in Statistics**
- Chat thread counts and activity
- Document library size and usage
- User engagement metrics
- System prompt change history

### **Log Files**
- PHP error logs for debugging
- Database query logs
- OpenAI API usage logs
- User activity logs

## 🤝 Contributing

### **Development Guidelines**
1. Follow existing code style and patterns
2. Use prepared statements for all database queries
3. Implement proper error handling
4. Add comments for complex logic
5. Test on multiple PHP versions

### **Reporting Issues**
Please include:
- PHP version and environment details
- Database version and configuration
- Complete error messages and logs
- Steps to reproduce the issue

## 📝 License

This project is open source. Please check the license file for specific terms and conditions.

## 🆘 Support

### **Documentation**
- See `CLAUDE.md` for detailed development guidance
- Check inline code comments for specific functionality
- Review database schema in `database.sql`

### **Getting Help**
1. Check troubleshooting section above
2. Review error logs for specific issues
3. Test with minimal configuration
4. Create detailed issue reports with logs

---

**Version**: 1.0  
**Last Updated**: 2025  
**Minimum PHP**: 7.4  
**Recommended PHP**: 8.1+