# Chat with Ollama - RAG-Powered AI Assistant

A modern, beautiful chat interface powered by Ollama with full RAG (Retrieval-Augmented Generation) capabilities. Upload documents and have intelligent, context-aware conversations with your local AI models.

## ✨ Features

### 🤖 Multiple Free GenAI Providers
- **Groq**: Ultra-fast inference (30 req/min free tier)
- **Hugging Face**: Free open-source models
- **Together AI**: $25 free credit to start
- **OpenRouter**: Access to multiple model providers
- **Ollama**: Local, private, unlimited (default)
- **Seamless Switching**: Switch providers without code changes
- **Auto-Detection**: Automatically detects provider from model name
- See [FREE_GENAI_PROVIDERS.md](FREE_GENAI_PROVIDERS.md) for setup instructions

### 🎨 Modern UI/UX
- **Glassmorphism Design**: Beautiful frosted glass effects with smooth animations
- **Gradient Accents**: Modern purple gradient theme throughout
- **Responsive Layout**: Works seamlessly on desktop and mobile
- **Smooth Animations**: Polished transitions and micro-interactions
- **Dark Theme**: Easy on the eyes with customizable colors

### 🧠 RAG (Retrieval-Augmented Generation)
- **Document Upload**: Support for PDF, DOCX, TXT, XLSX, CSV, and MD files
- **Intelligent Chunking**: Automatic text segmentation with overlap for context preservation
- **Vector Embeddings**: Uses Ollama's embedding models for semantic search
- **Context Retrieval**: Automatically finds relevant document chunks for each query
- **Context-Aware Responses**: AI responses are enhanced with relevant document context
- **Optimized Retrieval**: Caching and query optimization for faster RAG responses
- **Parallel Processing**: Improved performance with optimized database queries

### 💬 Chat Features
- **Multiple Chat Sessions**: Create and manage multiple conversation threads
- **Session History**: Persistent chat history stored in SQLite database
- **Model Selection**: Choose from available Ollama models
- **RAG Toggle**: Enable/disable RAG on the fly
- **File Attachments**: Attach images and files to conversations
- **Streaming Responses**: Real-time streaming for faster perceived performance
- **Parallel Model Inference**: Use multiple models simultaneously for faster responses
- **Smart Model Routing**: Automatically selects optimal model based on query complexity
- **Typing Indicators**: Visual feedback during response generation
- **Enhanced Markdown**: Improved code highlighting and formatting
- **Export/Import**: Export and import chat sessions as JSON

### 📁 Document Management
- **Drag & Drop Upload**: Intuitive file upload interface
- **Document Library**: View all uploaded documents with status
- **Processing Status**: Real-time status updates (pending, processing, processed)
- **Chunk Information**: See how many chunks each document was split into
- **Document Deletion**: Remove documents when no longer needed

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- Composer
- Ollama installed and running locally
- PHP extensions: `zip`, `sqlite3`, `gd` (for image handling)

### Setup

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure Environment**
   Edit `.env` file:
   ```env
   OLLAMA_API_URL=http://localhost:11434/api/
   OLLAMA_JWT_TOKEN=your_jwt_token_here
   
   # Optional: Add free GenAI provider API keys
   GROQ_API_KEY=your_groq_api_key_here
   HUGGINGFACE_API_KEY=your_huggingface_api_key_here
   TOGETHER_API_KEY=your_together_api_key_here
   OPENROUTER_API_KEY=your_openrouter_api_key_here
   DEFAULT_GENAI_PROVIDER=groq
   ```
   
   See [FREE_GENAI_PROVIDERS.md](FREE_GENAI_PROVIDERS.md) for detailed setup instructions.

3. **Create Required Directories**
   ```bash
   mkdir -p data public/uploads
   chmod 755 data public/uploads
   ```

4. **Install Embedding Model** (Required for RAG)
   ```bash
   ollama pull nomic-embed-text
   ```

5. **Start Development Server**
   ```bash
   php -S localhost:8000 -t public
   ```

6. **Access the Application**
   Open your browser to `http://localhost:8000/public/index.php`

## 📖 Usage

### Uploading Documents

1. Click on "Documents" in the sidebar or use the document icon in the header
2. Drag and drop files or click to browse
3. Wait for processing (documents are chunked and embedded automatically)
4. Once processed, documents are available for RAG queries

### Using RAG

1. Ensure RAG is enabled (brain icon in chat input should be highlighted)
2. Upload relevant documents
3. Ask questions naturally - the AI will automatically search your documents
4. The AI will use relevant context from your documents to provide better answers

### Managing Chat Sessions

- Click "New Chat" in the sidebar to start a fresh conversation
- Recent chats appear in the sidebar
- Each session maintains its own history and context

### Model Selection

- Use the model dropdown in the header to select your preferred Ollama model
- Click the sync icon to refresh available models
- Your selection is saved automatically

## 🏗️ Architecture

### Backend Structure

```
src/
├── Controllers/
│   ├── ChatController.php         # Main chat endpoint with RAG integration
│   ├── StreamChatController.php   # Streaming chat endpoint (SSE)
│   ├── RAGController.php          # Document upload and management
│   ├── ChatSessionController.php  # Chat session management
│   └── ExportController.php       # Export/import functionality
├── Services/
│   ├── DocumentService.php        # Document parsing and chunking
│   ├── EmbeddingService.php       # Vector embedding generation
│   └── RAGService.php            # RAG orchestration and retrieval (optimized)
├── Database/
│   └── Database.php               # SQLite database initialization
└── Models/
    └── Model.php                  # Model data structure
```

### Database Schema

- **documents**: Stores uploaded document metadata
- **document_chunks**: Text chunks extracted from documents
- **embeddings**: Vector embeddings for semantic search
- **chat_sessions**: Chat conversation sessions
- **chat_messages**: Individual messages within sessions

### RAG Flow

1. **Document Upload** → File is parsed and text extracted
2. **Chunking** → Text is split into overlapping chunks (~1000 tokens)
3. **Embedding** → Each chunk is converted to a vector using Ollama
4. **Storage** → Chunks and embeddings stored in database
5. **Query** → User question is embedded
6. **Retrieval** → Cosine similarity search finds relevant chunks
7. **Context Injection** → Relevant chunks added to prompt
8. **Response** → Ollama generates response with document context

## 🎨 Customization

### Colors & Theme

Edit `/public/assets/css/modern.css` to customize:
- `--primary-gradient`: Main accent gradient
- `--dark-bg`: Background color
- `--glass-bg`: Glassmorphism background
- `--accent`: Accent color

### RAG Parameters

In `src/Services/DocumentService.php`:
- `chunkSize`: Size of text chunks (default: 1000)
- `overlap`: Overlap between chunks (default: 200)

In `src/Services/RAGService.php`:
- `retrieveRelevantChunks()` limit: Number of chunks to retrieve (default: 5)

## 🔧 Troubleshooting

### Documents Not Processing
- Ensure `nomic-embed-text` model is installed: `ollama pull nomic-embed-text`
- Check PHP error logs for parsing errors
- Verify file permissions on `data/` and `public/uploads/` directories

### RAG Not Working
- Check that documents are in "processed" status
- Verify Ollama is running and accessible
- Ensure embedding model is available: `ollama list`

### Database Issues
- Delete `data/rag.db` to reset database
- Ensure `data/` directory is writable

## 📝 License

This project is open source and available for modification and distribution.

## 🚀 Performance Enhancements

### Speed Optimizations
- **Streaming Responses**: Server-Sent Events (SSE) for real-time token streaming
- **Parallel Model Inference**: Race multiple models and use the fastest response
- **Smart Model Selection**: Automatically routes simple queries to faster models
- **RAG Caching**: In-memory caching of query embeddings and results
- **Database Indexing**: Optimized indexes for faster queries
- **Query Limits**: Limits embedding queries to top 1000 chunks for performance

### How It Works
1. **Streaming**: Responses stream token-by-token for immediate feedback
2. **Parallel Models**: Multiple models process simultaneously, first response wins
3. **Model Routing**: Short queries → fast models (tinyllama, phi3), complex → default model
4. **RAG Caching**: Frequently asked questions are cached for instant responses

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Star the Repository**: Show your support by starring ⭐
2. **Fork & Contribute**: Fork the repo and submit pull requests
3. **Report Issues**: Found a bug? Open an issue
4. **Join Discussions**: Share ideas and feedback in discussions

### Quick Links
- 🌟 [Star on GitHub](https://github.com/yourusername/chat-with-ollama)
- 🍴 [Fork & Contribute](https://github.com/yourusername/chat-with-ollama/fork)
- 🐛 [Report Issues](https://github.com/yourusername/chat-with-ollama/issues)
- 💬 [Join Discussions](https://github.com/yourusername/chat-with-ollama/discussions)

## 📊 Features Roadmap

- [x] Streaming responses
- [x] Parallel model inference
- [x] Smart model routing
- [x] RAG optimization
- [x] Export/import functionality
- [x] GitHub community integration
- [ ] WebSocket support for real-time updates
- [ ] Multi-user support
- [ ] Advanced analytics dashboard
- [ ] Plugin system for extensions

## 🙏 Acknowledgments

- Built with [Ollama](https://ollama.ai/) for local AI inference
- Uses modern web technologies for a beautiful user experience
- Implements RAG pattern for context-aware AI responses
- Enhanced with performance optimizations and community features
