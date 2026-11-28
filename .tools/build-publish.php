<?php
declare(strict_types=1);

/**
 * Construit rimworld/.publish/ (rimworld.skoltroy.com) à partir de web/ : CSS, JavaScript et
 * images optimisés, plus l'inventaire local des mods Workshop. Le cache écrit par le site (cache/)
 * n'est ni publié ni effacé.
 *
 *   php .tools/build-publish.php
 */

ini_set('display_errors', 'stderr');
ini_set('memory_limit', '1G');
require __DIR__ . '/publish-lib.php';

$root = dirname(__DIR__);
$pub = $root . '/.publish';
publish_require(['gd', 'curl', 'dom'], [$root . '/web/index.php', $root . '/web/lib/github.php']);
require $root . '/web/lib/github.php';

// Inventaire du jeu installé (contenu officiel de RimWorld/Data + mods Workshop de RimWorld/Mods)
$gameMods = array_map(
    static fn(array $mod): array => ['id' => $mod['id'], 'name' => $mod['name'], 'kind' => $mod['kind']],
    array_merge(rw_scan_official(RW_GAME_DATA_PATH), rw_scan_game_mods(RW_GAME_MODS_PATH))
);
$manifest = json_encode($gameMods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($manifest === false) {
    throw new RuntimeException('Impossible d’encoder l’inventaire des mods RimWorld : ' . json_last_error_msg());
}

publish_version($root . '/web/version.php');
publish_report('rimworld/.publish', publish_tree($root . '/web', $pub, ['#(^|/)(\.gitignore|Thumbs\.db|\.DS_Store)$#'], ['#^cache/#']));

$manifestPath = $pub . DIRECTORY_SEPARATOR . 'game-mods.json';
if (file_put_contents($manifestPath, $manifest . PHP_EOL, LOCK_EX) === false) {
    throw new RuntimeException("Impossible d’écrire l’inventaire de publication : $manifestPath");
}
