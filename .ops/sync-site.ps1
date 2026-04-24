param(
    [string]$SourceRef    = "C:\Users\MyleneK\Local Sites\praatdeurtje\app\public",
    [string]$BundlePath   = "C:\Users\MyleneK\Local Sites\praatdeurtje\.ops\sync-bundle.latest.json",
    [string]$RestEndpoint = "",
    [string]$AppUser      = "",
    [string]$AppPassword  = "",
    [string]$ConfigPath   = "",
    [switch]$Apply,
    [switch]$ExportOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot    = Split-Path -Parent $PSScriptRoot
$phpIni      = Join-Path $PSScriptRoot 'php-cli-local.ini'
$buildScript = Join-Path $PSScriptRoot 'build-bundle.php'
$remoteConf  = Join-Path $PSScriptRoot 'sync-remote.json'

# wp-cli.phar: zoek eerst in deze repo, dan bij mycreatief als fallback
$wpCliPhar = Join-Path $repoRoot 'wp-cli.phar'
if (-not (Test-Path -LiteralPath $wpCliPhar)) {
    $wpCliPhar = 'C:\Users\MyleneK\Local Sites\mycreatief\wp-cli.phar'
}

if (($RestEndpoint -eq '' -or $AppUser -eq '' -or $AppPassword -eq '') -and (Test-Path -LiteralPath $remoteConf)) {
    $conf = Get-Content -LiteralPath $remoteConf -Raw | ConvertFrom-Json
    if ($RestEndpoint -eq '' -and $conf.rest_endpoint) { $RestEndpoint = $conf.rest_endpoint }
    if ($AppUser      -eq '' -and $conf.app_user)      { $AppUser      = $conf.app_user }
    if ($AppPassword  -eq '' -and $conf.app_password)  { $AppPassword  = $conf.app_password }
}

function Resolve-PhpExe {
    $phpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCmd) { return $phpCmd.Source }
    $candidates = @(
        'C:\Users\MyleneK\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe',
        'C:\Users\MyleneK\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win32\php.exe'
    )
    foreach ($c in $candidates) {
        if (Test-Path -LiteralPath $c) { return $c }
    }
    throw 'PHP executable not found.'
}

$phpExe = Resolve-PhpExe

$exportArgs = [System.Collections.Generic.List[string]]::new()
if (Test-Path -LiteralPath $phpIni) { $exportArgs.Add('-c'); $exportArgs.Add($phpIni) }
$exportArgs.Add($wpCliPhar)
if ($ConfigPath -ne '') { $exportArgs.Add("--config=$ConfigPath") }
$exportArgs.Add("--path=$SourceRef")
$exportArgs.Add('eval-file')
$exportArgs.Add($buildScript)
$exportArgs.Add("bundle_path=$BundlePath")
$exportArgs.Add('stdout=1')
$exportArgs.Add('write_file=1')

Write-Host "Exporting sync bundle from $SourceRef ..."
& $phpExe @exportArgs

if ($ExportOnly) {
    Write-Host "ExportOnly flag set - done."
    return
}

if ([string]::IsNullOrWhiteSpace($RestEndpoint)) {
    Write-Host "No RestEndpoint configured. Create .ops/sync-remote.json or pass -RestEndpoint."
    return
}

if ($AppUser -eq '' -or $AppPassword -eq '') {
    Write-Error "AppUser and AppPassword are required. Configure .ops/sync-remote.json or pass -AppUser/-AppPassword."
    return
}

$bundleJson = Get-Content -LiteralPath $BundlePath -Raw -Encoding UTF8
$base64Cred = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${AppUser}:${AppPassword}"))

if ($Apply) {
    $uri = ($RestEndpoint.TrimEnd('/')) + '/wp-json/prd/v1/sync/apply?apply=1'
    Write-Host ""
    Write-Host "Applying bundle to $uri ..."
} else {
    $uri = ($RestEndpoint.TrimEnd('/')) + '/wp-json/prd/v1/sync/apply'
    Write-Host ""
    Write-Host "Dry-run preview on $uri ..."
}

$headers = @{
    Authorization  = "Basic $base64Cred"
    'Content-Type' = 'application/json; charset=utf-8'
}

try {
    $response = Invoke-RestMethod -Uri $uri -Method POST -Headers $headers -Body ([Text.Encoding]::UTF8.GetBytes($bundleJson))
    $response | ConvertTo-Json -Depth 10
} catch {
    $statusCode = $null
    $errorBody  = $null
    if ($_.Exception.Response) {
        $statusCode = [int] $_.Exception.Response.StatusCode
        try {
            $stream = $_.Exception.Response.GetResponseStream()
            $reader = [System.IO.StreamReader]::new($stream, [System.Text.Encoding]::UTF8)
            $errorBody = $reader.ReadToEnd()
            $reader.Close()
        } catch { }
    }
    if ($errorBody) { Write-Host "Server response body:`n$errorBody" }
    Write-Error "REST call failed (HTTP $statusCode): $($_.Exception.Message)"
}
