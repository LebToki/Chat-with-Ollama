# Creating .env File

The application requires a `.env` file in the project root. Create it with the following content:

```env
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=
OLLAMA_CLOUD_API_KEY=
OLLAMA_CLOUD_MODE=false

# Multi-Provider API Keys (Optional)
GROQ_API_KEY=
HUGGINGFACE_API_KEY=
TOGETHERAI_API_KEY=
OPENROUTER_API_KEY=

# Image Generation API Keys (Optional)
OPENAI_API_KEY=
STABILITY_API_KEY=
```

## Configuration Options

### Local Ollama (Default)
```env
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=
OLLAMA_CLOUD_API_KEY=
OLLAMA_CLOUD_MODE=false
```

### Ollama Cloud API
```env
OLLAMA_API_URL=https://api.ollama.com/v1/
OLLAMA_JWT_TOKEN=
OLLAMA_CLOUD_API_KEY=your_cloud_api_key_here
OLLAMA_CLOUD_MODE=true
```

### Multi-Provider Configuration
```env
# Groq (Fast inference with Llama models)
GROQ_API_KEY=your_groq_api_key_here

# HuggingFace (Access to thousands of models)
HUGGINGFACE_API_KEY=your_huggingface_api_key_here

# TogetherAI (Affordable cloud inference)
TOGETHERAI_API_KEY=your_togetherai_api_key_here

# OpenRouter (Access to GPT-4, Claude, Gemini, and more)
OPENROUTER_API_KEY=your_openrouter_api_key_here

# Image Generation
# OpenAI (DALL-E)
OPENAI_API_KEY=your_openai_api_key_here

# Stability AI (Stable Diffusion)
STABILITY_API_KEY=your_stability_api_key_here
```

**Important Notes:**
1. If you don't have a JWT token, leave `OLLAMA_JWT_TOKEN` empty (as shown above) or remove the line entirely
2. For Ollama Cloud, get your API key from [ollama.com](https://ollama.com)
3. For Groq, get your API key from [console.groq.com](https://console.groq.com)
4. For HuggingFace, get your API key from [huggingface.co/settings/tokens](https://huggingface.co/settings/tokens)
5. For TogetherAI, get your API key from [together.ai](https://together.ai)
6. For OpenRouter, get your API key from [openrouter.ai/keys](https://openrouter.ai/keys)
7. For OpenAI (DALL-E), get your API key from [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
8. For Stability AI (Stable Diffusion), get your API key from [platform.stability.ai](https://platform.stability.ai)
9. Do NOT use placeholder text like `[insert your jwt token here]` - this will cause parsing errors
10. Do NOT put spaces around the `=` sign
11. Values should NOT be quoted unless they contain spaces

**Correct format:**
```env
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=
OLLAMA_CLOUD_API_KEY=
OLLAMA_CLOUD_MODE=false
GROQ_API_KEY=
HUGGINGFACE_API_KEY=
TOGETHERAI_API_KEY=
OPENROUTER_API_KEY=
```

**Incorrect formats (will cause errors):**
```env
OLLAMA_JWT_TOKEN = [insert your jwt token here]  ❌ (spaces and brackets)
OLLAMA_JWT_TOKEN="your token"  ❌ (quotes not needed)
OLLAMA_JWT_TOKEN = your token  ❌ (spaces around =)
```

The application will work without authentication for local Ollama instances, so an empty JWT token is fine.

