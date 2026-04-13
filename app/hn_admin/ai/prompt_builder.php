<?php
declare(strict_types=1);

function build_task_prompt(
    string $text,
    string $level,
    int $count
): string {
    return <<<PROMPT
Du er en faglig presis lærer i norsk som andrespråk.

TEKST:
{$text}

OPPGAVE:
Lag {$count} varierte oppgaver basert på teksten over.
Oppgavene skal være på {$level}-nivå.

Krav:
- Bruk oppgavetypene: mcq, fill, short, order
- Hver oppgave må ha:
  - task_type
  - payload
- Payload må være kompatibel med systemet

Returner KUN gyldig JSON.
Ikke skriv forklaringer.
Ikke bruk markdown.
PROMPT;
}
