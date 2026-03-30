@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul 2>&1

REM ============================================================
REM  OnlyFix - Windows Release Build (Transactional)
REM  Production-like build with custom DNS and loopback IPs.
REM  Requires administrator. Rolls back on any failure.
REM ============================================================

REM -- Resolve PROJECT_ROOT to repo root (two levels up from this script)
set "SCRIPT_DIR=%~dp0"
pushd "%SCRIPT_DIR%\..\.."
set "PROJECT_ROOT=%CD%"
popd

set "LARAVEL_DIR=%PROJECT_ROOT%\onlyfix"
set "COMPOSE_FILE=%PROJECT_ROOT%\docker-compose.yml"
set "HOSTS_FILE=%SystemRoot%\System32\drivers\etc\hosts"

REM -- Rollback tracking flags
set "HOSTS_MODIFIED=0"
set "LOOPBACK_ADDED=0"
set "CONTAINERS_STARTED=0"
set "IMAGES_BUILT=0"
set "ENV_CREATED=0"

echo.
echo ======================================================
echo    OnlyFix - Release Build [Windows]
echo ======================================================
echo    Project root: %PROJECT_ROOT%
echo.

REM -- Step 1: Admin rights check
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Administrator privileges required.
    echo         Right-click cmd.exe and choose "Run as administrator".
    EXIT /B 1
)

REM -- Step 2: Docker check
echo [STEP] Checking Docker...
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker is not installed.
    echo         Install Docker Desktop: https://docs.docker.com/get-docker/
    EXIT /B 1
)

docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker daemon is not running.
    echo         Start Docker Desktop and try again.
    EXIT /B 1
)
echo [  OK] Docker is available and running.

REM -- Step 3: Docker Compose detection
set COMPOSE_CMD=
docker compose version >nul 2>&1
if %errorlevel% equ 0 (
    set "COMPOSE_CMD=docker compose"
    echo [  OK] Docker Compose: docker compose
    goto :compose_found
)
docker-compose version >nul 2>&1
if %errorlevel% equ 0 (
    set "COMPOSE_CMD=docker-compose"
    echo [  OK] Docker Compose: docker-compose
    goto :compose_found
)
echo [ERROR] Docker Compose not found.
EXIT /B 1
:compose_found

REM -- Step 4: Verify project files exist
if not exist "%COMPOSE_FILE%" (
    echo [ERROR] docker-compose.yml not found at %COMPOSE_FILE%
    EXIT /B 1
)
if not exist "%LARAVEL_DIR%\.env.example" (
    echo [ERROR] .env.example not found at %LARAVEL_DIR%\.env.example
    EXIT /B 1
)

REM -- Step 5: Hosts file entries
echo [STEP] Configuring hosts file...
findstr /C:"# OnlyFix Project" "%HOSTS_FILE%" >nul 2>&1
if %errorlevel% equ 0 (
    echo [ INFO] OnlyFix hosts entries already exist. Skipping.
) else (
    echo.>> "%HOSTS_FILE%"
    echo # OnlyFix Project - Docker Services>> "%HOSTS_FILE%"
    echo 127.0.1.1       onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.2       db.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.3       mailpit.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.4       node.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.5       phpmyadmin.onlyfix.local>> "%HOSTS_FILE%"
    if %errorlevel% neq 0 (
        echo [ERROR] Failed to write hosts file.
        goto :rollback
    )
    set "HOSTS_MODIFIED=1"
    echo [  OK] Hosts file updated.
)

REM -- Step 6: Loopback IP aliases
echo [STEP] Configuring loopback IP aliases...

set "LOOPBACK_ADAPTER="
for /f "tokens=*" %%a in ('netsh interface show interface ^| findstr /i "Loopback"') do (
    for /f "tokens=4*" %%b in ("%%a") do set "LOOPBACK_ADAPTER=%%b %%c"
)

if not defined LOOPBACK_ADAPTER (
    echo [ INFO] No dedicated Loopback adapter found.
    echo [ INFO] Testing if loopback IPs are already routable...

    ping -n 1 -w 500 127.0.1.1 >nul 2>&1
    if %errorlevel% equ 0 (
        echo [  OK] Loopback IPs 127.0.1.x are already routable.
        goto :loopback_done
    )

    echo [WARN] 127.0.1.x IPs are not routable. Adding interface addresses...
    for %%i in (127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5) do (
        netsh interface ipv4 add address "Loopback Pseudo-Interface 1" %%i 255.255.255.255 >nul 2>&1
    )
    set "LOOPBACK_ADDED=1"
    goto :loopback_done
)

for %%i in (127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5) do (
    netsh interface ipv4 add address "!LOOPBACK_ADAPTER!" %%i 255.255.255.255 >nul 2>&1
)
set "LOOPBACK_ADDED=1"

:loopback_done
echo [  OK] Loopback IP configuration done.

ipconfig /flushdns >nul 2>&1
echo [  OK] DNS cache flushed.

REM -- Step 7: Create .env from .env.example
echo [STEP] Preparing .env file...
if not exist "%LARAVEL_DIR%\.env" (
    copy "%LARAVEL_DIR%\.env.example" "%LARAVEL_DIR%\.env" >nul
    if %errorlevel% neq 0 (
        echo [ERROR] Failed to create .env file.
        goto :rollback
    )
    set "ENV_CREATED=1"
    echo [  OK] .env created from .env.example
) else (
    echo [ INFO] .env already exists. Skipping.
)

REM -- Step 8: Tear down old containers and volumes for a clean slate
echo [STEP] Removing old containers and volumes for clean rebuild...
pushd "%PROJECT_ROOT%"
%COMPOSE_CMD% down -v >nul 2>&1
echo [  OK] Clean slate prepared.

REM -- Step 9: Build Docker images (no cache for release)
echo [STEP] Building Docker images (no-cache)...
%COMPOSE_CMD% build --no-cache
if %errorlevel% neq 0 (
    echo [ERROR] Docker build failed.
    popd
    goto :rollback
)
set "IMAGES_BUILT=1"
echo [  OK] Docker images built.

REM -- Step 10: Start containers
echo [STEP] Starting containers...
%COMPOSE_CMD% up -d
if %errorlevel% neq 0 (
    echo [ERROR] Failed to start containers.
    popd
    goto :rollback
)
set "CONTAINERS_STARTED=1"
echo [  OK] Containers started.

REM -- Step 11: Wait for MySQL readiness
echo [STEP] Waiting for MySQL to be ready...
set "DB_READY=0"
for /L %%i in (1,1,60) do (
    if !DB_READY! equ 0 (
        %COMPOSE_CMD% exec -T db mysqladmin ping -h localhost -u root --password=rootSecurePassword123! --silent >nul 2>&1
        if !errorlevel! equ 0 (
            set "DB_READY=1"
            echo [  OK] MySQL is ready. Waited %%i seconds.
        ) else (
            set /a "MOD=%%i %% 5"
            if !MOD! equ 0 echo [ INFO] Still waiting for MySQL... %%i seconds
            timeout /t 1 /nobreak >nul
        )
    )
)
if %DB_READY% equ 0 (
    echo [ERROR] MySQL did not become ready within 60 seconds.
    popd
    goto :rollback
)

REM -- Step 12: Composer install (production optimized)
echo [STEP] Installing Composer dependencies...
%COMPOSE_CMD% exec -T app composer install --no-interaction --optimize-autoloader --no-dev
if %errorlevel% neq 0 (
    echo [ERROR] Composer install failed.
    popd
    goto :rollback
)
echo [  OK] Composer dependencies installed (production).

REM -- Step 13: Generate application key
echo [STEP] Generating application key...
%COMPOSE_CMD% exec -T app php artisan key:generate --force --no-interaction
if %errorlevel% neq 0 (
    echo [ERROR] Key generation failed.
    popd
    goto :rollback
)
echo [  OK] Application key generated.

REM -- Step 14: Run migrations (non-destructive for release)
echo [STEP] Running database migrations...
%COMPOSE_CMD% exec -T app php artisan migrate --seed --force --no-interaction
if %errorlevel% neq 0 (
    echo [ERROR] Database migration failed.
    popd
    goto :rollback
)
echo [  OK] Database migrated.

REM -- Step 15: NPM install + production build (inside container)
echo [STEP] Building frontend assets (production)...
%COMPOSE_CMD% exec -T node sh -c "npm install && npm run build"
if %errorlevel% neq 0 (
    echo [ERROR] Frontend build failed.
    popd
    goto :rollback
)
echo [  OK] Frontend assets built.

REM -- Step 16: Storage link
echo [STEP] Creating storage symlink...
%COMPOSE_CMD% exec -T app php artisan storage:link --force --no-interaction
if %errorlevel% neq 0 (
    echo [ERROR] Storage link creation failed.
    popd
    goto :rollback
)
echo [  OK] Storage link created.

REM -- Step 17: Wayfinder route generation
echo [STEP] Generating Wayfinder routes...
%COMPOSE_CMD% exec -T app php artisan wayfinder:generate --with-form --no-interaction
if %errorlevel% neq 0 (
    echo [ERROR] Wayfinder generation failed.
    popd
    goto :rollback
)
echo [  OK] Wayfinder routes generated.

REM -- Step 18: Clear and rebuild caches (production optimization)
echo [STEP] Optimizing Laravel caches...
%COMPOSE_CMD% exec -T app php artisan config:clear --no-interaction
%COMPOSE_CMD% exec -T app php artisan route:clear --no-interaction
%COMPOSE_CMD% exec -T app php artisan view:clear --no-interaction
%COMPOSE_CMD% exec -T app php artisan config:cache --no-interaction
%COMPOSE_CMD% exec -T app php artisan route:cache --no-interaction
%COMPOSE_CMD% exec -T app php artisan view:cache --no-interaction
echo [  OK] Laravel caches optimized.

REM -- Done
popd

echo.
echo ======================================================
echo    [OK] OnlyFix release build complete!
echo ======================================================
echo.
echo  Endpoints:
echo    App:        http://onlyfix.local
echo    Mailpit:    http://mailpit.onlyfix.local:8025
echo    phpMyAdmin: http://phpmyadmin.onlyfix.local:8080
echo.
echo  Test accounts:
echo    Admin:    admin@example.com / password
echo    Mechanic: mechanic@example.com / password
echo    User:     test@example.com / password
echo.
echo  --------------------------------------------------------
echo  To UNDO this setup and remove all traces:
echo    1. cd %PROJECT_ROOT%
echo    2. docker compose down -v --rmi local
echo    3. Remove "# OnlyFix Project" block from:
echo       %SystemRoot%\System32\drivers\etc\hosts
echo    4. Delete onlyfix\.env if you want a fresh start
echo    5. Loopback IPs 127.0.1.x need no removal
echo  --------------------------------------------------------
echo.

endlocal
EXIT /B 0

REM ================================================================
REM  ROLLBACK: undo everything that was done so far
REM ================================================================
:rollback
echo.
echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
echo  [FAIL] Release build failed. Rolling back all changes...
echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
echo.

if %CONTAINERS_STARTED% equ 1 (
    echo [UNDO] Stopping and removing containers plus volumes...
    pushd "%PROJECT_ROOT%"
    %COMPOSE_CMD% down -v >nul 2>&1
    popd
    echo [UNDO] Containers and volumes removed.
)

if %IMAGES_BUILT% equ 1 (
    echo [UNDO] Removing built Docker images...
    pushd "%PROJECT_ROOT%"
    %COMPOSE_CMD% down --rmi local >nul 2>&1
    popd
    echo [UNDO] Docker images removed.
)

if %ENV_CREATED% equ 1 (
    if exist "%LARAVEL_DIR%\.env" (
        del "%LARAVEL_DIR%\.env" >nul 2>&1
        echo [UNDO] Removed .env file.
    )
)

if %HOSTS_MODIFIED% equ 1 (
    echo [UNDO] Removing OnlyFix entries from hosts file...
    set "HOSTS_TEMP=%TEMP%\hosts_clean_%RANDOM%.tmp"
    (for /f "usebackq delims=" %%L in ("%HOSTS_FILE%") do (
        set "LINE=%%L"
        echo !LINE! | findstr /C:"onlyfix" /C:"OnlyFix" >nul 2>&1
        if errorlevel 1 (
            echo %%L
        )
    )) > "!HOSTS_TEMP!"
    copy /y "!HOSTS_TEMP!" "%HOSTS_FILE%" >nul 2>&1
    del "!HOSTS_TEMP!" >nul 2>&1
    echo [UNDO] Hosts file cleaned.
)

if %LOOPBACK_ADDED% equ 1 (
    echo [UNDO] Removing loopback IP aliases...
    for %%i in (127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5) do (
        netsh interface ipv4 delete address "Loopback Pseudo-Interface 1" %%i >nul 2>&1
    )
    echo [UNDO] Loopback IPs removed.
)

ipconfig /flushdns >nul 2>&1

echo.
echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
echo  Rollback complete. All changes have been reversed.
echo  Fix the error above and run the script again.
echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
echo.

endlocal
EXIT /B 1
