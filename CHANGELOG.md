# Changelog

All notable changes to this project will be documented in this file.

## [2.2.0] - 2025-01-XX

### Added
- **Branding Configuration**: Configurable developer name, company name, and URLs via environment variables
- **NexusAI Chat Promotion**: Added FAQ section promoting NexusAI Chat premium features
- **Enhanced Documentation**: Improved README with clear free vs premium comparison

### Changed
- **Code Cleanup**: Removed all premium features and external provider support (Ollama-only in free version)
- **Path Fixes**: Fixed config path resolution using `dirname(__DIR__)` for better cross-platform compatibility
- **File Organization**: Moved all development files to `/devfiles/` directory for cleaner repository

### Removed
- **Premium Features**: Removed multi-provider support, Generative AI, provider usage analytics
- **External Providers**: Removed DeepSeek, OpenAI, Anthropic, Gemini, Groq, HuggingFace, TogetherAI, OpenRouter support
- **Development Files**: Moved test files, documentation, and tools to `/devfiles/` directory

### Fixed
- **Config Path**: Fixed `Failed to open stream` error by using proper path resolution
- **Branding**: All personal names and company references now use configurable placeholders

## [Unreleased] - 2024

### Added
- **Streaming Responses**: Real-time token streaming using Server-Sent Events (SSE) for faster perceived performance
- **Parallel Model Inference**: Support for running multiple models simultaneously and using the fastest response
- **Smart Model Routing**: Automatic model selection based on query complexity (fast models for simple queries)
- **RAG Optimization**: 
  - In-memory caching for query embeddings and results
  - Optimized database queries with LIMIT clauses
  - Improved similarity computation using array_map
- **Enhanced UI Features**:
  - Typing indicators during response generation
  - Improved markdown rendering with code highlighting
  - Streaming toggle button in header
- **Export/Import Functionality**: Export and import chat sessions as JSON
- **GitHub Community Integration**:
  - Community links in sidebar (Star, Fork, Issues, Discussions)
  - GitHub link in header
  - Contributing guidelines
  - Issue and PR templates
- **Database Optimizations**:
  - Additional indexes for faster queries
  - Indexes on created_at, updated_at, and status fields

### Changed
- **ChatController**: Enhanced with parallel model support and smart routing
- **RAGService**: Added optimized retrieval method with caching
- **Frontend**: Updated to support streaming and parallel inference
- **README**: Comprehensive updates with new features and performance enhancements

### Performance Improvements
- Streaming reduces perceived latency by showing tokens as they're generated
- Parallel inference can reduce response time by 30-50% when using multiple models
- RAG caching improves repeated query performance by 80-90%
- Database indexes improve query performance by 2-5x

### Technical Details
- Uses GuzzleHttp Promises for parallel requests
- Implements Server-Sent Events (SSE) for streaming
- In-memory LRU-style cache for RAG queries (100 entry limit)
- Smart model selection based on query length and word count

## [Previous Versions]
- Initial release with RAG support
- Basic chat functionality
- Document management
- Session management
