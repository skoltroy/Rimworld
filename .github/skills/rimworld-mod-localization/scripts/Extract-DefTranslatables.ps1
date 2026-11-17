# Extrait les chaînes traduisibles des Defs d'un mod (sans dossier Languages\English).
# Sortie : une ligne par clé : DefType<TAB>defName.champ[.index...]<TAB>texte anglais.
# Usage : -Root <dossier du mod> -OutFile <tsv>. Ignore les Abstract, suit ParentName dans le mod, inclut Compatibility\*\Defs.
param([string]$Root, [string]$OutFile)
$ErrorActionPreference = 'Stop'
$fields = @('label','description','labelShort','labelNoun','labelPlural','jobString','reportString','letterLabel','letterText','text','summary','pawnLabel','gerundLabel','textEnemy','textFriendly','textWillArrive','arrivedLetter','baseInspectLine','tipText','customLabel','skillLabel','makingLabel','verbNoun','labelMale','labelFemale','postfix','inspectString','ideoLabel')

function Get-Fields($el, [string[]]$path) {
    $res = [ordered]@{}
    $idx = 0
    foreach ($c in $el.ChildNodes) {
        if ($c.NodeType -ne 'Element') { continue }
        $seg = $c.Name
        if ($c.Name -eq 'li') { $seg = "$idx"; $idx++ }
        $np = @($path) + @($seg)
        $hasEl = @($c.ChildNodes | Where-Object { $_.NodeType -eq 'Element' }).Count -gt 0
        if ($hasEl) {
            $sub = Get-Fields $c $np
            foreach ($k in $sub.Keys) { $res[$k] = $sub[$k] }
        } else {
            $t = $c.InnerText.Trim()
            if ($t -ne '' -and (($fields -contains $c.Name) -or ($el.Name -eq 'rulesStrings'))) { $res[($np -join '.')] = $t }
        }
    }
    return $res
}

$files = @(Get-ChildItem (Join-Path $Root 'Defs') -Recurse -File -Filter *.xml)
$ce = Join-Path $Root 'Compatibility'
if (Test-Path $ce) { $files += @(Get-ChildItem $ce -Recurse -File -Filter *.xml | Where-Object { $_.FullName -match '\\Defs\\' }) }

$defs = @()
$byName = @{}
foreach ($f in $files) {
    [xml]$x = Get-Content -LiteralPath $f.FullName -Encoding UTF8 -Raw
    foreach ($d in $x.DocumentElement.ChildNodes) {
        if ($d.NodeType -ne 'Element') { continue }
        $defs += $d
        $n = $d.GetAttribute('Name'); if ($n) { $byName[$n] = $d }
    }
}
foreach ($d in $defs) { $dn = $d.defName; if ($dn) { if (-not $byName.ContainsKey($dn)) { $byName[$dn] = $d } } }

function Get-Inherited($d, [int]$depth) {
    $res = [ordered]@{}
    $pn = $d.GetAttribute('ParentName')
    if ($pn -and $depth -lt 10 -and $byName.ContainsKey($pn)) {
        $res = Get-Inherited $byName[$pn] ($depth + 1)
        foreach ($k in (Get-Fields $byName[$pn] @()).Keys) { $res[$k] = (Get-Fields $byName[$pn] @())[$k] }
    }
    return $res
}

$out = New-Object System.Collections.Generic.List[string]
$seen = @{}
foreach ($d in $defs) {
    if ($d.GetAttribute('Abstract') -eq 'True') { continue }
    $dn = $d.defName
    if (-not $dn) { continue }
    $type = $d.Name
    $own = Get-Fields $d @()
    $inh = Get-Inherited $d 0
    $merged = [ordered]@{}
    foreach ($k in $inh.Keys) { $merged[$k] = $inh[$k] }
    foreach ($k in $own.Keys) { $merged[$k] = $own[$k] }
    foreach ($k in $merged.Keys) {
        $key = "$dn.$k"
        $id = "$type|$key"
        if ($seen.ContainsKey($id)) { continue }
        $seen[$id] = 1
        $val = ($merged[$k] -replace "[\r\n\t]+", ' ')
        $out.Add("$type`t$key`t$val")
    }
}
[System.IO.File]::WriteAllLines($OutFile, $out, (New-Object System.Text.UTF8Encoding($false)))
"entries=$($out.Count)"

