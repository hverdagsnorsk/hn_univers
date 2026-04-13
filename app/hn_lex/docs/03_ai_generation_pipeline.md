# AI Generation Pipeline

Triggered when entry is missing or incomplete.

## Flow

1. Generate structure via OpenAI
2. Validate against LexContract
3. Insert entry
4. Insert grammar
5. Insert senses
6. Generate embeddings
7. Insert explanations

Safety:
- Scandinavian drift detection
- Required field validation
- Fallback creation
