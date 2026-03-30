#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

source "$SCRIPT_DIR/helpers.sh"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   OnlyFix – Kiadói build                 ║${NC}"
echo -e "${GREEN}║   (localhost, nincs sudo)                ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""

# ── 1. Docker ellenőrzés ──────────────────────────────────────────
check_docker
detect_compose

# ── 2. .env fájl ellenőrzés ──────────────────────────────────────
print_step ".env fájl ellenőrzése..."
if [ ! -f "onlyfix/.env" ]; then
    if [ -f "onlyfix/.env.example" ]; then
        cp "onlyfix/.env.example" "onlyfix/.env"
        print_success ".env fájl létrehozva (.env.example alapján)"
    else
        echo -e "${YELLOW}⚠️  .env.example nem található!${NC}"
    fi
else
    print_success ".env fájl már létezik"
fi

# ── 3. Docker compose fájlok meghatározása ─────────────────────
COMPOSE_FILES="-f docker-compose.yml"
if [ -f "docker-compose.local.yml" ]; then
    COMPOSE_FILES="$COMPOSE_FILES -f docker-compose.local.yml"
    print_success "Lokális override fájl megtalálva (docker-compose.local.yml)"
fi

# ── 4. Régi konténerek és volume-ok eltávolítása ────────────────
print_step "Régi konténerek és volume-ok eltávolítása..."
$COMPOSE_CMD $COMPOSE_FILES down -v 2>/dev/null || true
print_success "Tiszta állapot előkészítve"

# ── 5. Docker images build (production) ──────────────────────────
print_step "Docker image-ek építése (production)..."
$COMPOSE_CMD $COMPOSE_FILES build --no-cache
print_success "Docker image-ek elkészültek (production)"

# ── 5. Konténerek indítása ───────────────────────────────────────
print_step "Konténerek indítása..."
$COMPOSE_CMD $COMPOSE_FILES up -d
print_success "Konténerek elindultak"

# ── 5. Composer install ──────────────────────────────────────────
print_step "Composer függőségek telepítése..."
$COMPOSE_CMD $COMPOSE_FILES exec -it app composer install
print_success "Composer függőségek telepítve"

# ── 6. NPM install + production build ───────────────────────────
print_step "NPM függőségek telepítése és production build..."
(cd onlyfix && npm install && npm run build)
print_success "NPM production build elkészült"

# ── 7. Laravel app key generálás ─────────────────────────────────
print_step "Laravel alkalmazáskulcs generálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -it app php artisan key:generate
print_success "Alkalmazáskulcs generálva"

# ── 8. Migrate (adatok megmaradnak) ─────────────────────────────
print_step "Adatbázis migrálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -it app php artisan migrate
print_success "Adatbázis migrálva"

# ── 9. Storage link ─────────────────────────────────────────────
print_step "Storage link létrehozása..."
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan storage:link
print_success "Storage link létrehozva"

# ── 10. Wayfinder route generálás ────────────────────────────────
print_step "Wayfinder útvonalak generálása..."
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan wayfinder:generate --with-form
print_success "Wayfinder útvonalak generálva"

# ── 11. Cache ürítés ─────────────────────────────────────────────
print_step "Laravel cache-ek ürítése..."
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan cache:clear
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan config:clear
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan route:clear
$COMPOSE_CMD $COMPOSE_FILES exec app php artisan view:clear
print_success "Cache-ek ürítve"

# ── 12. Laravel Boost telepítés ──────────────────────────────────
print_step "Laravel Boost MCP telepítése..."
$COMPOSE_CMD $COMPOSE_FILES exec -it app php artisan boost:install
print_success "Laravel Boost telepítve"

# ── Befejezés ────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   🚀 Kiadói verzió elindítva!            ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "🌐 Elérhetőségek:"
echo -e "   App:        ${CYAN}http://localhost${NC}"
echo -e "   Mailpit:    ${CYAN}http://localhost:8025${NC}"
echo -e "   phpMyAdmin: ${CYAN}http://localhost:8080${NC}"
echo ""
echo -e "🔑 Teszt fiókok:"
echo -e "   Admin:    admin@example.com / password"
echo -e "   Mechanic: mechanic@example.com / password"
echo -e "   User:     test@example.com / password"
echo ""
