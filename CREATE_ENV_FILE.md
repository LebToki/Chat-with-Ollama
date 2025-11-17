# Creating .env File

The application requires a `.env` file in the project root. Create it with the following content:

```env
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=
```

**Important Notes:**
1. If you don't have a JWT token, leave `OLLAMA_JWT_TOKEN` empty (as shown above) or remove the line entirely
2. Do NOT use placeholder text like `[insert your jwt token here]` - this will cause parsing errors
3. Do NOT put spaces around the `=` sign
4. Values should NOT be quoted unless they contain spaces

**Correct format:**
```env
OLLAMA_API_URL=http://localhost:11434/api/
OLLAMA_JWT_TOKEN=
```

**Incorrect formats (will cause errors):**
```env
OLLAMA_JWT_TOKEN = [insert your jwt token here]  ❌ (spaces and brackets)
OLLAMA_JWT_TOKEN="your token"  ❌ (quotes not needed)
OLLAMA_JWT_TOKEN = your token  ❌ (spaces around =)
```

The application will work without authentication for local Ollama instances, so an empty JWT token is fine.

