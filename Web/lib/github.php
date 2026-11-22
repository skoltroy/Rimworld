<?php
/**
 * Lecture dynamique du dossier Extensions du dépôt GitHub skoltroy/Rimworld.
 *
 * - 2 appels à l'API GitHub (branche + arbre récursif), mis en cache CACHE_TTL secondes ;
 * - les About.xml sont lus sur raw.githubusercontent.com (hors quota API), en cache par SHA de dossier ;
 * - les zips sont construits à la demande et mis en cache par SHA de dossier.
 *
 * Jeton optionnel (quota API plus élevé) : variable d'environnement GITHUB_TOKEN
 * ou fichier lib/config.local.php retournant ['token' => '...'].
 */

const GH_OWNER  = 'skoltroy';
const GH_REPO   = 'Rimworld';
const GH_BRANCH = 'main';
const GH_DIR    = 'Extensions';
const CACHE_TTL = 600;
const STEAM_URL = 'https://steamcommunity.com/sharedfiles/filedetails/?id=';

function gh_cache_dir(string $sub = ''): string
{
    $dir = dirname(__DIR__) . '/cache' . ($sub !== '' ? '/' . $sub : '');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function gh_token(): ?string
{
    static $token = false;
    if ($token === false) {
        $token = getenv('GITHUB_TOKEN') ?: null;
        $local = __DIR__ . '/config.local.php';
        if (!$token && is_file($local)) {
            $cfg = include $local;
            $token = is_array($cfg) && !empty($cfg['token']) ? $cfg['token'] : null;
        }
    }
    return $token;
}

function gh_curl(string $url, bool $api)
{
    $ch = curl_init($url);
    $headers = ['User-Agent: rimworld-skoltroy-site'];
    if ($api) {
        $headers[] = 'Accept: application/vnd.github+json';
        if ($t = gh_token()) {
            $headers[] = 'Authorization: Bearer ' . $t;
        }
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_ENCODING       => '',
    ]);
    return $ch;
}

/** GET simple ; lève une exception si le statut n'est pas 200. */
function gh_get(string $url, bool $api = true): string
{
    $ch = gh_curl($url, $api);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code !== 200) {
        throw new RuntimeException("GitHub : HTTP $code $err ($url)");
    }
    return $body;
}

/** GET parallèles ; retourne [clé => contenu|null]. */
function gh_get_many(array $urls, int $concurrency = 16): array
{
    $out = [];
    foreach (array_chunk($urls, $concurrency, true) as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $key => $url) {
            $handles[$key] = gh_curl($url, false);
            curl_multi_add_handle($mh, $handles[$key]);
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $status === CURLM_OK);
        foreach ($handles as $key => $ch) {
            $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            $out[$key] = $ok ? curl_multi_getcontent($ch) : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
    return $out;
}

function gh_raw_url(string $commit, string $path): string
{
    $enc = implode('/', array_map('rawurlencode', explode('/', $path)));
    return 'https://raw.githubusercontent.com/' . GH_OWNER . '/' . GH_REPO . '/' . $commit . '/' . $enc;
}

/**
 * Snapshot du dossier Extensions : ['commit', 'date', 'fetched', 'folders' => [nom => ['sha', 'files' => [chemin relatif => taille]]]].
 * En cas d'échec de GitHub, le dernier cache est réutilisé.
 */
function gh_snapshot(bool $refresh = false): array
{
    $file = gh_cache_dir() . '/snapshot.json';
    $cached = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    if (!$refresh && $cached && time() - ($cached['fetched'] ?? 0) < CACHE_TTL) {
        return $cached;
    }
    try {
        $base = 'https://api.github.com/repos/' . GH_OWNER . '/' . GH_REPO;
        $branch = json_decode(gh_get($base . '/branches/' . GH_BRANCH), true);
        $commit = $branch['commit']['sha'];
        if ($cached && ($cached['commit'] ?? '') === $commit) {
            $cached['fetched'] = time();
            file_put_contents($file, json_encode($cached), LOCK_EX);
            return $cached;
        }
        $tree = json_decode(gh_get($base . '/git/trees/' . $commit . '?recursive=1'), true);

        $folders = [];
        $prefix = GH_DIR . '/';
        foreach ($tree['tree'] as $e) {
            if (!str_starts_with($e['path'], $prefix)) {
                continue;
            }
            $rel = substr($e['path'], strlen($prefix));
            $parts = explode('/', $rel, 2);
            if (count($parts) === 1) {
                if ($e['type'] === 'tree') {
                    $folders[$rel]['sha'] = $e['sha'];
                    $folders[$rel]['files'] ??= [];
                }
                continue;
            }
            if ($e['type'] === 'blob') {
                $folders[$parts[0]]['files'][$parts[1]] = (int)($e['size'] ?? 0);
            }
        }
        $folders = array_filter($folders, fn($f) => !empty($f['sha']) && !empty($f['files']));
        ksort($folders, SORT_NATURAL | SORT_FLAG_CASE);

        $snap = [
            'commit'  => $commit,
            'date'    => $branch['commit']['commit']['committer']['date'] ?? null,
            'fetched' => time(),
            'folders' => $folders,
        ];
        file_put_contents($file, json_encode($snap), LOCK_EX);
        return $snap;
    } catch (Throwable $e) {
        if ($cached) {
            $cached['stale'] = $e->getMessage();
            return $cached;
        }
        throw $e;
    }
}

/** Analyse un About.xml RimWorld. */
function gh_parse_about(?string $xml): array
{
    $about = ['name' => '', 'author' => '', 'packageId' => '', 'modVersion' => '', 'description' => '', 'rw' => [], 'deps' => []];
    if (!$xml) {
        return $about;
    }
    $prev = libxml_use_internal_errors(true);
    $x = simplexml_load_string(preg_replace('/^\xEF\xBB\xBF/', '', $xml), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if (!$x) {
        return $about;
    }
    foreach (['name', 'author', 'packageId', 'modVersion', 'description'] as $k) {
        $about[$k] = trim((string)($x->$k ?? ''));
    }
    foreach ($x->supportedVersions->li ?? [] as $li) {
        $about['rw'][] = trim((string)$li);
    }
    $about['rw'] = array_values(array_unique(array_filter($about['rw'])));
    usort($about['rw'], 'version_compare');
    foreach ($x->modDependencies->li ?? [] as $li) {
        $about['deps'][] = [
            'name' => trim((string)$li->displayName),
            'url'  => trim((string)$li->steamWorkshopUrl),
        ];
    }
    return $about;
}

/** Retourne les métadonnées About.xml de chaque dossier (cache par SHA de dossier). */
function gh_abouts(array $snap): array
{
    $dir = gh_cache_dir('about');
    $result = $missing = [];
    foreach ($snap['folders'] as $name => $f) {
        $file = $dir . '/' . $f['sha'] . '.json';
        if (is_file($file)) {
            $result[$name] = json_decode((string)file_get_contents($file), true);
            continue;
        }
        $aboutPath = null;
        foreach (array_keys($f['files']) as $p) {
            if (strcasecmp($p, 'About/About.xml') === 0) {
                $aboutPath = $p;
                break;
            }
        }
        if ($aboutPath === null) {
            $result[$name] = gh_parse_about(null);
            file_put_contents($file, json_encode($result[$name]));
            continue;
        }
        $missing[$name] = gh_raw_url($snap['commit'], GH_DIR . '/' . $name . '/' . $aboutPath);
    }
    foreach (gh_get_many($missing) as $name => $xml) {
        $result[$name] = gh_parse_about($xml);
        if ($xml !== null) {
            file_put_contents($dir . '/' . $snap['folders'][$name]['sha'] . '.json', json_encode($result[$name]));
        }
    }
    return $result;
}

/** Liste des mods prête pour l'affichage. */
function gh_mods(array $snap): array
{
    $abouts = gh_abouts($snap);
    $mods = [];
    foreach ($snap['folders'] as $folder => $f) {
        $a = $abouts[$folder] ?? gh_parse_about(null);
        $files = $f['files'];

        $ids = [];
        if (preg_match('/^(\d{6,})\b/', $folder, $m)) {
            $ids[] = $m[1];
        }
        foreach ($a['deps'] as $d) {
            if (preg_match('/[?&]id=(\d+)/', $d['url'], $m)) {
                $ids[] = $m[1];
            }
        }
        $ids = array_values(array_unique($ids));

        $count = fn(string $re) => count(array_filter(array_keys($files), fn($p) => preg_match($re, $p)));
        $content = [
            'Keyed'       => $count('#(^|/)Languages/French[^/]*/Keyed/.+\.xml$#i'),
            'DefInjected' => $count('#(^|/)Languages/French[^/]*/DefInjected/.+\.xml$#i'),
            'Patches'     => $count('#(^|/)Patches/.+\.xml$#i'),
            'Defs'        => $count('#(^|/)Defs/.+\.xml$#i'),
            'DLL'         => $count('#(^|/)Assemblies/.+\.dll$#i'),
        ];
        $content = array_filter($content);

        $types = [];
        if (stripos($folder, 'TradFix') !== false || $count('#(^|/)Languages/French#i')) {
            $types[] = 'Traduction';
        }
        if (stripos($folder, 'ModFix') !== false || isset($content['Patches']) || isset($content['DLL']) || isset($content['Defs'])) {
            $types[] = 'Correctif';
        }
        if (!$types) {
            $types[] = 'Autre';
        }

        $target = $a['deps'][0]['name'] ?? '';
        $name = $a['name'] !== '' ? $a['name'] : $folder;

        $mods[] = [
            'folder'      => $folder,
            'name'        => $name,
            'target'      => $target,
            'author'      => $a['author'],
            'packageId'   => $a['packageId'],
            'version'     => $a['modVersion'],
            'rw'          => $a['rw'],
            'description' => $a['description'],
            'ids'         => $ids,
            'types'       => $types,
            'content'     => $content,
            'files'       => count($files),
            'size'        => array_sum($files),
            'github'      => 'https://github.com/' . GH_OWNER . '/' . GH_REPO . '/tree/' . GH_BRANCH . '/'
                             . GH_DIR . '/' . rawurlencode($folder),
            'download'    => 'download.php?mod=' . rawurlencode($folder),
        ];
    }
    return $mods;
}

/** Construit (ou reprend du cache) le zip d'un dossier ; retourne le chemin du fichier. */
function gh_build_zip(array $snap, string $folder): string
{
    $f = $snap['folders'][$folder];
    $dir = gh_cache_dir('zips');
    $zipPath = $dir . '/' . $f['sha'] . '.zip';
    if (is_file($zipPath)) {
        touch($zipPath);
        return $zipPath;
    }

    $urls = [];
    foreach (array_keys($f['files']) as $rel) {
        $urls[$rel] = gh_raw_url($snap['commit'], GH_DIR . '/' . $folder . '/' . $rel);
    }
    $contents = gh_get_many($urls);
    foreach ($contents as $rel => $data) {
        if ($data === null) {
            throw new RuntimeException("Fichier indisponible sur GitHub : $rel");
        }
    }

    $tmp = $zipPath . '.' . getmypid() . '.tmp';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Création du zip impossible.');
    }
    $zip->addEmptyDir($folder);
    ksort($contents, SORT_NATURAL);
    foreach ($contents as $rel => $data) {
        $zip->addFromString($folder . '/' . $rel, $data);
    }
    $zip->close();
    rename($tmp, $zipPath);

    // Purge des zips non utilisés depuis 7 jours
    foreach (glob($dir . '/*.zip') as $old) {
        if (filemtime($old) < time() - 7 * 86400) {
            @unlink($old);
        }
    }
    return $zipPath;
}
