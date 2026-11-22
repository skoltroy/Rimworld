<?php
declare(strict_types=1);
require __DIR__ . '/lib/github.php';

$folder = (string)($_GET['mod'] ?? '');

try {
    $snap = gh_snapshot();
    if ($folder === '' || !isset($snap['folders'][$folder])) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Mod introuvable dans " . GH_DIR . " : $folder";
        exit;
    }
    @set_time_limit(120);
    $zip = gh_build_zip($snap, $folder);
} catch (Throwable $e) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Téléchargement impossible pour le moment : " . $e->getMessage();
    exit;
}

$name = $folder . '.zip';
$ascii = preg_replace('/[^A-Za-z0-9 ._()\[\]-]/', '_', $name);
header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zip));
header("Content-Disposition: attachment; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($name));
header('Cache-Control: public, max-age=600');
header('X-Content-Type-Options: nosniff');
readfile($zip);
