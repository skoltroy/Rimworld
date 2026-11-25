<?php
/**
 * Catalogue lu dynamiquement dans Extensions/ReadMe.md du dépôt GitHub skoltroy/SKOLTROY.rimworld.
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
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($body !== false && $code === 200) ? (string)$body : null;
}

/** Retourne ['markdown', 'path', 'fetched', 'stale'] (cache fichier, repli sur la dernière copie). */
function gh_readme(bool $refresh = false): array
{
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/readme.json';
    $cached = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!$refresh && $cached && time() - ($cached['fetched'] ?? 0) < CACHE_TTL) {
        return $cached;
    }
    foreach (GH_README as $name) {
        $path = GH_DIR . '/' . $name;
        $md = gh_get(gh_raw_url($path));
        if ($md !== null) {
            $data = ['markdown' => $md, 'path' => $path, 'fetched' => time(), 'stale' => false];
            @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
            return $data;
        }
    }
    if ($cached) {
        $cached['stale'] = true;
        return $cached;
    }
    return ['markdown' => '', 'path' => GH_DIR . '/' . GH_README[0], 'fetched' => 0, 'stale' => true];
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
            // lien relatif au dossier Extensions du dépôt
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
            'ids'         => array_values(array_unique($idm[0])),
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
