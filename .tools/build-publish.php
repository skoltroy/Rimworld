<?php
declare(strict_types=1);

/**
 * Construit rimworld/.publish/ (rimworld.skoltroy.com) à partir de web/ : CSS, JavaScript et
 * images optimisés. Le cache écrit par le site (cache/) n'est ni publié ni effacé.
 *
 *   php .tools/build-publish.php
 */

ini_set('display_errors', 'stderr');
ini_set('memory_limit', '1G');
require __DIR__ . '/publish-lib.php';

$root = dirname(__DIR__);
$pub = $root . '/.publish';
publish_require(['gd', 'curl'], [$root . '/web/index.php', $root . '/web/lib/github.php']);

publish_version($root . '/web/version.php');
publish_report('rimworld/.publish', publish_tree($root . '/web', $pub, ['#(^|/)(\.gitignore|Thumbs\.db|\.DS_Store)$#'], ['#^cache/#']));
