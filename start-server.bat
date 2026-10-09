@echo off
setlocal
cd /d "%~dp0"

echo ========================================================
echo Starting ModPyPhp Application Server
echo ========================================================

rem --- 1. Close web/API servers if already running on ports 8000, 5000, 5001 ---
echo Checking for running servers...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000"') do (
    echo Stopping PHP server on port 8000 PID %%a...
    taskkill /F /PID %%a >nul 2>nul
)
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5000"') do (
    echo Stopping Python AI Brain server on port 5000 PID %%a...
    taskkill /F /PID %%a >nul 2>nul
)
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5001"') do (
    echo Stopping Python Attendance server on port 5001 PID %%a...
    taskkill /F /PID %%a >nul 2>nul
)

rem --- 2. Check and start PostgreSQL if not running ---
echo Checking PostgreSQL status on port 5432...
netstat -ano | findstr ":5432" | findstr "LISTENING" >nul 2>nul
if %ERRORLEVEL%==0 (
    echo [OK] PostgreSQL is active and listening on port 5432.
    goto pg_done
)

echo [!] PostgreSQL is off or not listening on port 5432.
echo Attempting to restart PostgreSQL...

rem Step 2A: Try starting via standard Windows Service (works if run as Admin)
net start postgresql-x64-18 >nul 2>nul
timeout /t 2 /nobreak >nul
netstat -ano | findstr ":5432" | findstr "LISTENING" >nul 2>nul
if %ERRORLEVEL%==0 (
    echo [OK] PostgreSQL Windows Service started successfully.
    goto pg_done
)

rem Step 2B: Fallback to pg_ctl direct startup
set "PG_CTL="
set "PG_DATA="

if exist "C:\Program Files\PostgreSQL\18\bin\pg_ctl.exe" (
    set "PG_CTL=C:\Program Files\PostgreSQL\18\bin\pg_ctl.exe"
    set "PG_DATA=C:\Program Files\PostgreSQL\18\data"
)
if not defined PG_CTL if exist "C:\Program Files\PostgreSQL\16\bin\pg_ctl.exe" (
    set "PG_CTL=C:\Program Files\PostgreSQL\16\bin\pg_ctl.exe"
    set "PG_DATA=C:\Program Files\PostgreSQL\16\data"
)
if not defined PG_CTL if exist "C:\Program Files\PostgreSQL\15\bin\pg_ctl.exe" (
    set "PG_CTL=C:\Program Files\PostgreSQL\15\bin\pg_ctl.exe"
    set "PG_DATA=C:\Program Files\PostgreSQL\15\data"
)

if not defined PG_CTL (
    echo [WARNING] Could not locate PostgreSQL installation directory.
    echo Please make sure PostgreSQL is installed and service 'postgresql-x64-18' is started.
    goto pg_done
)

rem Clean up stale postmaster.pid lock file if server is not listening
if exist "%PG_DATA%\postmaster.pid" (
    echo Cleaning up stale postmaster.pid lock file...
    del /f /q "%PG_DATA%\postmaster.pid" >nul 2>nul
)

echo Starting PostgreSQL via pg_ctl...
start "" "%PG_CTL%" start -D "%PG_DATA%" -s >nul 2>nul

echo Waiting for PostgreSQL to initialize and accept connections...
for /l %%i in (1,1,10) do (
    timeout /t 1 /nobreak >nul
    netstat -ano | findstr ":5432" | findstr "LISTENING" >nul 2>nul
    if not errorlevel 1 goto pg_started_ok
)

echo [WARNING] PostgreSQL did not respond within 10 seconds.
echo Please check PostgreSQL logs or start service 'postgresql-x64-18' manually.
goto pg_done

:pg_started_ok
echo [OK] PostgreSQL started successfully and is accepting connections on port 5432.

:pg_done
rem Ensure PostgreSQL bin directory is in PATH for PHP pgsql/pdo_pgsql extensions
if exist "C:\Program Files\PostgreSQL\18\bin" set "PATH=%PATH%;C:\Program Files\PostgreSQL\18\bin"
if exist "C:\Program Files\PostgreSQL\16\bin" set "PATH=%PATH%;C:\Program Files\PostgreSQL\16\bin"
if exist "C:\Program Files\PostgreSQL\15\bin" set "PATH=%PATH%;C:\Program Files\PostgreSQL\15\bin"
if exist "C:\PostgreSQL\bin" set "PATH=%PATH%;C:\PostgreSQL\bin"

rem --- 3. Check for PHP installation ---
where php >nul 2>nul
if %ERRORLEVEL%==0 goto php_ok

echo PHP is not found in PATH. Searching common installation directories...
set "PHP_DIR="
if exist "C:\php\php.exe" set "PHP_DIR=C:\php"
if exist "%USERPROFILE%\php\php.exe" set "PHP_DIR=%USERPROFILE%\php"
for /d %%d in ("C:\Program Files\PHP\*") do (
    if exist "%%d\php.exe" (
        set "PHP_DIR=%%d"
    )
)
if not "%PHP_DIR%"=="" (
    set "PATH=%PATH%;%PHP_DIR%"
    echo Found PHP in %PHP_DIR%
    goto php_ok
)

echo PHP not found. Attempting to install PHP via winget...
winget install -e --id PHP.PHP.8.4 --silent --accept-package-agreements --accept-source-agreements 2>nul
if %ERRORLEVEL% neq 0 goto php_install_fail

rem Search again after installation
if exist "C:\php\php.exe" set "PHP_DIR=C:\php"
for /d %%d in ("C:\Program Files\PHP\*") do (
    if exist "%%d\php.exe" (
        set "PHP_DIR=%%d"
    )
)
if not "%PHP_DIR%"=="" (
    set "PATH=%PATH%;%PHP_DIR%"
    goto php_ok
)

:php_install_fail
echo PHP not found. Please run setup.bat inside the Setup folder to install PHP.
exit /b 1

:php_ok
echo PHP is installed and available.

rem --- 4. Check for Python installation ---
where python >nul 2>nul
if %ERRORLEVEL%==0 goto python_ok

echo Python is not found in PATH. Searching common installation directories...
set "PYTHON_DIR="
for /d %%d in ("%USERPROFILE%\AppData\Local\Programs\Python\*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)
for /d %%d in ("C:\Program Files\Python\*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)
for /d %%d in ("C:\Program Files\Python3*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)
for /d %%d in ("C:\Python*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)

if not "%PYTHON_DIR%"=="" (
    set "PATH=%PATH%;%PYTHON_DIR%;%PYTHON_DIR%\Scripts"
    echo Found Python in %PYTHON_DIR%
    goto python_ok
)

echo Python not found. Attempting to install Python via winget...
winget install -e --id Python.Python.3 --silent --accept-package-agreements --accept-source-agreements 2>nul
if %ERRORLEVEL% neq 0 goto python_install_fail

rem Search again after installation
for /d %%d in ("%USERPROFILE%\AppData\Local\Programs\Python\*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)
for /d %%d in ("C:\Program Files\Python\*") do (
    if exist "%%d\python.exe" (
        set "PYTHON_DIR=%%d"
    )
)
if not "%PYTHON_DIR%"=="" (
    set "PATH=%PATH%;%PYTHON_DIR%;%PYTHON_DIR%\Scripts"
    goto python_ok
)

:python_install_fail
echo Python not found. Please run setup.bat inside the Setup folder to install Python.
exit /b 1

:python_ok
echo Python is installed and available.

rem --- 5. Setup Virtual Environment using uv / offline wheels ---
where uv >nul 2>nul
if %ERRORLEVEL% neq 0 goto uv_missing

echo uv tool is available. Creating virtual environment using uv...
if not exist ".venv_uv" (
    uv venv .venv_uv
)
echo Installing/updating dependencies using uv...
if exist "Setup\wheels" (
    uv pip install -r requirements.txt --find-links Setup\wheels --no-index --python .venv_uv\Scripts\python.exe
) else (
    uv pip install -r requirements.txt --python .venv_uv\Scripts\python.exe
)
goto venv_ok

:uv_missing
echo Creating virtual environment if missing...
if not exist ".venv_uv" (
    echo Creating virtual environment...
    python -m venv .venv_uv
)
echo Verifying virtual environment dependencies...
if exist "Setup\wheels" (
    .venv_uv\Scripts\python.exe -m pip install --no-index --find-links=Setup\wheels -r requirements.txt 2>nul
) else (
    .venv_uv\Scripts\python.exe -m pip install -r requirements.txt 2>nul
)

:venv_ok

rem --- 6. Start Servers ---
echo.
echo ========================================================
echo Starting all servers...
echo ========================================================

echo Starting PHP Web Server on http://0.0.0.0:8000 (Logs: php_server.log)...
start "PHP Web Server" /B php -c php.ini -S 0.0.0.0:8000 > php_server.log 2>&1

echo Starting Python AI Brain on http://localhost:5000 (Logs: python_server.log)...
start "Python AI Brain" /B .venv_uv\Scripts\python.exe modules/AI_and_Tools/AIML/Python/brain.py > python_server.log 2>nul

echo Starting Python Attendance Server on http://localhost:5001 (Logs: python_attendance.log)...
start "Python Attendance Server" /B .venv_uv\Scripts\python.exe services/python/main.py > python_attendance.log 2>nul

rem Small pause to allow servers to bind
timeout /t 2 /nobreak >nul

echo.
echo ========================================================
echo OK: All servers started successfully!
echo   - PHP Web Server: http://localhost:8000
echo   - Python AI Brain: http://localhost:5000
echo   - Python Attendance: http://localhost:5001
echo   - PostgreSQL DB: localhost:5432
echo ========================================================
echo.
echo Opening ModPyPhp in default browser...
start http://localhost:8000
echo.
echo Press any key to stop all servers and exit...
pause >nul

echo.
echo Stopping servers...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000"') do taskkill /F /PID %%a >nul 2>nul
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5000"') do taskkill /F /PID %%a >nul 2>nul
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5001"') do taskkill /F /PID %%a >nul 2>nul
echo Done.
