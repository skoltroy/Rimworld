<?php
declare(strict_types=1);
require __DIR__ . '/lib/github.php';
date_default_timezone_set('Europe/Paris');
$sourceUrl = 'https://github.com/' . GH_OWNER . '/' . GH_REPO;

// Le catalogue des extensions est lu à chaque visite (cache court) dans extensions/README.md sur GitHub.
$readme = gh_readme(isset($_GET['refresh']));
$data = rw_parse_readme($readme['markdown']);
$mods = $data['mods'];

try {
    $gameMods = rw_game_mods();
    $gameModsError = '';
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    $gameMods = [];
    $gameModsError = 'Inventaire des mods du jeu indisponible. Reconstruisez la publication depuis le dossier RimWorld/Mods.';
}
rw_link_catalog($gameMods, $mods);

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $out = ($_GET['tab'] ?? '') === 'game' ? $gameMods : $mods;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$activeTab = ($_GET['tab'] ?? '') === 'extensions' ? 'extensions' : 'game';
$count = static fn(string $type) => count(array_filter($mods, fn($m) => $m['zips'] && in_array($type, $m['types'], true)));
$workshop = array_filter($gameMods, fn($g) => $g['kind'] === 'workshop');
$zipCount = count(array_unique(array_merge([], ...array_map(fn($m) => array_column($m['zips'], 'name'), $mods))));
$stats = [
    'Mods Workshop'         => count($workshop),
    'Contenu officiel'      => count($gameMods) - count($workshop),
    'Extensions publiées'   => $zipCount,
    'Mods couverts'         => count(array_filter($gameMods, fn($g) => $g['ext'])),
    'Traductions FR'        => $count('Traduction'),
    'Correctifs'            => $count('Correctif'),
];
$fetched = $readme['fetched'] ? date('d/m/Y H:i', $readme['fetched']) : '—';
$e = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$v = static fn(string $f) => $f . '?v=' . (@filemtime(__DIR__ . '/' . $f) ?: 1);
$json = static fn($x) => json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
$tab = static fn(string $id) => $activeTab === $id;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RimWorld – Mods, traductions FR et correctifs | Skoltroy</title>
<meta name="description" content="Mods RimWorld (jeu officiel, DLC et Workshop) et extensions Skoltroy : traductions françaises (French TradFix) et correctifs à télécharger.">
<link rel="icon" href="<?= $e($v('assets/favicon.svg')) ?>" type="image/svg+xml">
<link rel="manifest" href="manifest.json">
<link rel="stylesheet" href="<?= $e($v('assets/style.css')) ?>">
</head>
<body>
<div id="wrapper">

  <header id="top_section">
    <h1 class="forumtitle">
      <a href="./"><span class="logo-rim">RIMWORLD</span><span class="logo-sub">Mods · Traductions FR · Correctifs</span></a>
    </h1>
    <nav class="top_menu" aria-label="Navigation principale">
      <a<?= $tab('game') ? ' class="active"' : '' ?> href="?tab=game" data-tab-link="game">Mods du jeu</a>
      <a<?= $tab('extensions') ? ' class="active"' : '' ?> href="?tab=extensions" data-tab-link="extensions">Mes extensions</a>
    </nav>
  </header>

  <div class="navigate_section">
    <span><a href="./">rimworld.skoltroy.com</a> » <strong>Catalogue des mods</strong></span>
    <?php
      $ghOk = !$readme['stale'];
      $ghTitle = $ghOk
          ? 'GitHub accessible : catalogue lu le ' . $fetched
          : 'GitHub injoignable : ' . (!empty($readme['local']) ? 'README local affiché'
              : ($readme['fetched'] ? 'catalogue en cache du ' . $fetched : 'aucune copie du catalogue'));
    ?>
    <span class="gh-status <?= $ghOk ? 'ok' : 'ko' ?>" role="status" tabindex="0" data-tip="<?= $e($ghTitle) ?>">
      <span class="dot" aria-hidden="true"></span><span class="sr-only"><?= $e($ghTitle) ?></span>
    </span>
  </div>

  <main id="content_section">

    <section class="roundframe">
      <div class="cat_bar"><h2 class="catbg">Mods RimWorld et extensions françaises</h2></div>
      <div class="windowbg intro">
        <p>
          <span class="legend"><span class="chip chip-official">Jeu officiel</span> jeu de base et DLC Ludeon</span>
          <span class="legend"><span class="chip chip-workshop">Workshop</span> mods de la communauté installés</span>
          <span class="legend"><span class="chip chip-Traduction">Traduction FR</span><span class="chip chip-Correctif">Correctif</span> extensions Skoltroy à télécharger</span>
        </p>
        <p>Chaque mod indique s’il dispose d’une extension à télécharger, et chaque extension renvoie vers son mod d’origine.</p>
      </div>
      <ul class="stats">
        <?php foreach ($stats as $label => $n): ?>
          <li><span class="num"><?= (int)$n ?></span><span class="lbl"><?= $e($label) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="roundframe">
      <div class="cat_bar"><h2 class="catbg">Catalogue</h2></div>

      <nav class="catalog-tabs" role="tablist" aria-label="Catégories du catalogue">
        <a id="tab-game" class="catalog-tab<?= $tab('game') ? ' active' : '' ?>" role="tab"
          aria-controls="panel-game" aria-selected="<?= $tab('game') ? 'true' : 'false' ?>"
          tabindex="<?= $tab('game') ? '0' : '-1' ?>" data-tab="game" href="?tab=game">
          Mods du jeu <span class="tab-count"><?= count($gameMods) ?></span>
        </a>
        <a id="tab-extensions" class="catalog-tab<?= $tab('extensions') ? ' active' : '' ?>" role="tab"
          aria-controls="panel-extensions" aria-selected="<?= $tab('extensions') ? 'true' : 'false' ?>"
          tabindex="<?= $tab('extensions') ? '0' : '-1' ?>" data-tab="extensions" href="?tab=extensions">
          Mes extensions <span class="tab-count"><?= $zipCount ?></span>
        </a>
      </nav>

      <!-- ===== Mods du jeu : contenu officiel + Workshop ===== -->
      <section id="panel-game" class="tab-panel" role="tabpanel" aria-labelledby="tab-game" tabindex="0"<?= $tab('game') ? '' : ' hidden' ?>>
        <div class="toolbar windowbg2">
          <label class="search">
            <span>Rechercher un mod du jeu</span>
            <input type="search" id="g-q" placeholder="Nom du mod, ID Workshop, DLC…" autocomplete="off">
          </label>
          <label>
            <span>Source</span>
            <select id="g-src">
              <option value="">Toutes</option>
              <option value="official">Jeu officiel</option>
              <option value="workshop">Workshop</option>
            </select>
          </label>
          <label>
            <span>Extension</span>
            <select id="g-ext">
              <option value="">Tous les mods</option>
              <option value="yes">Avec extension</option>
              <option value="no">Sans extension</option>
              <option value="examined">Examiné, rien à traduire</option>
            </select>
          </label>
          <label>
            <span>Lignes</span>
            <select id="g-len">
              <option selected>10</option><option>25</option><option>50</option><option>100</option><option value="0">Tout</option>
            </select>
          </label>
          <button type="button" id="g-reset" class="button">Réinitialiser</button>
        </div>

        <?php if ($gameModsError !== ''): ?>
          <p class="catalog-error" role="status"><?= $e($gameModsError) ?></p>
        <?php endif; ?>

        <div class="table-wrap">
          <table id="game-mods" class="table_grid">
            <thead>
              <tr>
                <th data-key="name" class="sortable" data-default="asc">Mod</th>
                <th data-key="ext" class="sortable col-ext" data-default="desc">Extension</th>
                <th class="col-act"><span class="sr-only">Steam</span></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <noscript><p class="windowbg">Activez JavaScript pour afficher le catalogue, ou consultez <a href="?format=json&amp;tab=game">la version JSON</a>.</p></noscript>
        </div>

        <div class="pagesection">
          <span id="g-info" aria-live="polite"></span>
          <nav id="g-pager" class="pager" aria-label="Pagination des mods du jeu"></nav>
        </div>
      </section>

      <!-- ===== Mes extensions ===== -->
      <section id="panel-extensions" class="tab-panel" role="tabpanel" aria-labelledby="tab-extensions" tabindex="0"<?= $tab('extensions') ? '' : ' hidden' ?>>
        <div class="windowbg intro extension-intro">
          <?= $data['intro'] ?>
          <p class="howto">
            <strong>Installation :</strong> téléchargez l’archive voulue, décompressez-la dans un dossier du même nom sous
            <code>RimWorld/Mods</code> (l’archive contient directement le package), puis chargez-le <em>après</em> le mod d’origine.
          </p>
        </div>

        <div class="toolbar windowbg2">
          <label class="search">
            <span>Rechercher</span>
            <input type="search" id="q" placeholder="Nom, ID, package, archive, travail effectué…" autocomplete="off">
          </label>
          <label>
            <span>Type</span>
            <select id="f-type">
              <option value="">Tous</option>
              <option>Traduction</option>
              <option>Correctif</option>
              <option>Mod</option>
            </select>
          </label>
          <label>
            <span>Mod d’origine</span>
            <select id="f-origin">
              <option value="">Toutes</option>
              <option value="workshop">Mod Workshop</option>
              <option value="official">Jeu officiel</option>
              <option value="skoltroy">Mod Skoltroy</option>
              <option value="local">Package local</option>
            </select>
          </label>
          <label>
            <span>Version RW</span>
            <select id="f-rw"><option value="">Toutes</option></select>
          </label>
          <label>
            <span>Archive</span>
            <select id="f-zip">
              <option value="yes" selected>Téléchargeable</option>
              <option value="">Toutes les entrées</option>
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
                <th data-key="date" class="sortable col-date" data-default="desc">Date</th>
                <th data-key="name" class="sortable">Extension</th>
                <th data-key="type" class="sortable col-type">Type</th>
                <th data-key="rw" class="sortable col-rw" data-default="desc">RimWorld</th>
                <th class="col-act"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <noscript><p class="windowbg">Activez JavaScript pour afficher le catalogue, ou consultez <a href="?format=json">la version JSON</a>.</p></noscript>
        </div>

        <div class="pagesection">
          <span id="info" aria-live="polite"></span>
          <nav id="pager" class="pager" aria-label="Pagination des extensions"></nav>
        </div>
      </section>
    </section>

    <?php if (trim($data['footer']) !== ''): ?>
      <section class="roundframe notes windowbg"><?= $data['footer'] ?></section>
    <?php endif; ?>
  </main>

  <footer id="footer_section">
    <p>Sources : <a href="<?= $e($sourceUrl) ?>" target="_blank" rel="noopener">github.com/<?= $e(GH_OWNER) ?>/<?= $e(GH_REPO) ?></a>
      · <a href="?format=json">JSON des extensions</a> · <a href="?format=json&amp;tab=game">JSON des mods du jeu</a>
      · RimWorld est une marque de Ludeon Studios. Site non officiel.</p>
  </footer>
</div>

<div id="ttip" class="ttip" role="tooltip" hidden></div>
<div id="pop" class="pop" role="dialog" aria-label="Détails de l’extension" hidden></div>
<script>window.RW_MODS = <?= $json($mods) ?>; window.RW_GAME = <?= $json($gameMods) ?>;</script>
<script src="<?= $e($v('assets/app.js')) ?>"></script>
</body>
</html>
