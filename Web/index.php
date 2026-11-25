<?php
declare(strict_types=1);
require __DIR__ . '/lib/github.php';
date_default_timezone_set('Europe/Paris');
$sourceUrl = 'https://github.com/' . GH_OWNER . '/' . GH_REPO;

// Le catalogue est lu à chaque visite (cache court) dans Extensions/ReadMe.md sur GitHub.
$readme = gh_readme(isset($_GET['refresh']));
$data = rw_parse_readme($readme['markdown']);
$mods = $data['mods'];

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($mods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$count = static fn(string $type) => count(array_filter($mods, fn($m) => in_array($type, $m['types'], true)));
$zipCount = count(array_unique(array_merge([], ...array_map(fn($m) => array_column($m['zips'], 'name'), $mods))));
$stats = [
    'Entrées'      => count($mods),
    'Mods perso'   => $count('Mod'),
    'Traductions'  => $count('Traduction'),
    'Correctifs'   => $count('Correctif'),
    'Archives ZIP' => $zipCount,
];
$fetched = $readme['fetched'] ? date('d/m/Y H:i', $readme['fetched']) : '—';
$readmeUrl = gh_blob_url($readme['path']);
$cols = $data['columns'];
$col = static fn(int $i, string $def) => $cols[$i] ?? $def;
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
      <a href="?format=json">JSON</a>
    </nav>
  </header>

  <div class="navigate_section">
    <span><a href="./">rimworld.skoltroy.com</a> » <strong>Catalogue des mods</strong></span>
    <span class="updated">
      <?php if ($readme['stale']): ?><span class="stale">GitHub injoignable, copie en cache</span> · <?php endif; ?>
      Lu depuis <a href="<?= $e($readmeUrl) ?>" target="_blank" rel="noopener"><?= $e($readme['path']) ?></a> le <?= $e($fetched) ?>
    </span>
  </div>

  <main id="content_section">

    <section class="roundframe">
      <div class="cat_bar"><h2 class="catbg"><?= $e($data['title']) ?></h2></div>
      <div class="windowbg intro">
        <?= $data['intro'] ?>
        <p class="howto">
          <strong>Installation :</strong> cliquez sur le bouton de téléchargement de l’archive voulue, décompressez-la dans
          un dossier du même nom sous <code>RimWorld/Mods</code> (l’archive contient directement le package),
          puis chargez-le <em>après</em> le mod d’origine.
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
          <input type="search" id="q" placeholder="Nom, ID, package, archive, travail effectué…" autocomplete="off">
        </label>
        <label>
          <span>Type</span>
          <select id="f-type">
            <option value="">Tous</option>
            <option>Mod</option>
            <option>Traduction</option>
            <option>Correctif</option>
          </select>
        </label>
        <label>
          <span>Version RW</span>
          <select id="f-rw"><option value="">Toutes</option></select>
        </label>
        <label>
          <span>Archive</span>
          <select id="f-zip">
            <option value="">Toutes</option>
            <option value="yes">Téléchargeable</option>
            <option value="no">Sans archive</option>
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
              <th data-key="date" class="sortable" data-default="desc"><?= $e($col(0, 'Date')) ?></th>
              <th data-key="id" class="sortable"><?= $e($col(1, 'ID')) ?></th>
              <th data-key="name" class="sortable col-name">Mod</th>
              <th data-key="package" class="sortable col-package"><?= $e($col(2, 'Package / archive')) ?></th>
              <th data-key="rw" class="sortable col-version"><?= $e($col(5, 'Version déclarée / versions de RimWorld')) ?></th>
              <th class="col-work"><?= $e($col(6, 'Travail effectué')) ?></th>
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

    <?php if (trim($data['footer']) !== ''): ?>
      <section class="roundframe notes windowbg"><?= $data['footer'] ?></section>
    <?php endif; ?>
  </main>

  <footer id="footer_section">
    <p>Sources : <a href="<?= htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">github.com/<?= GH_OWNER ?>/<?= GH_REPO ?></a>
      · RimWorld est une marque de Ludeon Studios. Site non officiel.</p>
  </footer>
</div>

<div id="tip" class="tip" role="tooltip" hidden></div>
<script>window.RW_MODS = <?= json_encode($mods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= $e($v('assets/app.js')) ?>"></script>
</body>
</html>
