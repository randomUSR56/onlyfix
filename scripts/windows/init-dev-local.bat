@echo off
setlocal enabledelayedexpansion

echo.
echo ======================================================
echo    OnlyFix - Fejlesztoi inicializalas
echo    (localhost, nincs rendszergazda)
echo ======================================================
echo.

REM ── 1. Docker ellenorzes ────────────────────────────────────────
echo 🔧 Docker ellenorzese...
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker nincs telepitve!
    echo    Telepitsd a Docker Desktop-ot: https://docs.docker.com/get-docker/
    EXIT /B 1
)

docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker daemon nem fut!
    echo    Inditsd el a Docker Desktop alkalmazast es probald ujra.
    EXIT /B 1
)
echo ✅ Docker elerheto es fut

REM ── 2. Docker Compose detektalas ───────────────────────────────
set COMPOSE_CMD=
docker-compose version >nul 2>&1
if %errorlevel% equ 0 (
    set COMPOSE_CMD=docker-compose
    echo ✅ Docker Compose: docker-compose
    goto :compose_found
)
docker compose version >nul 2>&1
if %errorlevel% equ 0 (
    set COMPOSE_CMD=docker compose
    echo ✅ Docker Compose: docker compose
    goto :compose_found
)
echo ❌ Docker Compose nem talalhato!
echo    Telepitsd: https://docs.docker.com/compose/install/
EXIT /B 1
:compose_found

REM ── 3. .env fajl ellenorzes ────────────────────────────────────
echo 🔧 .env fajl ellenorzese...
if not exist "onlyfix\.env" (
    if exist "onlyfix\.env.example" (
        copy "onlyfix\.env.example" "onlyfix\.env" >nul
        echo ✅ .env fajl letrehozva ^(.env.example alapjan^)
    ) else (
        echo ⚠️  .env.example nem talalhato!
    )
) else (
    echo ✅ .env fajl mar letezik
)

REM ── 4. Docker compose fajlok ───────────────────────────────────
set COMPOSE_FILES=-f docker-compose.yml
if exist "docker-compose.local.yml" (
    set COMPOSE_FILES=!COMPOSE_FILES! -f docker-compose.local.yml
    echo ✅ Lokalis override fajl megtalalva ^(docker-compose.local.yml^)
)

REM ── 5. Docker images build ─────────────────────────────────────
echo 🔧 Docker image-ek epitese...
%COMPOSE_CMD% %COMPOSE_FILES% build
if %errorlevel% neq 0 (
    echo ❌ Docker build sikertelen!
    EXIT /B 1
)
echo ✅ Docker image-ek elkeszultek

REM ── 6. Kontenerek inditasa ─────────────────────────────────────
echo 🔧 Kontenerek inditasa...
%COMPOSE_CMD% %COMPOSE_FILES% up -d
echo ✅ Kontenerek elindultak

REM ── 7. Composer install ────────────────────────────────────────
echo 🔧 Composer fuggosegek telepitese...

set EXEC_FLAGS=-it
if not defined WT_SESSION (
    set EXEC_FLAGS=
)

%COMPOSE_CMD% %COMPOSE_FILES% exec %EXEC_FLAGS% app composer install
echo ✅ Composer fuggosegek telepitve

REM ── 8. NPM install ────────────────────────────────────────────
echo 🔧 NPM fuggosegek telepitese...
%COMPOSE_CMD% %COMPOSE_FILES% restart node
echo ✅ Node kontener ujrainditva ^(npm install automatikusan fut^)

REM ── 9. Laravel app key generalas ──────────────────────────────
echo 🔧 Laravel alkalmazaskulcs generalasa...
%COMPOSE_CMD% %COMPOSE_FILES% exec %EXEC_FLAGS% app php artisan key:generate
echo ✅ Alkalmazaskulcs generalva

REM ── 10. Migrate fresh + seed ──────────────────────────────────
echo 🔧 Adatbazis migralasa es seedelese...
%COMPOSE_CMD% %COMPOSE_FILES% exec %EXEC_FLAGS% app php artisan migrate:fresh --seed
echo ✅ Adatbazis migralva es seedelve

REM ── 11. Storage link ──────────────────────────────────────────
echo 🔧 Storage link letrehozasa...
%COMPOSE_CMD% %COMPOSE_FILES% exec app php artisan storage:link
echo ✅ Storage link letrehozva

REM ── 12. Wayfinder route generalas ─────────────────────────────
echo 🔧 Wayfinder utvonalak generalasa...
%COMPOSE_CMD% %COMPOSE_FILES% exec app php artisan wayfinder:generate --with-form
echo ✅ Wayfinder utvonalak generalva

REM ── 13. Laravel Boost telepites ────────────────────────────────
echo 🔧 Laravel Boost MCP telepitese...
%COMPOSE_CMD% %COMPOSE_FILES% exec %EXEC_FLAGS% app php artisan boost:install
echo ✅ Laravel Boost telepitve

REM ── Befejezes ──────────────────────────────────────────────────
echo.
echo ======================================================
echo    ✅ OnlyFix sikeresen inicializalva!
echo ======================================================
echo.
echo 🌐 Elerhetosegek:
echo    App:        http://localhost
echo    Mailpit:    http://localhost:8025
echo    phpMyAdmin: http://localhost:8080
echo.
echo 🔑 Teszt fiokok:
echo    Admin:    admin@example.com / password
echo    Mechanic: mechanic@example.com / password
echo    User:     test@example.com / password
echo.

endlocal
