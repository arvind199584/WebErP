# install_pgvector.ps1
<#
    PowerShell script to install the pgvector extension for PostgreSQL on Windows.
    It works without Stack Builder (useful when pgvector is not listed).
    Steps:
    1. Detect the PostgreSQL installation directory (expects the default
       "C:\Program Files\PostgreSQL\<version>").
    2. Download the latest pgvector release ZIP from GitHub.
    3. Extract the *.sql and *.control files to the PostgreSQL share\extension folder.
    4. Restart the PostgreSQL service so the extension becomes available.
#>

# ---- Configuration ----
# You can change these if your installation is non‑standard.
$PgRoot = "C:\Program Files\PostgreSQL"   # root folder containing version subfolders
$ServiceNamePattern = "postgresql-x64-*"   # pattern to match the service name (e.g., postgresql-x64-15)
$GitHubReleaseUrl = "https://api.github.com/repos/pgvector/pgvector/releases/latest"
# -----------------------

function Get-PostgresVersionPath {
    # Find the newest version folder under $PgRoot (e.g., 15, 14, ...)
    $dirs = Get-ChildItem -Path $PgRoot -Directory | Sort-Object Name -Descending
    if ($dirs.Count -eq 0) {
        Write-Error "No PostgreSQL installation found under $PgRoot"
        exit 1
    }
    return $dirs[0].FullName
}

function Get-ServiceName {
    $svc = Get-Service -Name $ServiceNamePattern -ErrorAction SilentlyContinue
    if (-not $svc) {
        Write-Error "PostgreSQL service not found (pattern: $ServiceNamePattern). Adjust the pattern if needed."
        exit 1
    }
    return $svc.Name
}

# ---- Main ----
$PgPath = Get-PostgresVersionPath
$ExtPath = Join-Path $PgPath "share\extension"
Write-Host "PostgreSQL detected at: $PgPath"
Write-Host "Extension directory: $ExtPath"

# Download latest release metadata
Write-Host "Fetching latest pgvector release information..."
$releaseInfo = Invoke-RestMethod -Uri $GitHubReleaseUrl -UseBasicParsing
$zipAsset = $releaseInfo.assets | Where-Object { $_.name -like "*windows*.zip" } | Select-Object -First 1
if (-not $zipAsset) {
    Write-Error "No Windows binary zip found in the latest release."
    exit 1
}
$zipUrl = $zipAsset.browser_download_url
Write-Host "Downloading $($zipAsset.name) from $zipUrl"
$zipFile = Join-Path $env:TEMP "pgvector.zip"
Invoke-WebRequest -Uri $zipUrl -OutFile $zipFile -UseBasicParsing

# Extract the needed files
Write-Host "Extracting files..."
Expand-Archive -Path $zipFile -DestinationPath $env:TEMP\pgvector_extracted -Force
# The zip contains a folder named something like "pgvector-<version>"
$extractedFolder = Get-ChildItem $env:TEMP\pgvector_extracted | Where-Object { $_.PSIsContainer } | Select-Object -First 1
$files = Get-ChildItem -Path $extractedFolder.FullName -Filter "*.sql","*.control" -Recurse
foreach ($f in $files) {
    Copy-Item -Path $f.FullName -Destination $ExtPath -Force
    Write-Host "Copied $($f.Name) to $ExtPath"
}

# Clean up temporary files
Remove-Item $zipFile -Force
Remove-Item $env:TEMP\pgvector_extracted -Recurse -Force

# Restart PostgreSQL service
$svcName = Get-ServiceName
Write-Host "Restarting PostgreSQL service ($svcName)..."
Restart-Service -Name $svcName -Force
Write-Host "pgvector installation complete. You can now run `CREATE EXTENSION vector;` in psql."
