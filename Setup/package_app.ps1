# ModPyPhp - Automated Packaging Script for Offline Installer
$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$RootDir = (Resolve-Path "$PSScriptRoot\..").Path
$SetupDir = "$RootDir\Setup"
$AppZipPath = "$SetupDir\modpyphp_app.zip"
$FullSetupZipPath = "$RootDir\Setup.zip"

Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host "                ModPyPhp - Offline Installer Packaging Tool                 " -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host ""

# Relative directory or file exclusions (Regex patterns matched against relative path)
$ExcludePatterns = @(
    '^\.venv_uv',
    '^venv',
    '^\.idea',
    '^\.git',
    '^scratch',
    '^sessions_new',
    '^supabase_chunks',
    '^Setup',
    '^Setup\.zip',
    '^ModPyPhp_Migration\.zip',
    '^python-installer-test\.exe',
    '^\.env',
    '\.log$',
    'node_modules',
    '\.expo',
    '[\\\/]build[\\\/]',
    '^build[\\\/]',
    '[\\\/]\.gradle',
    'test_exclude\.ps1$'
)

Write-Host "[1/2] Creating Application Zip Bundle ($AppZipPath)..." -ForegroundColor Yellow

if (Test-Path $AppZipPath) {
    Remove-Item $AppZipPath -Force
}

$zip = [System.IO.Compression.ZipFile]::Open($AppZipPath, [System.IO.Compression.ZipArchiveMode]::Create)

$allFiles = Get-ChildItem -Path $RootDir -Recurse -File

$fileCount = 0
foreach ($file in $allFiles) {
    $relPath = $file.FullName.Substring($RootDir.Length + 1)
    
    $shouldExclude = $false
    foreach ($pattern in $ExcludePatterns) {
        if ($relPath -match $pattern) {
            $shouldExclude = $true
            break
        }
    }
    
    if (-not $shouldExclude) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $relPath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        $fileCount++
    }
}

$zip.Dispose()

$AppZipSizeMB = [math]::Round((Get-Item $AppZipPath).Length / 1MB, 2)
Write-Host "[OK] Application Zip Bundle created successfully with $fileCount files ($AppZipSizeMB MB)." -ForegroundColor Green
Write-Host ""

Write-Host "[2/2] Updating Complete Setup Zip Archive ($FullSetupZipPath)..." -ForegroundColor Yellow
if (Test-Path $FullSetupZipPath) {
    Remove-Item $FullSetupZipPath -Force
}

$SetupItemsToZip = Get-ChildItem -Path $SetupDir
Compress-Archive -Path $SetupItemsToZip.FullName -DestinationPath $FullSetupZipPath -CompressionLevel Optimal -Force
$SetupZipSizeMB = [math]::Round((Get-Item $FullSetupZipPath).Length / 1MB, 2)
Write-Host "[OK] Complete Setup Archive created successfully ($SetupZipSizeMB MB)." -ForegroundColor Green

Write-Host ""
Write-Host "============================================================================" -ForegroundColor Cyan
Write-Host " Packaging Completed Successfully!" -ForegroundColor Cyan
Write-Host " - App Bundle: $AppZipPath ($AppZipSizeMB MB)" -ForegroundColor Cyan
Write-Host " - Full Setup Archive: $FullSetupZipPath ($SetupZipSizeMB MB)" -ForegroundColor Cyan
Write-Host "============================================================================" -ForegroundColor Cyan
