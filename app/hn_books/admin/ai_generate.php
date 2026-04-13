<?php
declare(strict_types=1);

require_once __DIR__ . '/../_config/config.php';

/* --------------------------------------------------
   Session + tilgang
-------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Ingen tilgang');
}

/* --------------------------------------------------
   Hent tekster
-------------------------------------------------- */
$texts = $pdo->query("
    SELECT id, title
    FROM texts
    ORDER BY title
")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';
$storedCount = 0;

/* --------------------------------------------------
   Lagre genererte oppgaver
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text_id'], $_POST['generated_json'])) {

    $textId = (int)$_POST['text_id'];
    $data   = json_decode($_POST['generated_json'], true);

    if ($textId <= 0) {
        $error = 'Ingen tekst valgt.';
    } elseif (!is_array($data)) {
        $error = 'Ugyldig JSON. Kontroller strukturen.';
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO tasks (text_id, task_type, status, payload_json, created_at)
            VALUES (:text_id, :type, 'draft', :payload, NOW())
        ");

        foreach ($data as $task) {
            if (
                empty($task['task_type']) ||
                empty($task['payload']) ||
                !is_array($task['payload'])
            ) {
                continue;
            }

            $stmt->execute([
                'text_id' => $textId,
                'type'    => $task['task_type'],
                'payload' => json_encode(
                    $task['payload'],
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                )
            ]);

            $storedCount++;
        }

        if ($storedCount > 0) {
            $success = "{$storedCount} oppgaver lagret som utkast.";
        } else {
            $error = 'Ingen gyldige oppgaver funnet i JSON.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – AI-generering</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
}

/* HEADER */
.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:40px;
}
.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

/* CONTAINER */
.container{max-width:1100px;margin:auto}

/* CARD */
.card{
    background:#ffffff;
    border-radius:16px;
    padding:30px 32px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    margin-bottom:32px;
}

/* FORM */
label{font-weight:600}
select, textarea, button{
    width:100%;
    margin-top:6px;
    margin-bottom:18px;
    padding:12px;
    font-size:1rem;
    border-radius:10px;
    border:1px solid #cbd5e1;
}
textarea{
    font-family: Consolas, monospace;
    min-height:260px;
}

button{
    background:#2f8485;
    color:#fff;
    border:none;
    font-weight:700;
    cursor:pointer;
}
button:hover{background:#226c6d}

/* INFO */
.note{
    background:#f1f9f9;
    padding:16px;
    border-left:5px solid #2f8485;
    border-radius:8px;
    margin-bottom:28px;
}

.msg-success{
    background:#ecfeff;
    border-left:4px solid #16a34a;
    padding:12px 16px;
    margin-bottom:20px;
    color:#065f46;
    font-weight:600;
}
.msg-error{
    background:#fef2f2;
    border-left:4px solid #dc2626;
    padding:12px 16px;
    margin-bottom:20px;
    color:#7f1d1d;
    font-weight:600;
}

pre{
    background:#0f172a;
    color:#e5e7eb;
    padding:18px;
    border-radius:12px;
    overflow:auto;
    font-size:.9rem;
}

/* FOOTER */
.footer{
    text-align:center;
    margin-top:60px;
    font-size:.85rem;
    color:#64748b;
}
</style>
</head>

<body>

<div class="container">

<!-- HEADER -->
<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>AI – Generer oppgaver</h1>
        <div class="sub">
            AI-støttet oppgaveutvikling • 2026<br>
            <a href="index.php">← Tilbake til adminpanel</a>
        </div>
    </div>
</div>

<div class="card">

<div class="note">
<strong>Arbeidsflyt:</strong><br>
1. Lim tekstutdraget inn i ChatGPT<br>
2. Bruk prompten under<br>
3. Kopier <strong>kun JSON</strong><br>
4. Lagre → oppgavene blir <em>utkast</em>
</div>

<?php if ($error): ?>
<div class="msg-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post">

<label>1. Velg tekst</label>
<select name="text_id" required>
    <option value="">Velg tekst …</option>
    <?php foreach ($texts as $t): ?>
        <option value="<?= $t['id'] ?>">
            <?= htmlspecialchars($t['title']) ?>
        </option>
    <?php endforeach; ?>
</select>

<label>2. Lim inn generert JSON</label>
<textarea name="generated_json" placeholder='[
  {
    "task_type": "mcq",
    "payload": {
      "prompt": "...",
      "choices": ["...", "..."],
      "correct_index": 0,
      "feedback": {
        "correct": "...",
        "incorrect": "..."
      }
    }
  }
]'></textarea>

<button>Lagre oppgaver som utkast</button>

</form>
</div>

<div class="card">
<h2>AI-prompt (bruk i ChatGPT)</h2>

<pre>
Du er en faglig presis lærer i norsk som andrespråk.

Lag 5 varierte oppgaver basert på teksten under.
Oppgavene skal være på A2/B1-nivå.

Krav:
- Bruk oppgavetypene: mcq, fill, short, order
- Hver oppgave må ha:
  - task_type
  - payload
- Payload må være kompatibel med systemet

Returner KUN gyldig JSON.
Ikke skriv forklaringer.
Ikke bruk markdown.
</pre>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>
</body>
</html>
