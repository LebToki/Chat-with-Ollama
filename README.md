# Chat with Ollama - Open-Source Edition

A modern, RAG-powered AI chat application that integrates with Ollama for local AI inference. Chat with various Ollama models while leveraging your own documents for context-aware, intelligent responses.

**🆓 Free & Open Source** - Perfect for individuals and developers who want privacy-first AI chat with local models.

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active-success)
![Provider](https://img.shields.io/badge/Provider-Ollama%20Only-orange)
![Version](https://img.shields.io/badge/version-2.2.0-blue)

> **💡 Looking for Multi-Provider Support?**  
> Check out **[NexusAI Chat](https://2tinteractive.com/solutions/NexusAI)** 
Our premium enterprise solution with support for Ollama, DeepSeek, OpenAI, Anthropic, and much more! 
Visit [2tinteractive.com](https://2tinteractive.com) to learn more.


## Features

### 🤖 AI Chat Interface
- **Multiple Model Support**: Chat with any Ollama model installed on your server
- **Session Management**: Organize conversations into separate chat sessions
- **Rename Sessions**: Customize chat session names for better organization
- **Markdown Rendering**: Beautifully formatted responses with code blocks, lists, and structured content
- **Real-time Processing**: Visual feedback during AI processing with rotating messages

### 📄 Document Management & RAG
- **Document Upload**: Support for PDF, DOCX, TXT, Markdown (MD), PPT, PPTX, XLSX, and CSV files
- **Automatic Processing**: Documents are automatically chunked and embedded for RAG
- **Document-Specific Chats**: Create focused chat sessions linked to specific documents
- **@ Mentions**: Reference specific documents in any chat using `@documentId`
- **Inline Preview**: View document chunks and metadata directly in the interface
- **Status Tracking**: Real-time status updates (pending, processing, processed, error)

### 🔍 Smart Search & Filtering
- **Full-Text Search**: Fast search across document names, types, and content
- **Advanced Filtering**: Filter by status, file type, and more
- **Sortable Columns**: Click column headers to sort documents
- **Indexed Search**: Optimized search performance with built-in indexing

### ⚙️ Settings & Customization
- **Model Management**: Sync and manage Ollama models from the settings page
- **Timezone Support**: Select from all IANA timezones for accurate date/time display
- **Date/Time Formats**: Customize date (Short, Medium, Long, Full) and time (Short, Medium) formats
- **RAG Toggle**: Enable/disable RAG on a per-message basis
- **Default Settings**: Save your preferences for model, RAG, and date/time formats

### 🎨 Modern UI/UX
- **Dark Theme**: Beautiful dark mode interface with glassmorphism effects
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Accessibility**: ARIA labels, keyboard navigation, and focus indicators
- **WowDash-Inspired**: Modern UI patterns with circular action buttons and glass cards
- **Icon System**: Comprehensive icon system using Iconify with CDN fallbacks

## Screenshots

<img width="1896" height="916" alt="Chatbot" src="https://github.com/user-attachments/assets/9b0f6916-2dfc-46c8-9e2a-832d8beb2f84" />

<img width="1647" height="910" alt="Documents Management" src="https://github.com/user-attachments/assets/06822734-51fd-411b-bc05-372d8922b273" />

<img width="1893" height="857" alt="Document Based Chats" src="https://github.com/user-attachments/assets/06a3c37f-9b3d-4c25-b2cb-2f2b1218be45" />

<img width="1903" height="910" alt="Chat With a Document" src="https://github.com/user-attachments/assets/355fde05-3d11-4d2b-b5b8-379a8d934e9d" />

<img width="1898" height="909" alt="Settings" src="https://github.com/user-attachments/assets/9dfb929b-6491-410c-8ca5-038175957997" />

<img width="1892" height="865" alt="FAQ" src="https://github.com/user-attachments/assets/2ccbe6bf-707a-4960-abc3-fad47f5b5cc8" />


## Requirements

- **PHP**: 8.1 or higher
- **Ollama**: Installed and running locally or remotely
- **Extensions**: 
  - PDO with SQLite
  - cURL
  - ZIP (for DOCX/PPTX support)
  - GD or ImageMagick (optional, for image processing)
- **Composer**: For dependency management
- **Web Server**: Apache/Nginx with mod_rewrite (or equivalent)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/{{GITHUB_USERNAME}}/{{GITHUB_REPO}}.git
cd chat-with-ollama
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the `.env.example` file to `.env` and configure your settings:

```bash
cp .env.example .env
```

Edit `.env` with your Ollama configuration:

```env
OLLAMA_API_URL=http://localhost:11434/api
OLLAMA_JWT_TOKEN=your_jwt_token_if_needed
```

### 4. Set Up Database

The database will be automatically created on first run. Ensure the `data/` directory is writable:

```bash
mkdir -p data
chmod 755 data
```

### 5. Install Ollama Embedding Model

For RAG functionality, install the embedding model:

```bash
ollama pull nomic-embed-text
```

### 6. Configure Web Server

#### Apache

Ensure mod_rewrite is enabled and add to your `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Usage

### Starting a Chat

1. Navigate to the application in your browser
2. Select your preferred Ollama model from the header dropdown
3. Type your message in the chat input
4. Enable RAG (brain icon) to use document context
5. Press Enter or click Send

### Uploading Documents

1. Go to the Documents page
2. Click "Upload Document"
3. Select your file (PDF, DOCX, TXT, etc.)
4. Wait for processing to complete (status will update automatically)
5. Once processed, documents are ready for RAG queries

### Creating Document-Specific Chats

1. Navigate to the Documents page
2. Click the chat icon (💬) on any document
3. A new chat session is created linked to that document
4. All messages in that session will automatically use only that document's chunks

### Using @ Mentions

1. In any chat, type `@` to see a dropdown of available documents
2. Select a document to mention it
3. Your message will include `@documentId`
4. RAG will only search chunks from mentioned documents

### Managing Settings

1. Go to Settings page
2. **Model Configuration**: Sync models, set default model, enable/disable RAG by default
3. **Date & Time Format**: Select timezone, date format, and time format
4. Settings are saved to browser localStorage

## RAG Workflow

The Retrieval-Augmented Generation (RAG) system works as follows:

1. **Document Upload**: Upload your document (PDF, DOCX, etc.)
2. **Text Extraction**: Content is extracted from the file
3. **Chunking**: Text is split into overlapping chunks (1000 chars with 200 char overlap)
4. **Embedding**: Each chunk is converted to vector embeddings using `nomic-embed-text`
5. **Storage**: Embeddings are stored in SQLite database
6. **Query Processing**: When you ask a question:
   - Your question is converted to an embedding
   - Similar chunks are found using cosine similarity
   - Top 5 most relevant chunks are included as context
   - The model generates a response using this context

## API Endpoints

### Chat API
- `POST /api/chat.php` - Send a message and get AI response

### Chat Sessions API
- `POST /api/chat-session.php?action=create` - Create a new chat session
- `POST /api/chat-session.php?action=list` - List all chat sessions
- `POST /api/chat-session.php?action=get` - Get messages for a session
- `POST /api/chat-session.php?action=delete` - Delete a chat session
- `POST /api/chat-session.php?action=rename` - Rename a chat session
- `POST /api/chat-session.php?action=cleanup_empty` - Delete empty sessions

### RAG API
- `POST /api/rag.php?action=upload` - Upload a document
- `POST /api/rag.php?action=list` - List all documents
- `POST /api/rag.php?action=delete` - Delete a document
- `POST /api/rag.php?action=preview` - Get document preview with chunks
- `POST /api/rag.php?action=list_for_mentions` - Get processed documents for @ mentions

### Models API
- `GET /api/models.php` - Get list of available Ollama models

## Project Structure

```
chat-with-ollama/
├── public/                 # Public web root
│   ├── api/               # API endpoints
│   ├── assets/            # CSS, JS, images
│   ├── index.php          # Main chat interface
│   ├── documents.php      # Document management
│   ├── settings.php       # Settings page
│   └── help.php           # Help & documentation
├── src/
│   ├── Controllers/       # Request controllers
│   ├── Services/          # Business logic
│   ├── Database/          # Database layer
│   └── Http/              # HTTP utilities
├── data/                  # SQLite database (auto-created)
├── uploads/               # Uploaded documents (auto-created)
├── vendor/                # Composer dependencies
└── .env                   # Environment configuration
```

## Technologies Used

- **Backend**: PHP 8.1+, SQLite
- **Frontend**: Vanilla JavaScript, Axios, Bootstrap 5
- **AI**: Ollama (local AI inference)
- **Embeddings**: nomic-embed-text model
- **Icons**: Iconify (with multi-CDN fallback)
- **Styling**: Custom CSS with CSS Variables, Glassmorphism

## Troubleshooting

### Document Processing Issues

- **Status stuck on "processing"**: Check if Ollama is running and `nomic-embed-text` model is installed
- **Status shows "error"**: Check server error logs for details
- **No chunks generated**: Document may be empty or contain only images

### RAG Not Working

- Ensure RAG toggle is enabled (brain icon in chat input)
- Make sure you have at least one processed document
- Verify `nomic-embed-text` model is installed: `ollama pull nomic-embed-text`
- Check Ollama API connection in Settings

### Model Loading Errors

- If you see "model loading" errors, the system will automatically retry up to 5 times
- Wait a few moments and try again if the model is still loading
- Check Ollama server status and ensure the model is available

### Iconify CDN Issues

- The application includes automatic CDN fallback (jsDelivr, unpkg, cdnjs)
- If all CDNs fail, a custom fallback element is used
- Check browser console for CDN status messages

## Development

### Running Tests

```bash
# Test Ollama connectivity
php public/ollama_tester.php

# Test RAG workflow
php devfiles/tests/test_rag_workflow.php

# Test document processing
php devfiles/tests/test_rag_simple.php
```

### Code Style

The project follows PSR-12 coding standards.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License.

## Free vs Premium

**Chat with Ollama (Free Edition)** - This repository
- ✅ Ollama integration (local AI)
- ✅ RAG with document support
- ✅ Session management
- ✅ Open source (MIT License)
- ✅ Community support

**NexusAI Chat (Premium)** - Enterprise solution
- ✅ All free features, plus:
- ✅ Multi-provider support (DeepSeek, OpenAI, Anthropic, and more)
- ✅ Team collaboration
- ✅ Advanced analytics
- ✅ Enterprise features
- ✅ Priority support

Visit [2tinteractive.com](https://2tinteractive.com) to learn more about NexusAI Chat.

## Credits

Developed by **{{DEVELOPER_NAME}}** from [{{COMPANY_NAME}}]({{COMPANY_URL}})

Professional web development, AI integrations, and premium solutions for your business needs.

> **Note**: Replace `{{DEVELOPER_NAME}}`, `{{COMPANY_NAME}}`, and `{{COMPANY_URL}}` with your actual branding in the `.env` file or `src/config.php`.  
> For example: `DEVELOPER_NAME=Your Name`, `COMPANY_NAME=Your Company`, `COMPANY_URL=https://yourcompany.com`

## Support

For issues, questions, or feature requests, please open an issue on GitHub or visit the Help & Docs page in the application.

---

**Made with ❤️ by {{COMPANY_NAME}}**
