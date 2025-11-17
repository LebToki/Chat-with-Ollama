# Free GenAI Providers Integration

This application now supports multiple free GenAI providers in addition to Ollama. You can switch between providers seamlessly without changing your code.

## Supported Free Providers

### 1. **Groq** ⚡ (Recommended - Fastest)
- **Free Tier**: 30 requests/minute, 14,400 requests/day
- **Speed**: Extremely fast inference (often < 1 second)
- **Models**: 
  - `llama-3.1-8b-instant` (fastest)
  - `llama-3.1-70b-versatile` (most capable)
  - `mixtral-8x7b-32768`
  - `gemma2-9b-it`
- **Setup**: Get free API key from [console.groq.com](https://console.groq.com)

### 2. **Hugging Face Inference API** 🤗
- **Free Tier**: Free tier available (rate limits apply)
- **Models**: 
  - `mistralai/Mistral-7B-Instruct-v0.2`
  - `meta-llama/Llama-2-7b-chat-hf`
  - `google/flan-t5-large`
  - `microsoft/DialoGPT-large`
  - `tiiuae/falcon-7b-instruct`
- **Setup**: Get free API key from [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens)

### 3. **Together AI** 🚀
- **Free Tier**: $25 free credit to start
- **Models**:
  - `meta-llama/Llama-3-8b-chat-hf`
  - `mistralai/Mixtral-8x7B-Instruct-v0.1`
  - `meta-llama/Llama-3-70b-chat-hf`
  - `Qwen/Qwen2.5-7B-Instruct`
- **Setup**: Get free API key from [together.ai](https://together.ai)

### 4. **OpenRouter** 🌐
- **Free Tier**: Free tier available with various models
- **Models**:
  - `google/gemini-flash-1.5`
  - `google/gemini-pro`
  - `mistralai/mistral-7b-instruct`
  - `meta-llama/llama-3-8b-instruct`
  - `qwen/qwen-2.5-7b-instruct`
- **Setup**: Get free API key from [openrouter.ai](https://openrouter.ai)

### 5. **Ollama** 🦙 (Local - Default)
- **Free**: Completely free, runs locally
- **Models**: Any Ollama-compatible model
- **Setup**: Install Ollama locally, no API key needed

## Configuration

### Step 1: Get API Keys

1. **Groq**: Sign up at [console.groq.com](https://console.groq.com) → Get API key
2. **Hugging Face**: Sign up at [huggingface.co](https://huggingface.co) → Settings → Access Tokens
3. **Together AI**: Sign up at [together.ai](https://together.ai) → Get API key
4. **OpenRouter**: Sign up at [openrouter.ai](https://openrouter.ai) → Get API key

### Step 2: Configure Environment Variables

Add your API keys to your `.env` file in the project root:

```env
# Ollama (default, no key needed if running locally)
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=

# Free GenAI Providers
GROQ_API_KEY=your_groq_api_key_here
HUGGINGFACE_API_KEY=your_huggingface_api_key_here
TOGETHER_API_KEY=your_together_api_key_here
OPENROUTER_API_KEY=your_openrouter_api_key_here

# Default provider (optional, defaults to 'ollama')
DEFAULT_GENAI_PROVIDER=groq
```

### Step 3: Use in Application

1. **Select Provider**: Use the provider dropdown in the header to switch between providers
2. **Select Model**: Choose a model from the model dropdown (models update based on selected provider)
3. **Chat**: Start chatting - the system automatically uses the selected provider

## Features

- ✅ **Seamless Switching**: Switch between providers without code changes
- ✅ **Auto-Detection**: Automatically detects provider from model name
- ✅ **Streaming Support**: All providers support streaming responses
- ✅ **RAG Compatible**: Works with existing RAG functionality
- ✅ **Fallback**: Automatically falls back to Ollama if provider unavailable

## Provider Comparison

| Provider | Speed | Free Tier | Best For |
|----------|-------|-----------|----------|
| Groq | ⚡⚡⚡⚡⚡ | 30 req/min | Fastest responses |
| Together AI | ⚡⚡⚡⚡ | $25 credit | High-quality models |
| OpenRouter | ⚡⚡⚡ | Free tier | Multiple model options |
| Hugging Face | ⚡⚡⚡ | Free tier | Open-source models |
| Ollama | ⚡⚡ | Unlimited | Privacy, offline use |

## Usage Tips

1. **For Speed**: Use Groq with `llama-3.1-8b-instant`
2. **For Quality**: Use Together AI with `Llama-3-70b-chat-hf`
3. **For Privacy**: Use Ollama (runs locally, no data sent externally)
4. **For Variety**: Use OpenRouter to access multiple model providers

## Troubleshooting

### Provider Not Showing Up
- Check that API key is set in `.env` file
- Verify API key is valid
- Check browser console for errors

### Models Not Loading
- Ensure provider is selected
- Check API key permissions
- Verify internet connection

### API Rate Limits
- Groq: 30 requests/minute - wait or upgrade
- Hugging Face: Check rate limits in dashboard
- Together AI: Check credit balance
- OpenRouter: Check usage limits

## Architecture

The integration uses a provider abstraction pattern:

```
GenAIProviderInterface (interface)
├── OllamaProvider
├── GroqProvider
├── HuggingFaceProvider
├── TogetherAIProvider
└── OpenRouterProvider

GenAIFactory (factory)
└── Creates and manages provider instances
```

All providers implement the same interface, making it easy to:
- Add new providers
- Switch between providers
- Maintain consistent API

## Adding New Providers

To add a new provider:

1. Create a new provider class in `src/Services/GenAI/`
2. Implement `GenAIProviderInterface`
3. Add provider to `GenAIFactory`
4. Add API key to config
5. Update frontend if needed

Example:
```php
class NewProvider implements GenAIProviderInterface {
    // Implement required methods
}
```

## License

All providers maintain their own terms of service. Please review each provider's terms before use.
