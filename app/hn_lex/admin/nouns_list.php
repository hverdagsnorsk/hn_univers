<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

header('Content-Type: application/json');

$entryId = (int)($_POST['entry_id'] ?? 0);
$field   = $_POST['field'] ?? '';
$value   = trim((string)($_POST['value'] ?? ''));

$allowedFields = [
    'gender',
    'gender_alt',
    'singular_indefinite',
    'singular_indefinite_alt',
    'singular_definite',
    'singular_definite_alt',
    'plural_indefinite',
    'plural_indefinite_alt',
    'plural_definite',
    'plural_definite_alt'
];

if ($entryId <= 0 || !in_array($field, $allowedFields, true)) {
    echo json_encode(['success' => false]);
    exit;
}

/* Valider kjønn */
if (in_array($field, ['gender', 'gender_alt'], true)) {
    if ($value !== '' && !in_array($value, ['m', 'f', 'n'], true)) {
        echo json_encode(['success' => false]);
        exit;
    }
}

$sql = sprintf(
    "UPDATE lex_nouns SET %s = ? WHERE entry_id = ?",
    $field
);

$stmt = $pdo_lex->prepare($sql);

$ok = $stmt->execute([
    $value === '' ? null : $value,
    $entryId
]);

echo json_encode(['success' => $ok]);