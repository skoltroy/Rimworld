<?php
declare(strict_types=1);

/**
 * Outils communs aux scripts build-publish.php (norme Skoltroy). PHP seul, aucune dépendance à installer :
 * contrôle des prérequis, minification CSS et JavaScript, optimisation des images, copie vers .publish/.
 *
 * La minification est volontairement prudente : commentaires et espaces superflus retirés,
 * chaînes, gabarits et expressions régulières intacts, retours à la ligne du JavaScript conservés.
 */

/** Arrête la construction, avant toute écriture, si une dépendance manque. */
function publish_require(array $extensions, array $files = []): void
{
    $missing = [];
    if (PHP_VERSION_ID < 80100) {
        $missing[] = 'PHP 8.1 ou plus récent (version actuelle : ' . PHP_VERSION . ')';
    }
    foreach ($extensions as $extension) {
        if (!extension_loaded($extension)) {
            $missing[] = "extension PHP « $extension » (à activer dans php.ini)";
        }
    }
    foreach ($files as $file) {
        if (!file_exists($file)) {
            $missing[] = "fichier ou dossier absent : $file";
        }
    }
    if ($missing) {
        fwrite(STDERR, "Publication impossible, dépendances manquantes :\n  - " . implode("\n  - ", $missing) . "\n");
        exit(1);
    }
}

/**
 * Numérote une publication et écrit le fichier lu par le pied de page de l'administration.
 * Version « vAA.MM.JJNN » : année, mois et jour de la construction (heure de Paris), puis rang de la
 * publication dans la journée, sur deux chiffres (v26.11.2601, puis v26.11.2602 ; le lendemain v26.11.2701).
 *
 * Le fichier est rangé dans les sources, publié avec elles, et sert de compteur : il est à commiter.
 * La variable d'environnement PUBLISH_SANS_VERSION=1 (tâche « build ») laisse la version inchangée.
 */
function publish_version(string $file): void
{
    $previous = is_file($file) ? (array) include $file : [];
    if (getenv('PUBLISH_SANS_VERSION') === '1' && $previous) {
        echo 'Version inchangée : ' . ($previous['version'] ?? '?') . "\n";
        return;
    }
    $prefix = 'v' . (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('y.m.d');
    $rank = preg_match('/\A' . preg_quote($prefix, '/') . '(\d{2})\z/', (string) ($previous['version'] ?? ''), $m)
        ? (int) $m[1] + 1
        : 1;
    if ($rank > 99) {
        fwrite(STDERR, "Publication impossible : 99 versions ont déjà été publiées aujourd'hui.\n");
        exit(1);
    }

    // Adresse GitHub du dépôt « origin », sans identifiants ni suffixe .git.
    $output = [];
    exec('git -C ' . escapeshellarg(dirname($file)) . ' remote get-url origin', $output, $code);
    $repository = $code === 0 && preg_match('~github\.com[:/]+([\w.-]+/[\w.-]+?)(?:\.git)?/?\s*$~', $output[0] ?? '', $m)
        ? 'https://github.com/' . $m[1]
        : (string) ($previous['depot'] ?? '');
    if ($repository === '') {
        fwrite(STDERR, "Publication impossible : adresse GitHub du dépôt introuvable (git remote « origin »).\n");
        exit(1);
    }

    $version = $prefix . sprintf('%02d', $rank);
    publish_write($file, "<?php\n// Écrit à chaque publication (publish_version) : ne pas modifier à la main.\n"
        . 'return ' . var_export(['version' => $version, 'depot' => $repository], true) . ";\n");
    echo "Version $version ($repository)\n";
}

function publish_minify_css(string $css): string
{
    $out = '';
    $space = false;
    $length = strlen($css);
    for ($i = 0; $i < $length; $i++) {
        $c = $css[$i];
        if ($c === '/' && ($css[$i + 1] ?? '') === '*') {
            $end = strpos($css, '*/', $i + 2);
            $end = $end === false ? $length : $end + 2;
            if (($css[$i + 2] ?? '') === '!') {          // licence : conservée
                $out .= substr($css, $i, $end - $i) . "\n";
                $space = false;
            } else {
                $space = true;
            }
            $i = $end - 1;
            continue;
        }
        if (ctype_space($c)) {
            $space = true;
            continue;
        }
        if ($space) {
            $space = false;
            $previous = $out === '' ? '' : $out[strlen($out) - 1];
            if ($previous !== '' && !str_contains("{};,\n", $previous) && !str_contains('{};,', $c)) {
                $out .= ' ';
            }
        }
        if ($c === '"' || $c === "'") {
            for ($j = $i + 1; $j < $length && $css[$j] !== $c && $css[$j] !== "\n"; $j++) {
                if ($css[$j] === '\\') {
                    $j++;
                }
            }
            $out .= substr($css, $i, $j - $i + 1);
            $i = $j;
            continue;
        }
        if ($c === '\\') {                               // caractère échappé : repris tel quel
            $out .= $c . ($css[$i + 1] ?? '');
            $i++;
            continue;
        }
        // url(…) sans guillemets : contenu littéral (peut contenir « /* » ou des points-virgules).
        if (($c === 'u' || $c === 'U') && preg_match('~\Gurl\(\s*+(?!["\'])[^)]*\)~i', $css, $m, 0, $i)) {
            $out .= $m[0];
            $i += strlen($m[0]) - 1;
            continue;
        }
        if ($c === '}' && $out !== '' && $out[strlen($out) - 1] === ';') {
            $out = substr($out, 0, -1);
        }
        $out .= $c;
    }
    return $out;
}

function publish_minify_js(string $js): string
{
    $out = '';
    $pending = '';                                       // '', ' ' ou "\n"
    $length = strlen($js);
    $templates = [];                                     // profondeur d'accolades de chaque ${ … } ouvert
    $emit = static function (string $token) use (&$out, &$pending): void {
        if ($pending !== '' && $out !== '' && $out[strlen($out) - 1] !== "\n") {
            $out .= $pending;
        }
        $pending = '';
        $out .= $token;
    };
    // Lit un gabarit à partir de $i (après ` ou }) ; renvoie la position du dernier caractère lu.
    $template = static function (int $i) use ($js, $length, &$out, &$templates): int {
        for (; $i < $length; $i++) {
            $c = $js[$i];
            if ($c === '\\') {
                $out .= $c . ($js[$i + 1] ?? '');
                $i++;
                continue;
            }
            $out .= $c;
            if ($c === '`') {
                return $i;
            }
            if ($c === '$' && ($js[$i + 1] ?? '') === '{') {
                $out .= '{';
                $templates[] = 0;
                return $i + 1;
            }
        }
        return $i;
    };
    $regexAllowed = static function () use (&$out): bool {
        $tail = rtrim(substr($out, -40));
        if ($tail === '') {
            return true;
        }
        if (str_ends_with($tail, '++') || str_ends_with($tail, '--')) {
            return false;
        }
        if (str_contains('(,=:[!&|?{};+-*%<>^~/}', $tail[strlen($tail) - 1])) {
            return true;
        }
        return (bool) preg_match('~(?<![\w$.])(return|typeof|instanceof|case|delete|void|throw|new|in|of|else|do|yield|await)$~', $tail);
    };

    for ($i = 0; $i < $length; $i++) {
        $c = $js[$i];
        $next = $js[$i + 1] ?? '';
        if ($c === '/' && $next === '/') {
            $end = strpos($js, "\n", $i);
            $i = ($end === false ? $length : $end) - 1;
            continue;
        }
        if ($c === '/' && $next === '*') {
            $end = strpos($js, '*/', $i + 2);
            $end = $end === false ? $length : $end + 2;
            $comment = substr($js, $i, $end - $i);
            if (($js[$i + 2] ?? '') === '!') {
                $emit($comment);
                $pending = "\n";
            } elseif (str_contains($comment, "\n")) {
                $pending = "\n";
            } elseif ($pending === '') {
                $pending = ' ';
            }
            $i = $end - 1;
            continue;
        }
        if ($c === "\n" || $c === "\r") {
            $pending = "\n";
            continue;
        }
        if ($c === ' ' || $c === "\t" || $c === "\f" || $c === "\v") {
            if ($pending === '') {
                $pending = ' ';
            }
            continue;
        }
        if ($c === '"' || $c === "'") {
            for ($j = $i + 1; $j < $length && $js[$j] !== $c && $js[$j] !== "\n"; $j++) {
                if ($js[$j] === '\\') {
                    $j += ($js[$j + 1] ?? '') === "\r" && ($js[$j + 2] ?? '') === "\n" ? 2 : 1;
                }
            }
            $emit(substr($js, $i, $j - $i + 1));
            $i = $j;
            continue;
        }
        if ($c === '`') {
            $emit('`');
            $i = $template($i + 1);
            continue;
        }
        if ($c === '{' && $templates) {
            $templates[count($templates) - 1]++;
        }
        if ($c === '}' && $templates) {
            if ($templates[count($templates) - 1] === 0) {
                array_pop($templates);
                $emit('}');
                $i = $template($i + 1);
                continue;
            }
            $templates[count($templates) - 1]--;
        }
        if ($c === '/' && $regexAllowed()) {
            $class = false;
            for ($j = $i + 1; $j < $length && $js[$j] !== "\n"; $j++) {
                if ($js[$j] === '\\') {
                    $j++;
                } elseif ($js[$j] === '[') {
                    $class = true;
                } elseif ($js[$j] === ']') {
                    $class = false;
                } elseif ($js[$j] === '/' && !$class) {
                    break;
                }
            }
            $emit(substr($js, $i, $j - $i + 1));
            $i = $j;
            continue;
        }
        $emit($c);
    }
    return $out;
}

/** Recompresse un PNG (sans perte) ou un JPEG (qualité 85) ; rend l'original s'il n'y a rien à gagner. */
function publish_optimize_image(string $extension, string $data): string
{
    $extension = strtolower($extension);
    if ($extension === 'png') {
        if (str_contains($data, 'acTL')) {               // PNG animé : GD perdrait l'animation
            return $data;
        }
        $limit = 1.0;
    } elseif ($extension === 'jpg' || $extension === 'jpeg') {
        // Orientation EXIF, profil couleur et CMJN seraient perdus : fichiers laissés tels quels.
        $head = substr($data, 0, 131072);
        $info = @getimagesizefromstring($data);
        if (str_contains($head, "Exif\0") || str_contains($head, 'ICC_PROFILE') || ($info['channels'] ?? 3) === 4) {
            return $data;
        }
        $limit = 0.9;
    } else {
        return $data;
    }
    $image = @imagecreatefromstring($data);
    if (!$image) {
        return $data;
    }
    ob_start();
    if ($extension === 'png') {
        imagesavealpha($image, true);
        imagepng($image, null, 9);
    } else {
        imagejpeg($image, null, 85);
    }
    $out = (string) ob_get_clean();
    return $out !== '' && strlen($out) < strlen($data) * $limit ? $out : $data;
}

/** Contenu optimisé d'un fichier d'après son extension. Les fichiers « .min. » sont repris tels quels. */
function publish_optimize(string $path, string $data): string
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (str_contains(strtolower(basename($path)), '.min.')) {
        return $data;
    }
    return match ($extension) {
        'css' => publish_minify_css($data),
        'js', 'mjs' => publish_minify_js($data),
        'png', 'jpg', 'jpeg' => publish_optimize_image($extension, $data),
        default => $data,
    };
}

/** @return list<string> chemins relatifs (séparateur « / ») de tous les fichiers d'un dossier */
function publish_list(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $item) {
        if ($item->isFile() && !$item->isLink()) {
            $files[] = str_replace('\\', '/', substr($item->getPathname(), strlen($directory) + 1));
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

/**
 * Copie optimisée d'un dossier. Les fichiers de destination absents de la source sont supprimés,
 * sauf ceux désignés par $keep (données écrites par le site en ligne).
 *
 * @param list<string> $exclude expressions régulières : chemins source jamais publiés
 * @param list<string> $keep    expressions régulières : chemins de destination jamais touchés
 * @return array{files:int, before:int, after:int}
 */
function publish_tree(string $source, string $destination, array $exclude = [], array $keep = []): array
{
    $matches = static function (string $path, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        return false;
    };
    $stats = ['files' => 0, 'before' => 0, 'after' => 0];
    $wanted = [];
    foreach (publish_list($source) as $relative) {
        if ($matches($relative, $exclude) || $matches($relative, $keep)) {
            continue;
        }
        $data = (string) file_get_contents("$source/$relative");
        $optimized = publish_optimize($relative, $data);
        publish_write("$destination/$relative", $optimized);
        $wanted[$relative] = true;
        $stats['files']++;
        $stats['before'] += strlen($data);
        $stats['after'] += strlen($optimized);
    }
    foreach (publish_list($destination) as $relative) {
        if (!isset($wanted[$relative]) && !$matches($relative, $keep)) {
            unlink("$destination/$relative");
        }
    }
    publish_prune($destination);
    return $stats;
}

function publish_write(string $file, string $data): void
{
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Impossible de créer le dossier : $directory");
    }
    if (file_put_contents($file, $data) === false) {
        throw new RuntimeException("Impossible d'écrire : $file");
    }
}

/** Supprime les sous-dossiers vides. */
function publish_prune(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $item) {
        if ($item->isDir() && !$item->isLink()) {
            publish_prune($item->getPathname());
            @rmdir($item->getPathname());                // échoue sans effet si le dossier n'est pas vide
        }
    }
}

/** @param array{files:int, before:int, after:int} $stats */
function publish_report(string $label, array $stats): void
{
    printf("%s : %d fichiers, %.1f Mo (%.0f Ko gagnés)\n", $label, $stats['files'], $stats['after'] / 1048576, ($stats['before'] - $stats['after']) / 1024);
}
