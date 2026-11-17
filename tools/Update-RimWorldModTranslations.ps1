[CmdletBinding()]
param(
    [ValidateSet('Ask', 'Explicit', 'Discover')]
    [string]$SelectionMode = 'Ask',

    [string[]]$ModId = @(),

    [ValidateSet('Ask', 'Complete', 'Supplement')]
    [string]$TranslationMode = 'Supplement',

    [ValidateSet('Plan', 'Export')]
    [string]$Action = 'Plan',

    [string]$SourceModsPath = 'D:\Games\RimWorld\Mods',

    [string]$ProjectPath = (Split-Path -Parent $PSScriptRoot),

    [switch]$NoReport
)

$ErrorActionPreference = 'Stop'

function Get-NumberedDirectories {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path -PathType Container)) {
        return @()
    }

    return @(
        Get-ChildItem -LiteralPath $Path -Directory |
            Where-Object { $_.Name -match '^\d+$' } |
            ForEach-Object { $_.Name }
    )
}

function Get-TrackedIdsFromReadme {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        return @()
    }

    $ids = New-Object 'System.Collections.Generic.HashSet[string]' ([System.StringComparer]::Ordinal)
    $idColumn = -1
    foreach ($line in Get-Content -LiteralPath $Path) {
        if ($line -match '^\s*[-*]\s*`(\d+)`\s*$') {
            [void]$ids.Add($Matches[1])
            continue
        }

        if ($line -match '^\s*\|(.+)\|\s*$') {
            $cells = @($Matches[1] -split '\|' | ForEach-Object { $_.Trim() })
            for ($index = 0; $index -lt $cells.Count; $index++) {
                if ($cells[$index] -ieq 'ID') {
                    $idColumn = $index
                    break
                }
            }

            if ($idColumn -ge 0 -and $idColumn -lt $cells.Count -and $cells[$idColumn] -match '^\d+$') {
                [void]$ids.Add($cells[$idColumn])
            } elseif ($idColumn -lt 0 -and $cells[0] -match '^\d+$') {
                [void]$ids.Add($cells[0])
            }
        }
    }

    return @($ids)
}

function Get-TrackedModIds {
    param(
        [string]$Root,
        [string]$Extensions
    )

    $ids = New-Object 'System.Collections.Generic.HashSet[string]' ([System.StringComparer]::Ordinal)
    $readmes = @(
        (Join-Path $Root 'README.md'),
        (Join-Path $Extensions 'ReadMe.md'),
        (Join-Path $Extensions 'README.md')
    )

    foreach ($readme in $readmes) {
        foreach ($id in (Get-TrackedIdsFromReadme -Path $readme)) {
            [void]$ids.Add($id)
        }
    }

    foreach ($directory in @(
        (Get-NumberedDirectories -Path $Root),
        (Get-NumberedDirectories -Path (Join-Path $Root 'Mods')),
        (Get-NumberedDirectories -Path $Extensions)
    )) {
        foreach ($id in $directory) {
            [void]$ids.Add($id)
        }
    }

    return $ids
}

function Get-XmlTranslationMap {
    param([string]$Path)

    $document = New-Object System.Xml.XmlDocument
    $document.XmlResolver = $null
    $document.Load($Path)

    $values = New-Object 'System.Collections.Generic.Dictionary[string,string]' ([System.StringComparer]::Ordinal)
    $duplicates = New-Object 'System.Collections.Generic.List[string]'
    foreach ($node in $document.DocumentElement.ChildNodes) {
        if ($node.NodeType -ne [System.Xml.XmlNodeType]::Element) {
            continue
        }

        if ($values.ContainsKey($node.LocalName)) {
            $duplicates.Add($node.LocalName)
            continue
        }

        $values.Add($node.LocalName, $node.InnerText)
    }

    [pscustomobject]@{
        Values = $values
        Duplicates = @($duplicates)
    }
}

function Get-ModName {
    param([string]$ModPath)

    $aboutPath = Join-Path $ModPath 'About\About.xml'
    if (-not (Test-Path -LiteralPath $aboutPath -PathType Leaf)) {
        return 'Nom non disponible (About/About.xml absent)'
    }

    try {
        $about = New-Object System.Xml.XmlDocument
        $about.XmlResolver = $null
        $about.Load($aboutPath)
        $name = $about.SelectSingleNode('/ModMetaData/name')
        if ($name -and -not [string]::IsNullOrWhiteSpace($name.InnerText)) {
            return $name.InnerText.Trim()
        }
    } catch {
        return "Nom indisponible (About.xml invalide : $($_.Exception.Message))"
    }

    return 'Nom non déclaré'
}

function Resolve-ModIds {
    param(
        [string]$Mode,
        [string[]]$RequestedIds,
        [string]$SourceRoot,
        [string]$Root,
        [string]$Extensions
    )

    if ($Mode -eq 'Ask') {
        Write-Host 'Choisissez les mods à traiter :'
        Write-Host '  1. Indiquer un ou plusieurs ID de mods'
        Write-Host '  2. Découvrir les ID présents dans le dossier du jeu mais absents des listes suivies'
        $choice = Read-Host 'Votre choix (1 ou 2)'
        if ($choice -eq '1') {
            $RequestedIds = @((Read-Host 'ID de mods, séparés par des virgules') -split '[,\s]+' | Where-Object { $_ })
            $Mode = 'Explicit'
        } elseif ($choice -eq '2') {
            $Mode = 'Discover'
        } else {
            throw 'Choix invalide. Relancez le workflow et choisissez 1 ou 2.'
        }
    }

    if ($Mode -eq 'Explicit') {
        if ($RequestedIds.Count -eq 0) {
            throw 'Le mode Explicit nécessite au moins un ID via -ModId.'
        }

        $ids = @($RequestedIds | ForEach-Object { $_.Trim() } | Where-Object { $_ } | Select-Object -Unique)
        foreach ($id in $ids) {
            if ($id -notmatch '^\d+$') {
                throw "ID de mod invalide : '$id'. Seuls les dossiers dont le nom est numérique sont acceptés."
            }

            $modPath = Join-Path $SourceRoot $id
            if (-not (Test-Path -LiteralPath $modPath -PathType Container)) {
                throw "Le dossier du mod $id est absent de '$SourceRoot'."
            }
        }

        return $ids
    }

    $sourceIds = Get-NumberedDirectories -Path $SourceRoot
    $trackedIds = Get-TrackedModIds -Root $Root -Extensions $Extensions
    $newIds = @($sourceIds | Where-Object { -not $trackedIds.Contains($_) } | Sort-Object)

    if ($Mode -eq 'Ask' -and $newIds.Count -gt 0) {
        Write-Host 'ID détectés comme non suivis :'
        $newIds | ForEach-Object { Write-Host "  $_" }
        $confirm = Read-Host 'Préparer un rapport pour ces mods ? (o/n)'
        if ($confirm -notmatch '^(o|oui|y|yes)$') {
            Write-Host 'Aucun changement effectué.'
            return @()
        }
    }

    return $newIds
}

function Get-TranslationPlan {
    param(
        [string[]]$Ids,
        [string]$SourceRoot,
        [string]$Mode
    )

    $plan = New-Object 'System.Collections.Generic.List[object]'
    foreach ($id in $Ids) {
        $modPath = Join-Path $SourceRoot $id
        $englishFiles = @(
            Get-ChildItem -LiteralPath $modPath -Recurse -File |
                Where-Object {
                    $_.FullName -notmatch '(?i)\\\.git\\' -and
                    $_.FullName -match '(?i)\\Languages\\English\\'
                }
        )

        if ($englishFiles.Count -eq 0) {
            $modName = Get-ModName -ModPath $modPath
            $plan.Add([pscustomobject]@{
                ModId = $id
                ModName = $modName
                EnglishFiles = 0
                FrenchFiles = 0
                KeysToTranslate = $null
                MissingKeys = $null
                PossibleUntranslatedKeys = $null
                DuplicateKeys = $null
                InvalidFiles = $null
                Files = @()
                Note = 'Aucune ressource Languages\English trouvée. Examiner les Defs chargées avant de conclure qu’il existe du texte à traduire.'
            })
            continue
        }

        $filePlans = New-Object 'System.Collections.Generic.List[object]'
        $totalKeys = 0
        $totalMissing = 0
        $totalSame = 0
        $totalDuplicates = 0
        $invalidFiles = 0

        foreach ($englishFile in $englishFiles) {
            $relativePath = $englishFile.FullName.Substring($modPath.Length + 1)
            $frenchPath = [regex]::Replace(
                $englishFile.FullName,
                '(?i)\\Languages\\English\\',
                '\Languages\French\',
                [System.Text.RegularExpressions.RegexOptions]::CultureInvariant
            )
            $frenchExists = Test-Path -LiteralPath $frenchPath -PathType Leaf
            $frenchRelativePath = [regex]::Replace($relativePath, '(?i)\\Languages\\English\\', '\Languages\French\')
            if ($frenchExists) {
                $frenchRelativePath = $frenchPath.Substring($modPath.Length + 1)
            }
            $filePlan = [ordered]@{
                EnglishPath = $relativePath
                FrenchPath = $frenchRelativePath
                Format = $englishFile.Extension.TrimStart('.').ToLowerInvariant()
                SourceEntries = $null
                MissingKeys = @()
                PossibleUntranslatedKeys = @()
                ExtraFrenchKeys = @()
                DuplicateKeys = @()
                FrenchExists = $frenchExists
                Note = ''
            }

            if ($englishFile.Extension -ieq '.xml') {
                try {
                    $englishMap = Get-XmlTranslationMap -Path $englishFile.FullName
                    $frenchMap = $null
                    if ($frenchExists) {
                        $frenchMap = Get-XmlTranslationMap -Path $frenchPath
                    }

                    $missingKeys = New-Object 'System.Collections.Generic.List[string]'
                    $sameKeys = New-Object 'System.Collections.Generic.List[string]'
                    $extraKeys = New-Object 'System.Collections.Generic.List[string]'
                    if ($frenchMap) {
                        foreach ($key in $frenchMap.Values.Keys) {
                            if (-not $englishMap.Values.ContainsKey($key)) {
                                $extraKeys.Add($key)
                            }
                        }
                    }

                    foreach ($key in $englishMap.Values.Keys) {
                        $totalKeys++
                        if (-not $frenchMap -or -not $frenchMap.Values.ContainsKey($key)) {
                            $missingKeys.Add($key)
                            $totalMissing++
                            continue
                        }

                        if ([string]::Equals(
                            $englishMap.Values[$key].Trim(),
                            $frenchMap.Values[$key].Trim(),
                            [System.StringComparison]::Ordinal
                        )) {
                            $sameKeys.Add($key)
                            $totalSame++
                        }
                    }

                    $duplicates = @($englishMap.Duplicates)
                    if ($frenchMap) {
                        $duplicates += @($frenchMap.Duplicates)
                    }
                    $totalDuplicates += $duplicates.Count
                    $filePlan.SourceEntries = $englishMap.Values.Count
                    $filePlan.MissingKeys = @($missingKeys)
                    $filePlan.PossibleUntranslatedKeys = @($sameKeys)
                    $filePlan.ExtraFrenchKeys = @($extraKeys)
                    $filePlan.DuplicateKeys = @($duplicates)
                    if ($Mode -eq 'Complete') {
                        $filePlan.Note = 'Mode complet : revoir toutes les entrées anglaises. Les chaînes identiques peuvent être des noms propres ou des termes partagés.'
                    } else {
                        $filePlan.Note = 'Mode complément : ajouter les clés manquantes et examiner les valeurs identiques à l’anglais (candidates seulement, pas une preuve d’erreur).'
                    }
                } catch {
                    $invalidFiles++
                    $filePlan.Note = "XML source ou cible invalide : $($_.Exception.Message)"
                }
            } else {
                $filePlan.Note = 'Ressource textuelle : comparer le contenu et sa traduction manuellement.'
            }

            $filePlans.Add([pscustomobject]$filePlan)
        }

        $keysToTranslate = $totalMissing + $totalSame
        if ($Mode -eq 'Complete') {
            $keysToTranslate = $totalKeys
        }
        $modName = Get-ModName -ModPath $modPath

        $frenchFiles = @(
            Get-ChildItem -LiteralPath $modPath -Recurse -File |
                Where-Object {
                    $_.FullName -notmatch '(?i)\\\.git\\' -and
                    $_.FullName -match '(?i)\\Languages\\French\\'
                }
        )

        $filePlanItems = @($filePlans.ToArray())
        $planItem = [pscustomobject]@{
            ModId = $id
            ModName = $modName
            EnglishFiles = $englishFiles.Count
            FrenchFiles = $frenchFiles.Count
            KeysToTranslate = $keysToTranslate
            MissingKeys = $totalMissing
            PossibleUntranslatedKeys = $totalSame
            DuplicateKeys = $totalDuplicates
            InvalidFiles = $invalidFiles
            Files = $filePlanItems
            Note = ''
        }
        $plan.Add($planItem)
    }

    return $plan.ToArray()
}

function Write-PlanReport {
    param(
        [object[]]$Plan,
        [string]$Mode,
        [string]$Root
    )

    $reportDirectory = Join-Path $Root 'tools\reports'
    if (-not (Test-Path -LiteralPath $reportDirectory -PathType Container)) {
        New-Item -ItemType Directory -Path $reportDirectory -Force | Out-Null
    }

    $reportPath = Join-Path $reportDirectory ("translation-plan-{0}.json" -f (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
    $report = [pscustomobject]@{
        CreatedAt = (Get-Date).ToString('o')
        TranslationMode = $Mode
        Mods = @($Plan)
        Caution = 'Equal English/French XML values are only candidates for review; names and shared terms may correctly remain unchanged. No translation is generated automatically.'
    }
    $report | ConvertTo-Json -Depth 12 | Set-Content -LiteralPath $reportPath -Encoding UTF8
    return $reportPath
}

function Export-FrenchResources {
    param(
        [string[]]$Ids,
        [string]$SourceRoot,
        [string]$ExtensionsRoot,
        [string]$Root
    )

    if ($Ids.Count -eq 0) {
        throw 'Export nécessite au moins un ID via -ModId.'
    }

    $copied = New-Object 'System.Collections.Generic.List[object]'
    foreach ($id in $Ids) {
        if ($id -notmatch '^\d+$') {
            throw "ID de mod invalide : '$id'."
        }

        $modPath = Join-Path $SourceRoot $id
        if (-not (Test-Path -LiteralPath $modPath -PathType Container)) {
            throw "Le dossier du mod $id est absent de '$SourceRoot'."
        }

        $frenchFiles = @(
            Get-ChildItem -LiteralPath $modPath -Recurse -File |
                Where-Object {
                    $_.FullName -notmatch '(?i)\\\.git\\' -and
                    $_.FullName -match '(?i)\\Languages\\French\\'
                }
        )
        if ($frenchFiles.Count -eq 0) {
            throw "Aucun fichier Languages\French à exporter pour le mod $id."
        }

        foreach ($file in $frenchFiles) {
            $relativePath = $file.FullName.Substring($modPath.Length + 1)
            $destination = Join-Path (Join-Path $ExtensionsRoot $id) $relativePath
            $destinationDirectory = Split-Path -Parent $destination
            if (-not (Test-Path -LiteralPath $destinationDirectory -PathType Container)) {
                New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
            }

            Copy-Item -LiteralPath $file.FullName -Destination $destination -Force
            $sourceHash = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash
            $destinationHash = (Get-FileHash -LiteralPath $destination -Algorithm SHA256).Hash
            if ($sourceHash -ne $destinationHash) {
                throw "La vérification SHA256 a échoué pour '$destination'."
            }

            $copied.Add([pscustomobject]@{
                ModId = $id
                Source = $file.FullName
                Destination = $destination
                SHA256 = $sourceHash
            })
        }
    }

    Write-Output ("Export terminé : {0} fichier(s) français vérifié(s)." -f $copied.Count)
    return $copied.ToArray()
}

if (-not (Test-Path -LiteralPath $SourceModsPath -PathType Container)) {
    throw "Dossier source introuvable : '$SourceModsPath'."
}
if (-not (Test-Path -LiteralPath $ProjectPath -PathType Container)) {
    throw "Dossier projet introuvable : '$ProjectPath'."
}

$extensionsPath = Join-Path $ProjectPath 'Extensions'
$idsToProcess = @()
if ($Action -eq 'Export') {
    if ($ModId.Count -eq 0) {
        throw 'Pour exporter, indiquez explicitement -ModId avec un ou plusieurs ID.'
    }
    $idsToProcess = Resolve-ModIds -Mode 'Explicit' -RequestedIds $ModId -SourceRoot $SourceModsPath -Root $ProjectPath -Extensions $extensionsPath
    [void](Export-FrenchResources -Ids $idsToProcess -SourceRoot $SourceModsPath -ExtensionsRoot $extensionsPath -Root $ProjectPath)
    return
}

$idsToProcess = Resolve-ModIds -Mode $SelectionMode -RequestedIds $ModId -SourceRoot $SourceModsPath -Root $ProjectPath -Extensions $extensionsPath
if ($idsToProcess.Count -eq 0) {
    Write-Host 'Aucun mod à traiter.'
    return
}

$mode = $TranslationMode
if ($mode -eq 'Ask') {
    Write-Host 'Choisissez la portée de la traduction :'
    Write-Host '  1. Complète : revoir toutes les entrées sources'
    Write-Host '  2. Complément : conserver les traductions existantes et compléter les lacunes'
    $choice = Read-Host 'Votre choix (1 ou 2)'
    if ($choice -eq '1') {
        $mode = 'Complete'
    } elseif ($choice -eq '2') {
        $mode = 'Supplement'
    } else {
        throw 'Choix invalide. Relancez le workflow et choisissez 1 ou 2.'
    }
}

$plan = Get-TranslationPlan -Ids $idsToProcess -SourceRoot $SourceModsPath -Mode $mode
foreach ($mod in $plan) {
    Write-Host ("{0} | {1} | EN: {2} fichier(s) | FR: {3} fichier(s) | clés à revoir: {4}" -f
        $mod.ModId, $mod.ModName, $mod.EnglishFiles, $mod.FrenchFiles, $mod.KeysToTranslate)
    if ($mod.Note) {
        Write-Host ("  {0}" -f $mod.Note)
    }
    foreach ($file in $mod.Files) {
        if ($file.InvalidFiles) {
            Write-Warning ("{0}: {1}" -f $file.EnglishPath, $file.Note)
        }
    }
}

if (-not $NoReport) {
    $reportPath = Write-PlanReport -Plan $plan -Mode $mode -Root $ProjectPath
    Write-Host "Rapport créé : $reportPath"
}

Write-Host 'Aucune traduction n’a été générée. Utilisez l’agent RimWorld Mod Localizer pour appliquer le plan, valider les fichiers, puis relancez avec -Action Export -ModId ... .'
