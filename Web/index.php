<?php
declare(strict_types=1);
require __DIR__ . '/lib/readme.php';

// Le catalogue est lu dans le ReadMe.md placé à côté de cette page.
$readme = __DIR__ . '/ReadMe.md';
if (!is_file($readme) && is_file(__DIR__ . '/../Extensions/ReadMe.md')) {
    $readme = __DIR__ . '/../Extensions/ReadMe.md'; // repli en local (dépôt complet)
}
$data = rw_parse_readme($readme);

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data['mods'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$mods = $data['mods'];
$count = static fn(string $type) => count(array_filter($mods, fn($m) => in_array($type, $m['types'], true)));
$stats = [
    'Entrées'     => count($mods),
    'Traductions' => $count('Traduction'),
    'Correctifs'  => $count('Correctif'),
    'Audits'      => $count('Audit'),
];
$updated = $data['updated'] ? date('d/m/Y H:i', $data['updated']) : '—';
$e = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$v = static fn(string $f) => $f . '?v=' . (@filemtime(__DIR__ . '/' . $f) ?: 1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RimWorld – Mods, traductions FR et correctifs | Skoltroy</title>
<meta name="description" content="Dépôt de mods RimWorld : traductions françaises (French TradFix) et correctifs de mods pour éviter les erreurs en jeu.">
<link rel="icon" href="<?= $e($v('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= $e($v('assets/style.css')) ?>">
</head>
<body>
<div id="wrapper">

  <header id="top_section">
    <h1 class="forumtitle">
      <a href="./"><span class="logo-rim">RIMWORLD</span><span class="logo-sub">Mods · Traductions FR · Correctifs</span></a>
    </h1>
    <nav class="top_menu">
      <a class="active" href="./">Catalogue</a>
      <a href="https://github.com/skoltroy/Rimworld" target="_blank" rel="noopener">GitHub</a>
      <a href="https://github.com/skoltroy/Rimworld/archive/refs/heads/main.zip">Télécharger (.zip)</a>
      <a href="?format=json">JSON</a>
    </nav>
  </header>

  <div class="navigate_section">
    <span><a href="./">rimworld.skoltroy.com</a> » <strong>Catalogue des mods</strong></span>
    <span class="updated">Catalogue mis à jour le <?= $e($updated) ?></span>
  </div>

  <main id="content_section">

    <section class="roundframe">
      <div class="cat_bar"><h2 class="catbg"><?= $e($data['title']) ?></h2></div>
      <div class="windowbg intro">
        <?= $data['intro'] ?>
        <p class="howto">
          <strong>Installation :</strong> téléchargez le dossier <code>… - French TradFix</code> correspondant depuis
          <a href="https://github.com/skoltroy/Rimworld/tree/main/Extensions" target="_blank" rel="noopener">GitHub</a>,
          copiez-le dans <code>RimWorld/Mods</code> puis chargez-le <em>après</em> le mod d’origine.
        </p>
      </div>
      <ul class="stats">
        <?php foreach ($stats as $label => $n): ?>
          <li><span class="num"><?= (int)$n ?></span><span class="lbl"><?= $e($label) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="roundframe">
      <div class="cat_bar"><h2 class="catbg">Catalogue des mods</h2></div>

      <div class="toolbar windowbg2">
        <label class="search">
          <span>Rechercher</span>
          <input type="search" id="q" placeholder="Nom, ID Workshop, dossier, travail effectué…" autocomplete="off">
        </label>
        <label>
          <span>Type</span>
          <select id="f-type">
            <option value="">Tous</option>
            <option>Traduction</option>
            <option>Correctif</option>
            <option>Audit</option>
          </select>
        </label>
        <label>
          <span>Version RW</span>
          <select id="f-rw"><option value="">Toutes</option></select>
        </label>
        <label>
          <span>Source</span>
          <select id="f-src">
            <option value="">Toutes</option>
            <option value="steam">Steam Workshop</option>
            <option value="local">Local</option>
          </select>
        </label>
        <label>
          <span>Lignes</span>
          <select id="f-len">
            <option selected>10</option><option>25</option><option>50</option><option>100</option><option value="0">Tout</option>
          </select>
        </label>
        <button type="button" id="reset" class="button">Réinitialiser</button>
      </div>

      <div class="table-wrap">
        <table id="mods" class="table_grid">
          <thead>
            <tr>
              <th data-key="name" class="sortable">Mod</th>
              <th data-key="type" class="sortable">Type</th>
              <th data-key="id" class="sortable">ID Workshop</th>
              <th data-key="rw" class="sortable">RimWorld</th>
              <th data-key="version" class="sortable col-version">Version</th>
              <th data-key="date" class="sortable" data-default="desc">Date</th>
              <th data-key="work" class="col-work">Travail effectué</th>
              <th class="col-dl">Télécharger</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
        <noscript><p class="windowbg">Activez JavaScript pour afficher le catalogue, ou consultez <a href="?format=json">la version JSON</a>.</p></noscript>
      </div>

      <div class="pagesection">
        <span id="info"></span>
        <nav id="pager" aria-label="Pagination"></nav>
      </div>
    </section>

    <?php if ($data['footer']): ?>
      <section class="roundframe notes windowbg"><?= $data['footer'] ?></section>
    <?php endif; ?>
  </main>

  <footer id="footer_section">
    <p>Sources : <a href="https://github.com/skoltroy/Rimworld" target="_blank" rel="noopener">github.com/skoltroy/Rimworld</a>
      · RimWorld est une marque de Ludeon Studios. Site non officiel.</p>
  </footer>
</div>

<script>window.RW_MODS = <?= json_encode($mods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= $e($v('assets/app.js')) ?>"></script>
</body>
</html>
