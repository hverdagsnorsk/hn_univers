# HN Lex – Architecture Overview

## Purpose

HN Lex is a structured Norwegian language engine combining:

- Dictionary storage
- Grammar engine
- AI generation
- Embedding-based sense ranking
- Context-based disambiguation
- Editorial control system

---

## Core Flow

Reader.js  
→ lookup.php  
→ LookupService  
→ CandidateRepository  
→ LexDisambiguator  
→ GrammarRepository  
→ ExplanationRepository  
→ LexContract  
→ JSON → Popover

---

## AI Flow

SenseGenerationService  
→ OpenAI  
→ EmbeddingService  
→ Database
