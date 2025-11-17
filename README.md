# Chat with Ollama - RAG-Powered AI Assistant

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Ollama](https://img.shields.io/badge/Ollama-Powered-orange.svg)

**A modern, beautiful chat interface powered by Ollama with full RAG (Retrieval-Augmented Generation) capabilities. Upload documents and have intelligent, context-aware conversations with your local AI models.**

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [Architecture](#️-architecture) • [Contributing](#-contributing)

</div>

---
## Screenshots

<img width="1896" height="916" alt="Chatbot" src="https://github.com/user-attachments/assets/9b0f6916-2dfc-46c8-9e2a-832d8beb2f84" />
<img width="1893" height="914" alt="Documents Management" src="https://github.com/user-attachments/assets/52fb8db9-5d53-417a-b7d5-cc8063bb1bba" />
<img width="1898" height="909" alt="Settings" src="https://github.com/user-attachments/assets/9dfb929b-6491-410c-8ca5-038175957997" />

---
In the coding oven after quite a long time!
---

## ✨ Features

### 🎨 Modern UI/UX - Ollama Inspired Design
- **Glassmorphism Design**: Beautiful frosted glass effects with smooth animations
- **Ollama-Style Interface**: Clean, modern design matching Ollama's aesthetic
- **Iconify Integration**: 275,000+ icons from 200+ icon sets for a rich visual experience
- **Playful Processing Messages**: Animated, rotating messages while AI processes requests
- **Responsive Layout**: Works seamlessly on desktop and mobile devices
- **Smooth Animations**: Polished transitions and micro-interactions
- **Dark Theme**: Easy on the eyes with GitHub-inspired color scheme
- **Empty State Design**: Beautiful bot avatar placeholder with floating animation

### 🧠 RAG (Retrieval-Augmented Generation)
- **Multi-Format Support**: PDF, DOCX, TXT, XLSX, CSV, MD, PPT, DOC files
- **Intelligent Chunking**: Automatic text segmentation with overlap for context preservation
- **Vector Embeddings**: Uses Ollama's embedding models (nomic-embed-text) for semantic search
- **Context Retrieval**: Automatically finds relevant document chunks for each query
- **Context-Aware Responses**: AI responses are enhanced with relevant document context
- **Visual Recognition**: OCR support for image-based documents (Tesseract integration)
- **Chunk Visualization**: See how documents are processed and chunked

### 💬 Advanced Chat Features
- **Multiple Chat Sessions**: Create and manage multiple conversation threads
- **Session History**: Persistent chat history stored in SQLite database
- **Model Selection**: Choose from available Ollama models with auto-sync
- **RAG Toggle**: Enable/disable RAG on the fly with visual indicator
- **File Attachments**: Attach images and files to conversations
- **Message Formatting**: Markdown support with code highlighting
- **Real-time Updates**: Live status updates and notifications

### 📁 Document Management
- **Drag & Drop Upload**: Intuitive file upload interface
- **Document Library**: View all uploaded documents with status badges
- **Processing Status**: Real-time status updates (pending, processing, processed)
- **Chunk Information**: See how many chunks each document was split into
- **Document Deletion**: Remove documents when no longer needed
- **File Type Icons**: Beautiful iconify icons for each file type
- **Upload Progress**: Visual feedback during document processing

### 🔧 Technical Features
- **RESTful API**: Clean API endpoints for all operations
- **SQLite Database**: Lightweight, file-based database for portability
- **Composer Integration**: Modern PHP dependency management
- **Error Handling**: Comprehensive error handling and logging
- **Security**: Input validation and sanitization
- **Performance**: Optimized queries and efficient chunking

## 🚀 Quick Start

### Prerequisites

- **PHP 7.4+** with extensions: `zip`, `sqlite3`, `gd`, `curl`
- **Composer** for dependency management
- **Ollama** installed and running locally ([Download](https://ollama.ai))
- **Tesseract OCR** (optional, for image recognition)

### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/Chat-with-Ollama.git
   cd Chat-with-Ollama
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   
   Create a `.env` file in the project root:
   ```env
   OLLAMA_API_URL=http://localhost:11434/api/
   OLLAMA_JWT_TOKEN=
   ```
   
   > **Note**: For local Ollama instances, you can leave `OLLAMA_JWT_TOKEN` empty. Only set it if your Ollama instance requires authentication.

4. **Create Required Directories**
   ```bash
   mkdir -p data public/uploads
   chmod 755 data public/uploads
   ```

5. **Install Embedding Model** (Required for RAG)
   ```bash
   ollama pull nomic-embed-text
   ```

6. **Start Development Server**
   ```bash
   php -S localhost:8000 -t public
   ```

7. **Access the Application**
   
   Open your browser to:
   - `http://localhost:8000/public/index.php`
   - Or configure your web server to point to the `public/` directory

## 📖 Usage Guide

### Uploading Documents

1. Navigate to **Documents** page via sidebar or header icon
2. **Drag & drop** files onto the upload zone or **click to browse**
3. Supported formats: PDF, DOCX, TXT, XLSX, CSV, MD, PPT, DOC
4. Wait for processing - documents are automatically chunked and embedded
5. Once status shows "processed", documents are ready for RAG queries

### Using RAG (Retrieval-Augmented Generation)

1. **Enable RAG**: Ensure the brain icon (🧠) in the chat input is highlighted
2. **Upload Documents**: Add relevant documents to your library
3. **Ask Questions**: Type naturally - the AI automatically searches your documents
4. **Get Context-Aware Answers**: Responses include relevant information from your documents
5. **Toggle RAG**: Click the brain icon to enable/disable RAG mode

### Managing Chat Sessions

- **New Chat**: Click the "+" button in the sidebar to start fresh
- **Recent Chats**: Previous conversations appear in the sidebar
- **Session History**: Each session maintains its own context and history
- **Delete Sessions**: Remove old conversations to keep things organized

### Model Selection

- **Select Model**: Use the dropdown in the header to choose your Ollama model
- **Sync Models**: Click the sync icon to refresh available models from Ollama
- **Auto-Save**: Your model preference is saved automatically
- **Default Model**: Set your preferred model in Settings

### Document Processing

Documents go through these stages:
1. **Upload** → File is received and validated
2. **Parse** → Text is extracted based on file type
3. **Chunk** → Text is split into overlapping chunks (~1000 tokens)
4. **Embed** → Each chunk is converted to a vector embedding
5. **Store** → Chunks and embeddings saved to database
6. **Ready** → Document is available for RAG queries

## 🏗️ Architecture

### Project Structure

```
Chat-with-Ollama/
├── public/                 # Web-accessible files
│   ├── api/                # API endpoints
│   │   ├── chat.php        # Chat messages endpoint
│   │   ├── chat-session.php # Session management
│   │   ├── rag.php         # Document management
│   │   └── models.php       # Models list
│   ├── assets/             # Static assets
│   │   ├── css/            # Stylesheets
│   │   ├── js/             # JavaScript files
│   │   ├── libs/           # Third-party libraries
│   │   └── img/            # Images
│   ├── index.php           # Main chat interface
│   ├── documents.php       # Document management page
│   └── settings.php        # Settings page
├── src/                    # Application source code
│   ├── Controllers/        # Request handlers
│   │   ├── ChatController.php
│   │   ├── RAGController.php
│   │   └── ChatSessionController.php
│   ├── Services/           # Business logic
│   │   ├── DocumentService.php
│   │   ├── EmbeddingService.php
│   │   └── RAGService.php
│   ├── Database/           # Database layer
│   │   └── Database.php
│   ├── Models/             # Data models
│   └── config.php          # Configuration
├── data/                   # Application data
│   └── rag.db             # SQLite database
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables
└── composer.json           # PHP dependencies
```

### Backend Architecture

#### Controllers
- **ChatController**: Handles chat messages, integrates RAG, communicates with Ollama
- **RAGController**: Manages document upload, processing, and retrieval
- **ChatSessionController**: Manages chat sessions and message history

#### Services
- **DocumentService**: Parses various file formats (PDF, DOCX, XLSX, etc.)
- **EmbeddingService**: Generates vector embeddings using Ollama
- **RAGService**: Orchestrates RAG workflow (chunking, embedding, retrieval)

#### Database Schema

**documents**
- `id`, `original_filename`, `file_type`, `file_size`, `file_path`
- `status`, `chunk_count`, `created_at`, `updated_at`

**document_chunks**
- `id`, `document_id`, `chunk_index`, `content`, `created_at`

**embeddings**
- `id`, `chunk_id`, `embedding_vector`, `created_at`

**chat_sessions**
- `id`, `title`, `created_at`, `updated_at`

**chat_messages**
- `id`, `session_id`, `role`, `content`, `created_at`

### RAG Flow Diagram

```
User Query
    ↓
Embed Query (Ollama)
    ↓
Cosine Similarity Search
    ↓
Retrieve Top K Chunks
    ↓
Inject Context into Prompt
    ↓
Send to Ollama
    ↓
Return Enhanced Response
```

### API Endpoints

#### Chat API
- `POST /api/chat.php` - Send chat messages with RAG support
  - Body: `{message, model, use_rag, session_id, file?}`

#### Document API
- `POST /api/rag.php?action=list` - List all documents
- `POST /api/rag.php?action=upload` - Upload a document
- `POST /api/rag.php?action=delete` - Delete a document

#### Session API
- `POST /api/chat-session.php?action=list` - List chat sessions
- `POST /api/chat-session.php?action=create` - Create new session
- `POST /api/chat-session.php?action=get` - Get session messages
- `POST /api/chat-session.php?action=delete` - Delete session

#### Models API
- `GET /api/models.php` - Get available Ollama models

## 🎨 Customization

### Theme & Colors

Edit `public/assets/css/modern.css` to customize:

```css
:root {
    --dark-bg: #0d1117;              /* Background color */
    --dark-surface: #161b22;          /* Surface color */
    --accent: #58a6ff;                /* Accent color */
    --primary-gradient: linear-gradient(...); /* Gradient */
}
```

### RAG Parameters

**Chunking Settings** (`src/Services/DocumentService.php`):
- `chunkSize`: Size of text chunks (default: 1000 tokens)
- `overlap`: Overlap between chunks (default: 200 tokens)

**Retrieval Settings** (`src/Services/RAGService.php`):
- `retrieveRelevantChunks()` limit: Number of chunks to retrieve (default: 5)

### Icon Customization

Icons use Iconify - browse available icons at [iconify.design](https://iconify.design)

Edit `public/icon_helper.php` to customize icon mappings for:
- File types
- Bot avatars
- Actions
- Navigation items

## 🔧 Configuration

### Environment Variables

Create a `.env` file in the project root:

```env
# Ollama API Configuration
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=

# Optional: Force HTTPS
# FORCE_HTTPS=true
```

### Database Configuration

The application uses SQLite by default. The database file is located at:
- `data/rag.db`

To reset the database, simply delete this file (it will be recreated automatically).

### Server Configuration

#### Apache (.htaccess)
Ensure mod_rewrite is enabled and point DocumentRoot to `public/` directory.

#### Nginx
```nginx
server {
    root /path/to/Chat-with-Ollama/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

## 🐛 Troubleshooting

### Documents Not Processing

**Symptoms**: Documents stuck in "pending" or "processing" status

**Solutions**:
- Verify `nomic-embed-text` model is installed: `ollama pull nomic-embed-text`
- Check PHP error logs: `tail -f /var/log/php_errors.log`
- Verify file permissions: `chmod 755 data public/uploads`
- Check Ollama is running: `curl http://localhost:11434/api/tags`

### RAG Not Working

**Symptoms**: AI responses don't include document context

**Solutions**:
- Ensure RAG toggle is enabled (brain icon highlighted)
- Verify documents show "processed" status
- Check Ollama is accessible: `curl http://localhost:11434/api/tags`
- Verify embedding model: `ollama list | grep nomic-embed-text`
- Check database has embeddings: Inspect `data/rag.db`

### API Endpoints Returning 404

**Symptoms**: JavaScript console shows 404 errors for API calls

**Solutions**:
- Verify API files exist in `public/api/` directory
- Check web server DocumentRoot points to `public/` directory
- Verify `.htaccess` or Nginx config allows PHP execution
- Check file permissions on API files

### Database Issues

**Symptoms**: Errors about database connection or schema

**Solutions**:
- Delete `data/rag.db` to reset database (will be recreated)
- Ensure `data/` directory is writable: `chmod 755 data`
- Check SQLite extension is enabled: `php -m | grep sqlite3`

### CSS/JS Not Loading

**Symptoms**: Page loads but styles are missing

**Solutions**:
- Check `public/path_helper.php` is calculating correct paths
- Verify assets exist in `public/assets/` directory
- Clear browser cache (Ctrl+Shift+R)
- Check browser console for 404 errors
- Visit `http://your-domain/debug_assets.php` for diagnostics

## 📚 Documentation

### API Documentation

See `TEST_ENDPOINTS.md` for detailed API endpoint documentation and testing instructions.

### Development

- **Debug Mode**: Add `?debug=assets` to any URL to enable asset debugging
- **Error Logging**: Check PHP error logs for detailed error messages
- **Database Inspection**: Use SQLite browser to inspect `data/rag.db`

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Add comments for complex logic
- Update documentation for new features
- Test thoroughly before submitting PR

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- **[Ollama](https://ollama.ai/)** - For the amazing local AI inference platform
- **[Iconify](https://iconify.design/)** - For the beautiful icon library (275k+ icons)
- **[Bootstrap](https://getbootstrap.com/)** - For the responsive CSS framework
- **PHP Community** - For excellent libraries and tools
- **Open Source Contributors** - For inspiration and best practices

---

## 💼 Professional Services & Premium Solutions

### 🚀 2TInteractive - Your Development Partner

Looking for **custom development**, **premium solutions**, or **professional services**?

**2TInteractive** offers:

* **Custom Web Development** - Tailored solutions for your business needs
* **Premium AI Solutions** - Enterprise-grade AI integrations and RAG implementations
* **Chat Application Development** - Custom chat interfaces and messaging systems
* **Ollama Integration Services** - Professional Ollama setup and optimization
* **RAG System Development** - Advanced retrieval-augmented generation systems
* **Full-Stack Development** - Modern web applications with cutting-edge technologies
* **Consulting Services** - Expert guidance for your AI and development projects
* **Maintenance & Support** - Ongoing support and updates for your applications

**Visit us**: [https://2tinteractive.com](https://2tinteractive.com)

**Contact**: For inquiries about premium solutions, custom development, or professional services, please visit our website or reach out through our contact channels.

---

**Made with ❤️ by [Tarek Tarabichi](https://2tinteractive.com) | [2TInteractive](https://2tinteractive.com)**

_This application is open-source and free to use. For enterprise features, custom integrations, or professional support, consider our premium services._

---

## 📞 Support

* **Documentation** - Check this README and inline code comments
* **Issues** - Report bugs via GitHub Issues
* **Professional Support** - Contact [2TInteractive](https://2tinteractive.com) for premium support

## 🔄 Changelog

### Version 1.0.0 (Current)

* **NEW**: Complete UI overhaul with Ollama-inspired design
* **NEW**: Iconify integration with 275k+ icons
* **NEW**: Playful animated processing messages
* **NEW**: Multi-format document support (PDF, DOCX, TXT, XLSX, CSV, MD, PPT, DOC)
* **NEW**: OCR support for image recognition
* **NEW**: RESTful API endpoints
* **IMPROVED**: Better error handling and logging
* **IMPROVED**: Enhanced RAG performance
* **FIXED**: Asset loading issues
* **FIXED**: API endpoint paths
* **FIXED**: JSON input handling in controllers

---

**Author**: Tarek Tarabichi | **Company**: 2TInteractive | **Website**: [https://2tinteractive.com](https://2tinteractive.com)
