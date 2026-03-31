#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

source "$SCRIPT_DIR/helpers.sh"

# ── Rollback tracking flags ─────────────────────────────────────
CONTAINERS_STARTED=0
IMAGES_BUILT=0
ENV_CREATED=0

# ── Compose file flags ──────────────────────────────────────────
COMPOSE_FILES="-f docker-compose.yml"

# Override set -e: use trap to trigger rollback on any unhandled error
trap 'rollback "$COMPOSE_FILES"' ERR

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   OnlyFix – Kiadoi build                 ║${NC}"
echo -e "${GREEN}║   (localhost, nincs sudo)                ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo "   Project root: $PROJECT_DIR"
echo ""

# ── 1. Docker ellenőrzés ──────────────────────────────────────────
check_docker
detect_compose

# ── 2. Verify project files exist ──────────────────────────────────
print_step "Projekt fájlok ellenőrzése..."
if [ ! -f "docker-compose.yml" ]; then
    print_error "docker-compose.yml nem található: $PROJECT_DIR/docker-compose.yml"
    exit 1
fi
if [ ! -f "onlyfix/.env.example" ]; then
    print_error ".env.example nem található: $PROJECT_DIR/onlyfix/.env.example"
    exit 1
fi
print_success "Projekt fájlok megtalálva"

# ── 3. .env fájl ellenőrzés ──────────────────────────────────────
print_step ".env fájl ellenőrzése..."
if [ ! -f "onlyfix/.env" ]; then
    cp "onlyfix/.env.example" "onlyfix/.env"
    if [ $? -ne 0 ]; then
        print_error ".env fájl létrehozása sikertelen."
        rollback "$COMPOSE_FILES"
    fi
    ENV_CREATED=1
    print_success ".env fájl létrehozva (.env.example alapján)"
else
    print_success ".env fájl már létezik"
fi

# ── 4. Docker compose fájlok meghatározása ─────────────────────
if [ -f "docker-compose.local.yml" ]; then
    COMPOSE_FILES="$COMPOSE_FILES -f docker-compose.local.yml"
    print_success "Lokális override fájl megtalálva (docker-compose.local.yml)"
fi

# ── 5. Régi konténerek és volume-ok eltávolítása ────────────────
print_step "Régi konténerek és volume-ok eltávolítása..."
$COMPOSE_CMD $COMPOSE_FILES down -v 2>/dev/null || true
print_success "Tiszta állapot előkészítve"

# ── 6. Docker images build (production) ──────────────────────────
print_step "Docker image-ek építése (production, no-cache)..."
$COMPOSE_CMD $COMPOSE_FILES build --no-cache
if [ $? -ne 0 ]; then
    print_error "Docker build sikertelen."
    rollback "$COMPOSE_FILES"
fi
IMAGES_BUILT=1
print_success "Docker image-ek elkészültek (production)"

# ── 7. Konténerek indítása ───────────────────────────────────────
print_step "Konténerek indítása..."
$COMPOSE_CMD $COMPOSE_FILES up -d
if [ $? -ne 0 ]; then
    print_error "Konténerek indítása sikertelen."
    rollback "$COMPOSE_FILES"
fi
CONTAINERS_STARTED=1
print_success "Konténerek elindultak"

# ── 8. Wait for MySQL readiness ──────────────────────────────────
wait_for_mysql "$COMPOSE_FILES" || rollback "$COMPOSE_FILES"

# ── 9. Composer install ──────────────────────────────────────────
print_step "Composer függőségek telepítése..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app composer install --no-interaction --optimize-autoloader
if [ $? -ne 0 ]; then
    print_error "Composer install sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Composer függőségek telepítve"

# ── 10. Laravel app key generálás ─────────────────────────────────
print_step "Laravel alkalmazáskulcs generálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan key:generate --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Alkalmazáskulcs generálás sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Alkalmazáskulcs generálva"

# ── 11. Migrate (non-destructive for release) ─────────────────────
print_step "Adatbázis migrálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan migrate --seed --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Adatbázis migrálás sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Adatbázis migrálva"

# ── 12. NPM install + production build (inside container) ─────────
print_step "Frontend assets építése (production)..."
$COMPOSE_CMD $COMPOSE_FILES run --rm node sh -c "npm install && npm run build"
if [ $? -ne 0 ]; then
    print_error "Frontend build sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Frontend assets elkészültek"

# ── 13. Storage link ─────────────────────────────────────────────
print_step "Storage link létrehozása..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan storage:link --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Storage link létrehozása sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Storage link létrehozva"

# ── 14. Wayfinder route generálás ────────────────────────────────
print_step "Wayfinder útvonalak generálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan wayfinder:generate --with-form --no-interaction
if [ $? -ne 0 ]; then
    print_error "Wayfinder generálás sikertelen."
    rollback "$COMPOSE_FILES"
fi
print_success "Wayfinder útvonalak generálva"

# ── 15. Clear and rebuild caches (production optimization) ────────
print_step "Laravel cache-ek optimalizálása..."
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan config:clear --no-interaction
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan route:clear --no-interaction
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan view:clear --no-interaction
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan config:cache --no-interaction
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan route:cache --no-interaction
$COMPOSE_CMD $COMPOSE_FILES exec -T app php artisan view:cache --no-interaction
print_success "Laravel cache-ek optimalizálva"

# ── Befejezés ────────────────────────────────────────────────────
# Disable ERR trap for successful completion
trap - ERR

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   [OK] Kiadoi verzió elindítva!          ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "Elérhetőségek:"
echo -e "   App:        ${CYAN}http://localhost${NC}"
echo -e "   Mailpit:    ${CYAN}http://localhost:8025${NC}"
echo -e "   phpMyAdmin: ${CYAN}http://localhost:8080${NC}"
echo ""
echo -e "Teszt fiókok:"
echo -e "   Admin:    admin@example.com / password"
echo -e "   Mechanic: mechanic@example.com / password"
echo -e "   User:     test@example.com / password"
echo ""
echo "  --------------------------------------------------------"
echo "  UNDO / eltávolítás:"
echo "    1. cd $PROJECT_DIR"
echo "    2. $COMPOSE_CMD $COMPOSE_FILES down -v --rmi local"
echo "    3. Töröld az onlyfix/.env fájlt ha tiszta újrakezdést akarsz"
echo "  --------------------------------------------------------"
echo ""
