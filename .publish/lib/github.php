<?php
/**
 * Catalogue lu dynamiquement dans extensions/README.md du dépôt GitHub skoltroy/SKOLTROY.rimworld,
 * relié à l'inventaire du jeu installé (contenu officiel de RimWorld/Data et mods Workshop de RimWorld/Mods).
 *
 * - le ReadMe est lu sur raw.githubusercontent.com (hors quota API) et mis en cache CACHE_TTL secondes ;
 * - si GitHub ne répond pas, la dernière copie en cache est réutilisée ;
 * - les liens « Télécharger » pointent directement vers les archives ZIP du dépôt (colonne « Nom du ZIP »).
 */

const GH_OWNER  = 'skoltroy';
const GH_REPO   = 'SKOLTROY.rimworld';
const GH_BRANCH = 'main';
const GH_DIR    = 'extensions';
const GH_README = ['README.md', 'ReadMe.md']; // nom suivi par Git : README.md
const CACHE_TTL = 300;
const RW_GAME_MODS_PATH = 'D:\\Games\\RimWorld\\Mods';
const RW_GAME_DATA_PATH = 'D:\\Games\\RimWorld\\Data';

/** Contenu officiel Ludeon (dossiers de RimWorld/Data) : nom affiché et identifiant d'application Steam. */
const RW_OFFICIAL = [
    'Core'     => ['name' => 'RimWorld (jeu de base)', 'appId' => '294100'],
    'Royalty'  => ['name' => 'RimWorld – Royalty', 'appId' => '1149640'],
    'Ideology' => ['name' => 'RimWorld – Ideology', 'appId' => '1392840'],
    'Biotech'  => ['name' => 'RimWorld – Biotech', 'appId' => '1826140'],
    'Anomaly'  => ['name' => 'RimWorld – Anomaly', 'appId' => '2380740'],
    'Odyssey'  => ['name' => 'RimWorld – Odyssey', 'appId' => '3022790'],
];

function rw_official_entry(string $key): array
{
    if (!isset(RW_OFFICIAL[$key])) {
        throw new InvalidArgumentException("Contenu officiel inconnu : $key");
    }
    return [
        'id'   => $key,
        'name' => RW_OFFICIAL[$key]['name'],
        'url'  => 'https://store.steampowered.com/app/' . RW_OFFICIAL[$key]['appId'] . '/',
        'kind' => 'official',
    ];
}

/** Contenu officiel installé (jeu de base + DLC), dans l'ordre de sortie. */
function rw_scan_official(string $dataRoot): array
{
    $out = [];
    foreach (array_keys(RW_OFFICIAL) as $key) {
        if (is_dir($dataRoot . DIRECTORY_SEPARATOR . $key)) {
            $out[] = rw_official_entry($key);
        }
    }
    return $out;
}

function rw_steam_url(string $id): string
{
    if (!preg_match('/^\d+$/D', $id)) {
        throw new InvalidArgumentException('Un identifiant Steam Workshop doit être numérique.');
    }

    return 'https://steamcommunity.com/sharedfiles/filedetails/?id=' . rawurlencode($id);
}

/** Inventorie les dossiers Workshop numériques ; les packages locaux/extensions sont exclus. */
function rw_scan_game_mods(string $modsRoot): array
{
    if (!is_dir($modsRoot)) {
        throw new RuntimeException("Dossier des mods RimWorld introuvable : $modsRoot");
    }

    $mods = [];
    foreach (new DirectoryIterator($modsRoot) as $directory) {
        $folder = $directory->getFilename();
        if ($directory->isDot() || !$directory->isDir() || !preg_match('/^\d+$/D', $folder)) {
            continue;
        }

        $id = $folder;
        $publishedIdPath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'About' . DIRECTORY_SEPARATOR . 'PublishedFileId.txt';
        if (is_file($publishedIdPath)) {
            $publishedId = trim((string) file_get_contents($publishedIdPath));
            if ($publishedId !== '' && preg_match('/^\d+$/D', $publishedId)) {
                $id = $publishedId;
            } elseif ($publishedId !== '') {
                error_log("PublishedFileId invalide dans $publishedIdPath");
            }
        }

        $name = $folder;
        $aboutPath = $directory->getPathname() . DIRECTORY_SEPARATOR . 'About' . DIRECTORY_SEPARATOR . 'About.xml';
        if (is_file($aboutPath)) {
            $document = new DOMDocument();
            $document->resolveExternals = false;
            $document->substituteEntities = false;
            $previousLibxmlState = libxml_use_internal_errors(true);
            $loaded = $document->load($aboutPath, LIBXML_NONET);
            $parseErrors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlState);

            if ($loaded) {
                $nameNode = $document->getElementsByTagName('name')->item(0);
                if ($nameNode && trim($nameNode->textContent) !== '') {
                    $name = trim($nameNode->textContent);
                }
            } else {
                $detail = $parseErrors ? trim($parseErrors[0]->message) : 'XML illisible';
                error_log("About.xml invalide dans $aboutPath : $detail");
            }
        }

        $mods[] = [
            'id' => $id,
            'name' => $name,
            'url' => rw_steam_url($id),
            'kind' => 'workshop',
        ];
    }

    usort($mods, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']) ?: strcmp($a['id'], $b['id']));
    return $mods;
}

/**
 * Contenu officiel puis mods Workshop : inventaire local en développement,
 * inventaire embarqué (game-mods.json) en production.
 */
function rw_game_mods(): array
{
    $configuredRoot = getenv('RIMWORLD_MODS_PATH');
    $modsRoot = $configuredRoot !== false && $configuredRoot !== '' ? $configuredRoot : RW_GAME_MODS_PATH;
    if (is_dir($modsRoot)) {
        return array_merge(rw_scan_official(dirname($modsRoot) . DIRECTORY_SEPARATOR . 'Data'), rw_scan_game_mods($modsRoot));
    }

    $manifestPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'game-mods.json';
    if (!is_file($manifestPath)) {
        throw new RuntimeException('Inventaire des mods indisponible : reconstruisez la publication depuis le dossier RimWorld/Mods.');
    }

    $json = file_get_contents($manifestPath);
    if ($json === false) {
        throw new RuntimeException("Impossible de lire l'inventaire : $manifestPath");
    }

    $manifest = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($manifest)) {
        throw new RuntimeException("Inventaire JSON invalide : $manifestPath (" . json_last_error_msg() . ')');
    }

    $official = $workshop = [];
    foreach ($manifest as $entry) {
        $kind = is_array($entry) ? ($entry['kind'] ?? 'workshop') : null;
        if ($kind === 'official' && is_string($entry['id'] ?? null) && isset(RW_OFFICIAL[$entry['id']])) {
            $official[] = rw_official_entry($entry['id']);
            continue;
        }
        if (
            $kind !== 'workshop'
            || !isset($entry['id'], $entry['name'])
            || !is_string($entry['id'])
            || !preg_match('/^\d+$/D', $entry['id'])
            || !is_string($entry['name'])
        ) {
            throw new RuntimeException("Entrée invalide dans l'inventaire : $manifestPath");
        }
        $workshop[] = [
            'id' => $entry['id'],
            'name' => $entry['name'],
            'url' => rw_steam_url($entry['id']),
            'kind' => 'workshop',
        ];
    }

    return array_merge($official, $workshop);
}

/**
 * Relie les mods du jeu et les extensions dans les deux sens.
 *
 * - chaque extension reçoit `origin` (workshop, official, skoltroy, local) et `targets` (mods d'origine) ;
 * - chaque mod du jeu reçoit `ext` (extensions téléchargeables) et `examined` (examiné sans package publié),
 *   sous forme d'index dans la liste des extensions.
 */
function rw_link_catalog(array &$gameMods, array &$mods): void
{
    $byId = [];
    foreach ($gameMods as $i => &$game) {
        $game['ext'] = [];
        $game['examined'] = [];
        $byId[$game['id']] = $i;
    }
    unset($game);

    $dlc = array_values(array_diff(array_keys(RW_OFFICIAL), ['Core']));
    foreach ($mods as $i => &$mod) {
        $targets = [];
        if ($mod['ids']) {
            $mod['origin'] = 'workshop';
            $targets = $mod['ids'];
        } elseif (preg_match('/^(' . implode('|', array_keys(RW_OFFICIAL)) . ')\b/i', $mod['package'] . ' ' . $mod['name'], $m)) {
            $mod['origin'] = 'official';
            $targets = [ucfirst(strtolower($m[1]))];
        } elseif (preg_match('/^DLC\b/i', $mod['package'] ?: $mod['name'])) {
            $mod['origin'] = 'official';
            $targets = $dlc;
        } elseif (preg_match('/Skoltroy/i', $mod['package'] . ' ' . $mod['name'] . ' ' . $mod['zipText'])) {
            $mod['origin'] = 'skoltroy';
        } else {
            $mod['origin'] = 'local';
        }

        $mod['targets'] = [];
        foreach ($targets as $id) {
            $installed = isset($byId[$id]);
            if ($installed) {
                $game = $gameMods[$byId[$id]];
            } elseif (isset(RW_OFFICIAL[$id])) {
                $game = rw_official_entry($id);
            } else {
                $game = ['id' => $id, 'name' => '', 'url' => rw_steam_url($id), 'kind' => 'workshop'];
            }
            $mod['targets'][] = $game + ['installed' => $installed];
            if ($installed) {
                $gameMods[$byId[$id]][$mod['zips'] ? 'ext' : 'examined'][] = $i;
            }
        }
        // Champs internes inutiles côté client
        foreach ($mod['targets'] as &$t) {
            unset($t['ext'], $t['examined']);
        }
        unset($t);
    }
    unset($mod);
}

function gh_raw_url(string $path): string
{
    $enc = implode('/', array_map('rawurlencode', explode('/', $path)));
    return 'https://raw.githubusercontent.com/' . GH_OWNER . '/' . GH_REPO . '/' . GH_BRANCH . '/' . $enc;
}

function gh_blob_url(string $path): string
{
    $enc = implode('/', array_map('rawurlencode', explode('/', $path)));
    return 'https://github.com/' . GH_OWNER . '/' . GH_REPO . '/blob/' . GH_BRANCH . '/' . $enc;
}

/** GET simple ; retourne null si le statut n'est pas 200. */
function gh_get(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['User-Agent: rimworld-catalogue'],
        CURLOPT_ENCODING       => '',
    ]);
    // Développement Windows : PHP souvent sans certificats racines (curl.cainfo vide) → bundle de Git for Windows
    $ca = getenv('CURL_CA_BUNDLE') ?: '';
    if ($ca === '' && PHP_OS_FAMILY === 'Windows' && !ini_get('curl.cainfo')) {
        $ca = 'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt';
    }
    if ($ca !== '' && is_file($ca)) {
        curl_setopt($ch, CURLOPT_CAINFO, $ca);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($body === false || $code !== 200) {
        error_log("GitHub : échec de lecture de $url (HTTP $code" . ($body === false ? ', ' . curl_error($ch) : '') . ')');
    }
    curl_close($ch);
    return ($body !== false && $code === 200) ? (string)$body : null;
}

/**
 * Retourne ['markdown', 'path', 'fetched', 'stale', 'local'] ; `stale` = GitHub injoignable au dernier essai.
 *
 * GitHub est interrogé au plus une fois par CACHE_TTL (succès ou échec), avec repli sur la dernière copie.
 * En développement (dépôt Git présent), le contenu affiché est celui du README local extensions/README.md,
 * mais l'accès à GitHub est tout de même vérifié pour l'indicateur d'état.
 */
function gh_readme(bool $refresh = false): array
{
    $data = gh_readme_remote($refresh);
    foreach (GH_README as $name) {
        $local = dirname(__DIR__, 2) . '/' . GH_DIR . '/' . $name;
        if (is_file($local) && is_dir(dirname(__DIR__, 2) . '/.git')) {
            return ['markdown' => (string)file_get_contents($local), 'path' => GH_DIR . '/' . $name, 'local' => true] + $data;
        }
    }
    return $data;
}

function gh_readme_remote(bool $refresh): array
{
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/readme.json';
    $cached = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    $cached = is_array($cached) ? $cached : ['markdown' => '', 'path' => GH_DIR . '/' . GH_README[0], 'fetched' => 0, 'stale' => true];
    $checked = $cached['checked'] ?? $cached['fetched'] ?? 0;
    if (!$refresh && time() - $checked < CACHE_TTL) {
        return $cached + ['local' => false];
    }
    $data = null;
    foreach (GH_README as $name) {
        $path = GH_DIR . '/' . $name;
        $md = gh_get(gh_raw_url($path));
        if ($md !== null) {
            $data = ['markdown' => $md, 'path' => $path, 'fetched' => time(), 'stale' => false];
            break;
        }
    }
    // En cas d'échec, on garde la dernière copie et on note l'essai pour ne pas réinterroger GitHub à chaque visite.
    $data = ($data ?? array_merge($cached, ['stale' => true])) + ['checked' => time()];
    $data['checked'] = time();
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $data + ['local' => false];
}

/* ---------- Markdown ---------- */

function md_inline(string $s): string
{
    $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $s = preg_replace('#&lt;br\s*/?&gt;#i', '<br>', $s);
    $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function ($m) {
        $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
        if (!preg_match('#^https?://#i', $url)) {
            // lien relatif au dossier extensions du dépôt
            $url = gh_blob_url(GH_DIR . '/' . ltrim($url, './'));
        }
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
    }, $s);
    return $s;
}

function md_text(string $s): string
{
    $s = preg_replace('#<br\s*/?>#i', "\n", $s);
    $s = str_replace(['`', '**'], '', $s);
    return trim(preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $s));
}

/** Rendu simple : titres, listes, paragraphes. */
function md_blocks(array $lines): string
{
    $html = '';
    $para = [];
    $list = [];
    $flush = static function () use (&$html, &$para, &$list) {
        if ($para) {
            $html .= '<p>' . md_inline(implode(' ', $para)) . "</p>\n";
            $para = [];
        }
        if ($list) {
            $html .= '<ul>' . implode('', array_map(fn($li) => '<li>' . md_inline($li) . '</li>', $list)) . "</ul>\n";
            $list = [];
        }
    };
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') {
            $flush();
        } elseif (preg_match('/^(#{2,6})\s+(.*)$/', $t, $m)) {
            $flush();
            $lvl = min(6, strlen($m[1]) + 1);
            $html .= "<h$lvl>" . md_inline($m[2]) . "</h$lvl>\n";
        } elseif (preg_match('/^[-*]\s+(.*)$/', $t, $m)) {
            if ($para) {
                $flush();
            }
            $list[] = $m[1];
        } else {
            if ($list) {
                $flush();
            }
            $para[] = $t;
        }
    }
    $flush();
    return $html;
}

function md_cells(string $line): array
{
    $line = trim($line);
    $line = preg_replace('/^\||\|$/', '', $line);
    $cells = preg_split('/(?<!\\\\)\|/', $line);
    return array_map(fn($c) => trim(str_replace('\|', '|', $c)), $cells);
}

/* ---------- Catalogue ---------- */

/** « RW 1.4–1.7 », « (RW 1.0, 1.1, 1.6) » → liste triée de versions. */
function rw_versions(string $s): array
{
    $out = [];
    if (preg_match_all('/RW\s+([0-9.,\s–\-]+)/u', $s, $mm)) {
        foreach ($mm[1] as $chunk) {
            foreach (preg_split('/\s*,\s*/', trim($chunk, " ,")) as $part) {
                if (preg_match('/^(\d+)\.(\d+)\s*[–-]\s*(\d+)\.(\d+)$/u', $part, $r) && $r[1] === $r[3]) {
                    for ($i = (int)$r[2]; $i <= (int)$r[4]; $i++) {
                        $out[] = $r[1] . '.' . $i;
                    }
                } elseif (preg_match('/^\d+\.\d+$/', $part)) {
                    $out[] = $part;
                }
            }
        }
    }
    $out = array_values(array_unique($out));
    usort($out, 'version_compare');
    return $out;
}

/** Types déduits uniquement des mots-clés du nom du package / de l'archive : TradFix, ModFix, Mod (mot seul). */
function rw_types(string $names): array
{
    $types = [];
    if (preg_match('/(?<![A-Za-z])TradFix(?![A-Za-z])/i', $names)) {
        $types[] = 'Traduction';
    }
    if (preg_match('/(?<![A-Za-z])ModFix(?![A-Za-z])/i', $names)) {
        $types[] = 'Correctif';
    }
    if (preg_match('/(?<![A-Za-z])Mod(?![A-Za-z])/', $names)) { // mot « Mod » seul : ni « ModFix », ni « Model »
        $types[] = 'Mod';
    }
    return $types;
}

/** Analyse le ReadMe : ['title', 'intro', 'footer', 'columns', 'mods']. */
function rw_parse_readme(string $md): array
{
    // Séparateurs explicites : \R sans /u couperait sur l'octet 0x85 des caractères UTF-8 (ex. « 具 »)
    $lines = preg_split('/\r\n|\n|\r/', preg_replace('/^\xEF\xBB\xBF/', '', $md));
    $title = 'Extensions de mods RimWorld';
    $before = $after = $rows = [];
    $columns = [];
    $state = 'before';
    foreach ($lines as $line) {
        $t = trim($line);
        if ($state === 'before' && preg_match('/^#\s+(.*)$/', $t, $m)) {
            $title = $m[1];
            continue;
        }
        $isRow = str_starts_with($t, '|');
        if ($state === 'before' && $isRow) {
            $columns = md_cells($t);
            $state = 'sep';
            continue;
        }
        if ($state === 'sep') {
            $state = 'table';
            if (preg_match('/^\|?\s*:?-{3,}/', $t)) {
                continue;
            }
        }
        if ($state === 'table') {
            if ($isRow) {
                $rows[] = md_cells($t);
                continue;
            }
            $state = 'after';
        }
        if ($state === 'before') {
            $before[] = $line;
        } else {
            $after[] = $line;
        }
    }

    // Repérage des colonnes par leur intitulé (robuste à un réordonnancement du ReadMe)
    $find = static function (string $re, int $default) use ($columns): int {
        foreach ($columns as $i => $c) {
            if (preg_match($re, md_text($c))) {
                return $i;
            }
        }
        return $default;
    };
    $col = [
        'date'    => $find('/^date/iu', 0),
        'id'      => $find('/^id\b/iu', 1),
        'package' => $find('/package|archive/iu', 2),
        'zip'     => $find('/zip/iu', 3),
        'name'    => $find('/^mod\b|name/iu', 4),
        'version' => $find('/version/iu', 5),
        'work'    => $find('/travail/iu', 6),
    ];

    $mods = [];
    foreach ($rows as $n => $cells) {
        $get = static fn(string $k) => $cells[$col[$k]] ?? '';
        $date = $get('date');
        $dateIso = preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $d) ? "$d[3]-$d[2]-$d[1]" : null;

        preg_match_all('/\b\d{6,}\b/', $get('id'), $idm);
        $ids = array_values(array_unique($idm[0]));
        $steamLinks = array_map(
            static fn(string $id): array => ['id' => $id, 'url' => rw_steam_url($id)],
            $ids
        );
        $zips = [];
        preg_match_all('/`([^`]+\.zip)`/i', $get('zip'), $zm);
        foreach ($zm[1] as $z) {
            $z = trim($z);
            if ($z !== '' && !str_contains($z, '*') && !str_contains($z, '/')) {
                $zips[] = ['name' => $z, 'url' => gh_raw_url(GH_DIR . '/' . $z)];
            }
        }

        $work = $get('work');
        $mods[] = [
            'n'           => $n,
            'date'        => $dateIso ? $date : '',
            'dateIso'     => $dateIso,
            'ids'         => $ids,
            'steamLinks'  => $steamLinks,
            'idHtml'      => md_inline($get('id')),
            'name'        => md_text($get('name')),
            'nameHtml'    => md_inline($get('name')),
            'package'     => md_text($get('package')),
            'packageHtml' => md_inline($get('package')),
            'zipText'     => md_text($get('zip')),
            'zips'        => $zips,
            'version'     => md_text($get('version')),
            'versionHtml' => md_inline($get('version')),
            'rw'          => rw_versions(md_text($get('version'))),
            'work'        => md_text($work),
            'workHtml'    => md_inline($work),
            'types'       => rw_types(md_text($get('package')) . ' ' . md_text($get('zip'))),
        ];
    }

    return [
        'title'   => $title,
        'intro'   => md_blocks($before),
        'footer'  => md_blocks($after),
        'columns' => array_map('md_text', $columns),
        'mods'    => $mods,
    ];
}
