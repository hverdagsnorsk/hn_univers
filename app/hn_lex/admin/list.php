<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';
require_once dirname(__DIR__) . '/inc/LexTerminology.php';

$layout_mode = 'admin';
$page_title  = LexTerminology::label('all_entries');

require_once $root . '/hn_core/layout/header.php';

/* ==========================================================
   DB
========================================================== */

$pdoLex = $pdo_lex ?? ($pdo ?? null);
if (!$pdoLex instanceof PDO) {
    exit('Lex database not available.');
}

/* ==========================================================
   INPUT
========================================================== */

$q         = trim((string)($_GET['q'] ?? ''));
$class     = trim((string)($_GET['class'] ?? ''));
$sort      = (string)($_GET['sort'] ?? 'lemma');
$direction = strtolower((string)($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$page      = max(1, (int)($_GET['page'] ?? 1));

$limit  = 50;
$offset = ($page - 1) * $limit;

$allowedSort = ['lemma','ordklasse','id'];
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'lemma';
}

/* ==========================================================
   WHERE
========================================================== */

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = "LOWER(e.lemma) LIKE LOWER(:q)";
    $params[':q'] = '%' . $q . '%';
}

if ($class !== '') {
    $where[] = "wc.code = :class";
    $params[':class'] = $class;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ==========================================================
   SORT
========================================================== */

$orderSql = match ($sort) {
    'id'        => 'e.id',
    'ordklasse' => 'wc.code',
    default     => 'e.lemma'
};

$orderDirection = $direction === 'desc' ? 'DESC' : 'ASC';

/* ==========================================================
   COUNT
========================================================== */

$stmt = $pdoLex->prepare("
    SELECT COUNT(*)
    FROM lex_entries e
    JOIN lex_word_classes wc ON wc.id = e.word_class_id
    $whereSql
");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

/* ==========================================================
   DATA
========================================================== */

$sql = "
    SELECT 
        e.id,
        e.lemma,
        e.source,
        e.verified_at,
        wc.code,
        wc.name
    FROM lex_entries e
    JOIN lex_word_classes wc ON wc.id = e.word_class_id
    $whereSql
    ORDER BY {$orderSql} {$orderDirection}
    LIMIT :offset, :limit
";

$stmt = $pdoLex->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   KLASSER
========================================================== */

$classes = $pdoLex->query("
    SELECT code, name
    FROM lex_word_classes
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   HELPERS
========================================================== */

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function listQueryParams(array $overrides = []): string
{
    $base = $_GET;
    $merged = array_merge($base, $overrides);
    return http_build_query($merged);
}

function badge(string $type): string
{
    return match ($type) {
        'ai'     => '<span class="hn-badge hn-badge--info">'
                    . h(LexTerminology::label('ai_generated'))
                    . '</span>',
        'manual' => '<span class="hn-badge hn-badge--success">'
                    . h(LexTerminology::label('manual'))
                    . '</span>',
        default  => ''
    };
}
?>

<section class="hn-section">
  <div class="hn-container">

    <header class="hn-pagehead">
      <h1 class="hn-title"><?= h($page_title) ?></h1>
      <p class="hn-meta">
        <?= h(LexTerminology::label('total')) ?>:
        <?= (int)$total ?>
      </p>
    </header>

    <!-- Filter -->
    <div class="hn-card hn-mb-4">
      <div class="hn-card__body">

        <form method="get" class="hn-filter-bar">

          <input class="hn-input"
                 type="text"
                 name="q"
                 placeholder="<?= h(LexTerminology::label('search_placeholder')) ?>"
                 value="<?= h($q) ?>">

          <select class="hn-select" name="class">
            <option value="">
              <?= h(LexTerminology::label('all_word_classes')) ?>
            </option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= h($c['code']) ?>"
                  <?= $class === $c['code'] ? 'selected' : '' ?>>
                <?= h($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button class="hn-btn hn-btn--primary">
            <?= h(LexTerminology::label('filter')) ?>
          </button>

        </form>

      </div>
    </div>

    <!-- Tabell -->
    <div class="hn-card">
      <div class="hn-card__body" style="padding:0;">

        <table class="hn-table hn-table--striped">
          <thead>
            <tr>
              <th>ID</th>
              <th><?= h(LexTerminology::label('lemma')) ?></th>
              <th><?= h(LexTerminology::label('word_class')) ?></th>
              <th><?= h(LexTerminology::label('status')) ?></th>
              <th></th>
            </tr>
          </thead>

          <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="5">
                <div class="hn-meta" style="padding:1rem;">
                  <?= h(LexTerminology::label('no_results')) ?>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>

                <td>
                  <a href="edit.php?id=<?= (int)$r['id'] ?>" class="hn-link">
                    <?= h($r['lemma']) ?>
                  </a>
                </td>

                <td><?= h($r['name']) ?></td>

                <td>
                  <?= badge($r['source'] ?? '') ?>

                  <?php if (!empty($r['verified_at'])): ?>
                    <span class="hn-badge hn-badge--verified">
                      <?= h(LexTerminology::label('verified')) ?>
                    </span>
                  <?php else: ?>
                    <span class="hn-badge hn-badge--warning">
                      <?= h(LexTerminology::label('not_verified')) ?>
                    </span>
                  <?php endif; ?>
                </td>

                <td class="u-text-right">
                  <a class="hn-btn hn-btn--outline hn-btn--sm"
                     href="edit.php?id=<?= (int)$r['id'] ?>">
                    <?= h(LexTerminology::label('edit')) ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>

      </div>
    </div>

  </div>
</section>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>