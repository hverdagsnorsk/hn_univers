# Lookup Pipeline

## Step 1 – Word Click

Reader.js sends context to lookup.php.

## Step 2 – LookupService

1. CandidateRepository finds lemma and inflections
2. LexDisambiguator ranks candidates
3. GrammarRepository loads grammar
4. ExplanationRepository loads explanations
5. LexContract filters output
6. lookup.php formats response

## Output

{
  found: true,
  lemma: "...",
  word_class: "...",
  grammar: [...],
  forklaring: "...",
  example: "..."
}
