@echo off
setlocal enabledelayedexpansion

echo.
echo ======================================================
echo    OnlyFix - Kiadoi build
echo    (nevfeloldassal, egyedi IP-vel)
echo ======================================================
echo.

REM ── 1. Rendszergazda jog ellenorzes ─────────────────────────────
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Rendszergazdai jogosultsag szukseges!
    echo    Kattints jobb gombbal a cmd.exe-re es valaszd a "Futtatas rendszergazdakent" opciot.
    EXIT /B 1
)

REM ── 2. Docker ellenorzes ────────────────────────────────────────
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

REM ── 3. Docker Compose detektalas ───────────────────────────────
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

REM ── 4. Hosts fajl bejegyzesek ──────────────────────────────────
echo 🔧 Hosts fajl konfiguralasa...

set HOSTS_FILE=%SystemRoot%\System32\drivers\etc\hosts

findstr /C:"OnlyFix Project" "%HOSTS_FILE%" >nul 2>&1
if %errorlevel% equ 0 (
    echo ⚠️  OnlyFix hosts bejegyzesek mar leteznek.
) else (
    echo.>> "%HOSTS_FILE%"
    echo # OnlyFix Project - Docker Services>> "%HOSTS_FILE%"
    echo 127.0.1.1       onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.2       db.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.3       mailpit.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.4       node.onlyfix.local>> "%HOSTS_FILE%"
    echo 127.0.1.5       phpmyadmin.onlyfix.local>> "%HOSTS_FILE%"
    echo ✅ Hosts fajl frissitve
)

REM ── 5. Loopback IP-k beallitasa ────────────────────────────────
echo 🔧 Loopback IP-k beallitasa...
for %%i in (127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5) do (
    netsh interface ip add address "Loopback" %%i 255.255.255.0 >nul 2>&1
)
echo ✅ Loopback IP-k konfiguralva

REM DNS cache urites
echo 🔧 DNS cache uritese...
ipconfig /flushdns >nul 2>&1
echo ✅ DNS cache uritve

REM ── 6. .env fajl ellenorzes ────────────────────────────────────
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

REM ── 7. Regi kontenerek es volume-ok eltavolitasa ──────────────
echo 🔧 Regi kontenerek es volume-ok eltavolitasa...
%COMPOSE_CMD% down -v >nul 2>&1
echo ✅ Tiszta allapot elokeszitve

REM ── 8. Docker images build (production) ────────────────────────
echo 🔧 Docker image-ek epitese ^(production^)...
%COMPOSE_CMD% build --no-cache
if %errorlevel% neq 0 (
    echo ❌ Docker build sikertelen!
    EXIT /B 1
)
echo ✅ Docker image-ek elkeszultek ^(production^)

REM ── 8. Kontenerek inditasa ─────────────────────────────────────
echo 🔧 Kontenerek inditasa...
%COMPOSE_CMD% up -d
echo ✅ Kontenerek elindultak

REM ── 9. Composer install ────────────────────────────────────────
echo 🔧 Composer fuggosegek telepitese...

set EXEC_FLAGS=-it
if not defined WT_SESSION (
    set EXEC_FLAGS=
)

%COMPOSE_CMD% exec %EXEC_FLAGS% app composer install
echo ✅ Composer fuggosegek telepitve

REM ── 11. NPM install + production build ─────────────────────────
echo 🔧 NPM fuggosegek telepitese es production build...
pushd onlyfix
npm install
npm run build
popd
echo ✅ NPM production build elkeszult

REM ── 11. Laravel app key generalas ──────────────────────────────
echo 🔧 Laravel alkalmazaskulcs generalasa...
%COMPOSE_CMD% exec %EXEC_FLAGS% app php artisan key:generate
echo ✅ Alkalmazaskulcs generalva

REM ── 12. Migrate (adatok megmaradnak) ───────────────────────────
echo 🔧 Adatbazis migralasa...
%COMPOSE_CMD% exec %EXEC_FLAGS% app php artisan migrate
echo ✅ Adatbazis migralva

REM ── 13. Storage link ──────────────────────────────────────────
echo 🔧 Storage link letrehozasa...
%COMPOSE_CMD% exec app php artisan storage:link
echo ✅ Storage link letrehozva

REM ── 14. Wayfinder route generalas ─────────────────────────────
echo 🔧 Wayfinder utvonalak generalasa...
%COMPOSE_CMD% exec app php artisan wayfinder:generate --with-form
echo ✅ Wayfinder utvonalak generalva

REM ── 15. Cache urites ──────────────────────────────────────────
echo 🔧 Laravel cache-ek uritese...
%COMPOSE_CMD% exec app php artisan cache:clear
%COMPOSE_CMD% exec app php artisan config:clear
%COMPOSE_CMD% exec app php artisan route:clear
%COMPOSE_CMD% exec app php artisan view:clear
echo ✅ Cache-ek uritve

REM ── 16. Laravel Boost telepites ────────────────────────────────
echo 🔧 Laravel Boost MCP telepitese...
%COMPOSE_CMD% exec %EXEC_FLAGS% app php artisan boost:install
echo ✅ Laravel Boost telepitve

REM ── Befejezes ──────────────────────────────────────────────────
echo.
echo ======================================================
echo    🚀 Kiadoi verzio elinditva!
echo ======================================================
echo.
echo 🌐 Elerhetosegek:
echo    App:        http://onlyfix.local
echo    Mailpit:    http://mailpit.onlyfix.local:8025
echo    phpMyAdmin: http://phpmyadmin.onlyfix.local:8080
echo.
echo 🔑 Teszt fiokok:
echo    Admin:    admin@example.com / password
echo    Mechanic: mechanic@example.com / password
echo    User:     test@example.com / password
echo.

endlocal
