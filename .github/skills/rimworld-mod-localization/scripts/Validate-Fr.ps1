param([Parameter(Mandatory=$true)][string]$ModPath)
$ErrorActionPreference = 'Continue'
$bad = 0; $ok = 0; $identical = 0; $missing = 0
$tokRx = '\{[^}]*\}|\[[^\]]*\]|\\n'
$enFiles = Get-ChildItem $ModPath -Recurse -File -Filter *.xml | Where-Object { $_.FullName -match '\\Languages\\English\\' }
foreach ($f in $enFiles) {
  $en = $f.FullName
  $fr = $en -replace '\\Languages\\English\\', '\Languages\French\'
  if (-not (Test-Path -LiteralPath $fr)) { Write-Output "MISSING FR: $fr"; $missing++; continue }
  try {
    [xml]$a = Get-Content -LiteralPath $en -Raw -Encoding UTF8
    [xml]$b = Get-Content -LiteralPath $fr -Raw -Encoding UTF8
  } catch { Write-Output "XML ERROR: $fr :: $($_.Exception.Message)"; $bad++; continue }
  $ea = @($a.DocumentElement.ChildNodes | Where-Object { $_.NodeType -eq 'Element' })
  $eb = @($b.DocumentElement.ChildNodes | Where-Object { $_.NodeType -eq 'Element' })
  $na = @($ea | ForEach-Object { $_.Name }); $nb = @($eb | ForEach-Object { $_.Name })
  $dup = @($nb | Group-Object | Where-Object { $_.Count -gt 1 } | ForEach-Object { $_.Name })
  if ($dup.Count -gt 0) { Write-Output "DUPLICATE KEYS in $fr : $($dup -join ', ')"; $bad++ }
  $d = Compare-Object $na $nb
  if ($d) { Write-Output "KEY DIFF $(Split-Path $fr -Leaf): missing/extra = $(($d | ForEach-Object { $_.SideIndicator + $_.InputObject }) -join ' ')"; $bad++ }
  foreach ($n in $ea) {
    $fn = $eb | Where-Object { $_.Name -eq $n.Name } | Select-Object -First 1
    if (-not $fn) { continue }
    $ta = @([regex]::Matches($n.InnerText, $tokRx) | ForEach-Object { $_.Value } | Sort-Object)
    $tb = @([regex]::Matches($fn.InnerText, $tokRx) | ForEach-Object { $_.Value } | Sort-Object)
    if (($ta -join '|') -ne ($tb -join '|')) { Write-Output "TOKEN DIFF $($n.Name) in $(Split-Path $fr -Leaf): EN[$($ta -join ' ')] FR[$($tb -join ' ')]"; $bad++ }
    if ($n.InnerText.Trim() -eq $fn.InnerText.Trim() -and $n.InnerText.Trim().Length -gt 0) { $identical++ }
  }
  $ok++
}
Write-Output "Files checked: $ok ; missing FR: $missing ; identical EN/FR candidates: $identical ; problems: $bad"

