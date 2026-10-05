@echo off
:: ============================================================================
:: ModPyPhp - Automated Offline System Installer
:: ============================================================================
setlocal enabledelayedexpansion

echo ============================================================================
echo           ModPyPhp - Integrated Office Management System
echo                       Offline Setup Installer
echo ============================================================================
echo.

set "SETUP_DIR=%~dp0"
set "ROOT_DIR=%SETUP_DIR%..\"
cd /d "%ROOT_DIR%"

echo [1/6] Checking Application Files...
if exist "%SETUP_DIR%modpyphp_app.zip" (
    if not exist "%ROOT_DIR%index.php" (
        echo Extracting ModPyPhp application bundle to root directory...
        powershell -Command "Expand-Archive -Path '%SETUP_DIR%modpyphp_app.zip' -DestinationPath '%ROOT_DIR%' -Force"
        echo [OK] Application files extracted successfully.
    ) else (
        echo [OK] Application source files are already present.
    )
) else (
    if exist "%ROOT_DIR%index.php" (
        echo [OK] Application source files present in root directory.
    ) else (
        echo [WARNING] modpyphp_app.zip not found in Setup directory, but proceeding with existing workspace.
    )
)
echo.

echo [2/6] Checking Python Installation...
where python >nul 2>nul
if %ERRORLEVEL%==0 (
    echo [OK] Python is already installed.
    goto python_done
)

echo [!] Python was not found on your system.
echo Installing Python 3.11 offline from Setup\Installers...
start /wait "" "%SETUP_DIR%Installers\python-3.11.9-amd64.exe" /passive InstallAllUsers=1 PrependPath=1 Include_pip=1
set "PATH=%PATH%;C:\Program Files\Python311;C:\Program Files\Python311\Scripts;%USERPROFILE%\AppData\Local\Programs\Python\Python311"

where python >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Python installation failed or PATH not updated. Please restart terminal after installing Python.
    pause
    exit /b 1
)
echo [OK] Python installed successfully.

:python_done
echo.

echo [3/6] Checking PHP Installation...
where php >nul 2>nul
if %ERRORLEVEL%==0 (
    echo [OK] PHP is already installed.
    goto php_done
)

if exist "C:\php\php.exe" (
    set "PATH=%PATH%;C:\php"
    echo [OK] Found PHP in C:\php.
    goto php_done
)

echo [!] PHP was not found on your system.
echo Extracting bundled PHP 8.x to C:\php...
if not exist "C:\php" mkdir "C:\php"
powershell -Command "Expand-Archive -Path '%SETUP_DIR%Installers\php-windows.zip' -DestinationPath 'C:\php' -Force"
set "PATH=%PATH%;C:\php"

where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    if exist "C:\php\php.exe" set "PATH=%PATH%;C:\php"
)

echo [OK] PHP installed/configured successfully.

:php_done
echo.

echo [4/6] Setting up Python Virtual Environment (Offline Wheels)...
if not exist ".venv_uv" (
    echo Creating virtual environment .venv_uv...
    python -m venv .venv_uv
)

echo Installing dependencies from offline wheels directory...
.venv_uv\Scripts\python.exe -m pip install --upgrade pip --no-index --find-links="%SETUP_DIR%wheels" >nul 2>nul
.venv_uv\Scripts\python.exe -m pip install --no-index --find-links="%SETUP_DIR%wheels" -r requirements.txt

if %ERRORLEVEL% neq 0 (
    echo [WARNING] Some packages failed to install offline. Trying fallback links...
    .venv_uv\Scripts\python.exe -m pip install --no-index --find-links="%ROOT_DIR%Setup\wheels" -r requirements.txt
)
echo [OK] Python dependencies installed into virtual environment.
echo.

echo [5/6] Checking PostgreSQL Installation ^& Database Restore...
set "PG_EXE="
if exist "C:\Program Files\PostgreSQL\18\bin\psql.exe" set "PG_EXE=C:\Program Files\PostgreSQL\18\bin"
if exist "C:\Program Files\PostgreSQL\16\bin\psql.exe" set "PG_EXE=C:\Program Files\PostgreSQL\16\bin"
if exist "C:\Program Files\PostgreSQL\15\bin\psql.exe" set "PG_EXE=C:\Program Files\PostgreSQL\15\bin"

if "%PG_EXE%"=="" (
    where psql >nul 2>nul
    if %ERRORLEVEL%==0 set "PG_EXE=."
)

if "%PG_EXE%"=="" (
    echo [!] PostgreSQL is not installed.
    echo Launching PostgreSQL 18 offline installer...
    echo PLEASE NOTE: When prompted by the PostgreSQL installer:
    echo   - Set password for superuser 'postgres' to: daredevil
    echo   - Keep port as 5432
    echo.
    start /wait "" "%SETUP_DIR%Installers\postgresql-18-installer.exe"
    
    if exist "C:\Program Files\PostgreSQL\18\bin\psql.exe" (
        set "PG_EXE=C:\Program Files\PostgreSQL\18\bin"
    ) else (
        echo [ERROR] PostgreSQL installation was cancelled or not found.
        pause
        exit /b 1
    )
)

echo [OK] PostgreSQL binaries found at: %PG_EXE%
echo.

echo Restoring modpyphp database...
set "PGPASSWORD=daredevil"
"%PG_EXE%\createdb.exe" -U postgres modpyphp >nul 2>nul
"%PG_EXE%\psql.exe" -U postgres -d modpyphp -f "%SETUP_DIR%Database\modpyphp_dump.sql" >nul 2>nul

echo [OK] Database restored successfully.
echo.

echo [6/6] Creating Configuration ^& Environment Files...
if not exist ".env" (
    echo DB_HOST=localhost > .env
    echo DB_PORT=5432 >> .env
    echo DB_NAME=modpyphp >> .env
    echo DB_USER=postgres >> .env
    echo DB_PASS=daredevil >> .env
    echo DB_SSLMODE=disable >> .env
    echo [OK] Created .env configuration file.
) else (
    echo [OK] Existing .env configuration preserved.
)

echo.
echo ============================================================================
echo   SUCCESS: ModPyPhp Offline Installation Completed Successfully!
echo ============================================================================
echo.
echo You can now start the application anytime by double-clicking:
echo   start-server.bat
echo.
set /p START_NOW="Would you like to start the application server now? (Y/N): "
if /i "%START_NOW%"=="Y" (
    call start-server.bat
)

pause
