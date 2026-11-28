$ErrorActionPreference = 'Stop'
$scriptPath = Join-Path (Split-Path -Parent $PSScriptRoot) '.tools\Update-RimWorldModTranslations.ps1'
$testRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("rimworld-translation-workflow-test-" + [guid]::NewGuid().ToString('N'))
$sourceRoot = Join-Path $testRoot 'GameMods'
$projectRoot = Join-Path $testRoot 'Project'
$extensionsRoot = Join-Path $projectRoot 'Extensions'

function Assert-True {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) {
        throw "Assertion failed: $Message"
    }
}

function New-TestFile {
    param([string]$Path, [string]$Content)
    $directory = Split-Path -Parent $Path
    New-Item -ItemType Directory -Path $directory -Force | Out-Null
    Set-Content -LiteralPath $Path -Value $Content -Encoding UTF8
}

try {
    New-Item -ItemType Directory -Path $sourceRoot, $projectRoot, $extensionsRoot -Force | Out-Null

    New-TestFile -Path (Join-Path $projectRoot 'README.md') -Content @'
# Test
| ID | Mod |
|---:|---|
| 123 | Tracked in root README |
'@
    New-TestFile -Path (Join-Path $extensionsRoot 'ReadMe.md') -Content @'
# Existing translations
| Date | ID | Folder | Mod |
|---|---:|---|---|
| 15/11/2026 | 111 | `111` | Tracked in extension README |
'@
    New-TestFile -Path (Join-Path $extensionsRoot '222 - French TradFix.zip') -Content 'archive placeholder'

    New-TestFile -Path (Join-Path $sourceRoot '123\Languages\English\Keyed\Strings.xml') -Content @'
<LanguageData><Keep>Original</Keep><Missing>Needs translation</Missing></LanguageData>
'@
    New-TestFile -Path (Join-Path $sourceRoot '123\Languages\French\Keyed\Strings.xml') -Content @'
<LanguageData><Keep>Original</Keep></LanguageData>
'@
    New-TestFile -Path (Join-Path $sourceRoot '456\Languages\English\Keyed\Strings.xml') -Content @'
<LanguageData><Only>New mod</Only></LanguageData>
'@
    New-TestFile -Path (Join-Path $sourceRoot '222\Languages\English\Keyed\Strings.xml') -Content @'
<LanguageData><Only>Already archived</Only></LanguageData>
'@
    New-TestFile -Path (Join-Path $sourceRoot '111\About\About.xml') -Content '<ModMetaData><name>Tracked in extension README</name></ModMetaData>'
    New-TestFile -Path (Join-Path $sourceRoot '789\About\About.xml') -Content '<ModMetaData><name>Resource-free</name></ModMetaData>'
    New-Item -ItemType Directory -Path (Join-Path $sourceRoot '789') -Force | Out-Null
    New-TestFile -Path (Join-Path $sourceRoot '789\About\About.xml') -Content '<ModMetaData><name>Resource-free</name></ModMetaData>'

    & $scriptPath -SelectionMode Discover -SourceModsPath $sourceRoot -ProjectPath $projectRoot
    $discoverReportPath = Get-ChildItem -LiteralPath (Join-Path $projectRoot '.tools\reports') -Filter 'translation-plan-*.json' |
        Sort-Object Name |
        Select-Object -Last 1 -ExpandProperty FullName
    $discoverReport = Get-Content -LiteralPath $discoverReportPath -Raw | ConvertFrom-Json
    $discoveredIds = @($discoverReport.Mods | ForEach-Object { $_.ModId })
    Assert-True ($discoveredIds -contains '456') 'discovery should include an untracked numeric mod ID'
    Assert-True ($discoveredIds -contains '789') 'discovery should include an untracked mod without English resources for manual review'
    Assert-True ($discoveredIds -notcontains '123') 'discovery should exclude IDs listed in the root README'
    Assert-True ($discoveredIds -notcontains '111') 'discovery should exclude IDs listed in the extension README ID column'
    Assert-True ($discoveredIds -notcontains '222') 'discovery should exclude IDs tracked by an Extensions ZIP archive'

    & $scriptPath -SelectionMode Explicit -ModId '123' -TranslationMode Supplement -SourceModsPath $sourceRoot -ProjectPath $projectRoot
    $supplementReportPath = Get-ChildItem -LiteralPath (Join-Path $projectRoot '.tools\reports') -Filter 'translation-plan-*.json' |
        Sort-Object Name |
        Select-Object -Last 1 -ExpandProperty FullName
    $supplementReport = Get-Content -LiteralPath $supplementReportPath -Raw | ConvertFrom-Json
    Assert-True ($supplementReport.Mods[0].KeysToTranslate -eq 2) 'supplement mode should flag the missing key and the identical-text candidate for review'

    [void](& $scriptPath -Action Export -ModId '123' -SourceModsPath $sourceRoot -ProjectPath $projectRoot 2>&1)
    $exported = Join-Path $projectRoot '.tools\exports\123\Languages\French\Keyed\Strings.xml'
    Assert-True (Test-Path -LiteralPath $exported -PathType Leaf) 'export should stage the French resource outside Extensions'
    Assert-True (-not (Test-Path -LiteralPath (Join-Path $extensionsRoot '123'))) 'export should not create an unpacked directory in Extensions'
    $exportedXml = [xml](Get-Content -LiteralPath $exported -Raw)
    Assert-True ($exportedXml.LanguageData.Keep -eq 'Original') 'export should preserve translation content'

    Write-Output 'All translation workflow tests passed.'
} finally {
    if (Test-Path -LiteralPath $testRoot) {
        Remove-Item -LiteralPath $testRoot -Recurse -Force
    }
}
