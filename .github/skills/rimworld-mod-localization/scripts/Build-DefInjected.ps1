# Construit les fichiers DefInjected à partir d'une source extraite et de traductions alignées.
#
# Entrées :
#   -SourceTsv      : sortie de Extract-DefTranslatables.ps1 (DefType<TAB>clé<TAB>anglais)
#   -TranslationDir : dossier contenant des fichiers *.tsv de traduction (clé<TAB>français),
#                     dans le MÊME ordre que la source. Le script refuse tout décalage.
#   -ModRoot        : dossier racine du mod (ex. D:\Games\RimWorld\Mods\<ID>)
#   -Language       : dossier de langue (défaut French)
#   -FileName       : nom du fichier écrit dans chaque sous-dossier de type (défaut Translations.xml)
#
# Ne supprime rien : seuls les fichiers <DefType>\<FileName> sont écrits (remplacés).
# Un fichier est écrit par DefType ; chaque clé est vérifiée et chaque XML relu.
param(
    [Parameter(Mandatory = $true)][string]$SourceTsv,
    [Parameter(Mandatory = $true)][string]$TranslationDir,
    [Parameter(Mandatory = $true)][string]$ModRoot,
    [string]$Language = 'French',
    [string]$FileName = 'Translations.xml'
)
$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding($false)

$src = [IO.File]::ReadAllLines($SourceTsv, [Text.Encoding]::UTF8) | Where-Object { $_.Trim() -ne '' }
$fr = @()
foreach ($f in (Get-ChildItem -LiteralPath $TranslationDir -Filter *.tsv | Sort-Object Name)) {
    $fr += [IO.File]::ReadAllLines($f.FullName, [Text.Encoding]::UTF8) | Where-Object { $_.Trim() -ne '' }
}
if ($src.Count -ne $fr.Count) { throw "Nombre de lignes différent : source=$($src.Count) traductions=$($fr.Count)" }

function Esc([string]$t) { return $t.Replace('&', '&amp;').Replace('<', '&lt;').Replace('>', '&gt;') }

$groups = [ordered]@{}
for ($i = 0; $i -lt $src.Count; $i++) {
    $sp = $src[$i] -split "`t"
    $fp = $fr[$i] -split "`t"
    if ($sp[1] -ne $fp[0]) { throw "Clé différente ligne $($i + 1) : source '$($sp[1])' / traduction '$($fp[0])'" }
    if ($fp.Count -ne 2 -or [string]::IsNullOrWhiteSpace($fp[1])) { throw "Traduction vide ou mal formée ligne $($i + 1) : $($sp[1])" }
    $type = $sp[0]
    if (-not $groups.Contains($type)) { $groups[$type] = New-Object System.Collections.Generic.List[string] }
    $groups[$type].Add("  <$($fp[0])>$(Esc $fp[1])</$($fp[0])>")
}

$base = Join-Path $ModRoot "Languages\$Language\DefInjected"
$total = 0
foreach ($type in $groups.Keys) {
    $dir = Join-Path $base $type
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $out = Join-Path $dir $FileName
    $lines = @('<?xml version="1.0" encoding="utf-8"?>', '<LanguageData>') + $groups[$type] + @('</LanguageData>')
    [IO.File]::WriteAllLines($out, [string[]]$lines, $utf8)
    [xml]$null = Get-Content -LiteralPath $out -Encoding UTF8 -Raw   # relecture : échoue si XML invalide
    $total += $groups[$type].Count
}
"types=$($groups.Count) entrees=$total dossier=$base"
