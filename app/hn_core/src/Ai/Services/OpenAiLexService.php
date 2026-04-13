<?php
declare(strict_types=1);

namespace HnCore\Ai\Services;

use HnCore\Ai\Contracts\LexAiInterface;
use HnCore\Ai\Transport\OpenAiHttpClient;
use HnCore\Ai\Exceptions\AiException;
use HnLex\Contracts\LexContract;

class OpenAiLexService implements LexAiInterface
{
    public function __construct(
        private OpenAiHttpClient $client = new OpenAiHttpClient()
    ) {}

    public function generateEntry(string $word): array
    {
	     
        $word = $this->normalizeText($word);

        if ($word === '') {
            throw new AiException('Empty word sent to AI');
        }

        $allowed = implode(', ', LexContract::WORD_CLASSES);

        $messages = [
            [
                'role' => 'system',
                'content' =>
                    "You are a Norwegian lexicographer.\n" .
                    "Return ONLY valid JSON.\n" .
                    "No markdown. No explanations.\n" .
                    "Use Bokmål.\n\n" .

                    "STRICT REQUIREMENTS:\n" .
                    "- You MUST include top-level field \"word_class\".\n" .
                    "- word_class MUST be one of: {$allowed}\n" .
                    "- NEVER omit word_class\n\n" .

                    "- If word_class is 'pronoun' or 'determiner', you MUST include \"subclass\".\n" .
                    "- Valid subclasses for pronoun:\n" .
                    "  personal, possessive, demonstrative, reflexive, interrogative, relative, indefinite\n\n" .

                    "- A word can have MULTIPLE meanings.\n" .
                    "- Each sense MUST include:\n" .
                    "  - word_class\n" .
                    "  - definition\n" .
                    "  - example\n\n" .

                    "Required JSON:\n" .
                    "{\n" .
                    "  \"lemma\": \"\",\n" .
                    "  \"language\": \"nb\",\n" .
                    "  \"word_class\": \"\",\n" .
                    "  \"subclass\": \"\",\n" .
                    "  \"senses\": [ ... ]\n" .
                    "}"
            ],
            [
                'role' => 'user',
                'content' => "WORD:\n\"{$word}\""
            ]
        ];

        $data = $this->client->json($messages);

        file_put_contents(
            '/tmp/ai_debug.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        if (!is_array($data)) {
            throw new AiException('AI returned invalid payload');
        }

        $lemma = $this->normalizeLemma((string)($data['lemma'] ?? $word));

        /* ======================================================
           WORD CLASS
        ====================================================== */

        $wordClass = LexContract::normalizeWordClass(
            (string)($data['word_class'] ?? '')
        );

        if (!LexContract::isValidWordClass($wordClass)) {

            foreach (($data['senses'] ?? []) as $s) {

                $wc = LexContract::normalizeWordClass(
                    (string)($s['word_class'] ?? '')
                );

                if (LexContract::isValidWordClass($wc)) {
                    $wordClass = $wc;
                    break;
                }
            }
        }

        if (!LexContract::isValidWordClass($wordClass)) {
            throw new AiException("Missing valid word_class for: {$word}");
        }

        /* ======================================================
           SUBCLASS (entry-level)
        ====================================================== */

        $subclass = LexContract::normalizeSubclass(
            $wordClass,
            $data['subclass'] ?? null
        );

        if (!$subclass && !empty($data['senses'])) {
            foreach ($data['senses'] as $s) {
                $sub = LexContract::normalizeSubclass(
                    $wordClass,
                    $s['subclass'] ?? null
                );
                if ($sub) {
                    $subclass = $sub;
                    break;
                }
            }
        }

        return [
            'lemma'      => $lemma,
            'language'   => 'nb',
            'word_class' => $wordClass,
            'subclass'   => $subclass,
            'senses'     => $this->normalizeMultiSenses(
                $data['senses'] ?? [],
                $subclass // 🔥 send entry-subclass ned
            )
        ];
    }

    /* ======================================================
       🔧 Interface
    ====================================================== */

    public function generateBatch(array $words): array
    {
        $out = [];

        foreach ($words as $w) {
            try {
                $out[] = $this->generateEntry((string)$w);
            } catch (\Throwable) {
                // ignore per item
            }
        }

	

        return $out;
    }

    /* ======================================================
       SENSES (FIX: subclass-propagasjon)
    ====================================================== */

    private function normalizeMultiSenses(array $senses, ?string $entrySubclass = null): array
    {
        $out = [];

        foreach ($senses as $s) {

            $wc = LexContract::normalizeWordClass(
                (string)($s['word_class'] ?? '')
            );

            if (!LexContract::isValidWordClass($wc)) {
                continue;
            }

            $definition = trim((string)($s['definition'] ?? ''));
            if ($definition === '') continue;

            $example = trim((string)($s['example'] ?? ''));

            // 🔥 viktig: fallback til entry-subclass
            $subclass = LexContract::normalizeSubclass(
                $wc,
                $s['subclass'] ?? $entrySubclass
            );

            $grammar = $this->normalizeGrammar(
                $wc,
                $s['grammar'] ?? []
            );

            $out[] = [
                'word_class' => $wc,
                'subclass'   => $subclass,
                'definition' => $definition,
                'grammar'    => $grammar,
                'forms'      => $this->buildFormsFromGrammar($wc, $grammar),
                'explanations' => [[
                    'level' => 'A2',
                    'explanation' => $definition,
                    'example' => $example
                ]]
            ];
        }

        return $out;
    }

    private function normalizeGrammar(string $wordClass, mixed $grammar): array
    {
        if (!is_array($grammar)) {
            $grammar = [];
        }

        $allowed = LexContract::getAllowedFields($wordClass);
        $out = [];

        foreach ($allowed as $field) {

            $value = $grammar[$field] ?? '';

            if ($field === 'gender') {
                $out[$field] = $this->normalizeGender((string)$value);
                continue;
            }

            $out[$field] = is_scalar($value)
                ? trim((string)$value)
                : '';
        }

        return $out;
    }

    private function normalizeGender(string $value): string
    {
        return match (strtolower($value)) {
            'masculine', 'm' => 'm',
            'feminine', 'f' => 'f',
            'neuter', 'n' => 'n',
            default => ''
        };
    }

    private function buildFormsFromGrammar(string $wordClass, array $grammar): array
    {
        $map = match ($wordClass) {

            'noun' => [
                'indefinite_singular',
                'definite_singular',
                'indefinite_plural',
                'definite_plural',
            ],

            'verb' => [
                'infinitive',
                'present',
                'past',
                'past_participle',
                'imperative'
            ],

            'adjective' => [
                'positive_mf',
                'positive_n',
                'positive_pl',
                'comparative',
                'superlative'
            ],

            default => array_keys(array_filter($grammar))
        };

        $out = [];

        foreach ($map as $f) {

            $v = trim((string)($grammar[$f] ?? ''));
            if ($v === '') continue;

            $out[] = [
                'form' => $v,
                'field' => $f
            ];
        }

        return $out;
    }

    private function normalizeLemma(string $lemma): string
    {
        $lemma = $this->normalizeText($lemma);
        $lemma = preg_replace('/^(en|ei|et)\s+/u','',$lemma) ?? $lemma;
        return trim($lemma);
    }

    private function normalizeText(string $text): string
    {
        return mb_strtolower(trim($text));
    }
}