<?php
declare(strict_types=1);

/*
|---------------------------------------------------------
| HN TEST FORMATTER (Isolated)
|---------------------------------------------------------
| - Does NOT affect existing reader
| - Pure experimental parser
|---------------------------------------------------------
*/

function hn_format_text(string $text): string
{
    // Escape HTML first (security)
    $text = htmlspecialchars($text);

    // H2 headings (## Heading)
    $text = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $text);

    // Bold (**text**)
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    // Bullet points (- item)
    $text = preg_replace('/^- (.*)$/m', '<li>$1</li>', $text);

    // Wrap consecutive <li> inside <ul>
    $text = preg_replace('/(<li>.*<\/li>)/sU', '<ul>$1</ul>', $text);

    // Paragraphs
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $text = '';

    foreach ($paragraphs as $p) {
        if (trim($p) !== '') {
            if (!str_starts_with(trim($p), '<h2>')
                && !str_starts_with(trim($p), '<ul>')) {
                $text .= "<p>$p</p>";
            } else {
                $text .= $p;
            }
        }
    }

    return $text;
}

$rawText = $_POST['text'] ?? '';

?>

<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>HN Formatter Test</title>
<style>
body { font-family: system-ui; margin: 40px; }
textarea { width: 100%; height: 250px; }
.preview { margin-top: 40px; padding: 30px; background: #f4f4f4; }
h2 { margin-top: 30px; }
ul { margin-left: 20px; }
</style>
</head>
<body>

<h1>Formatter Test (isolert)</h1>

<form method="post">
    <textarea name="text"><?= htmlspecialchars($rawText) ?></textarea>
    <br><br>
    <button type="submit">Render</button>
</form>

<?php if ($rawText): ?>
<div class="preview">
    <?= hn_format_text($rawText); ?>
</div>
<?php endif; ?>

</body>
</html>