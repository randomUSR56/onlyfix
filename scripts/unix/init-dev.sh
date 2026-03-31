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
HOSTS_MODIFIED=0

# Override set -e: use trap to trigger rollback on any unhandled error
trap 'rollback ""' ERR

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   OnlyFix – Fejlesztoi inicializalas     ║${NC}"
echo -e "${GREEN}║   (nevfeloldassal, egyedi IP-vel)        ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo "   Project root: $PROJECT_DIR"
echo ""

# ── 1. Docker ellenorzés ──────────────────────────────────────────
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

# ── 3. Hosts fájl bejegyzések (sudo szükséges) ───────────────────
print_step "Hosts fájl konfigurálása..."

HOSTS_FILE="/etc/hosts"
HOSTS_MARKER="# OnlyFix Project - Docker Services"

HOSTS_ENTRIES="${HOSTS_MARKER}
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local"

if grep -q "OnlyFix Project" "$HOSTS_FILE" 2>/dev/null; then
    print_info "OnlyFix hosts bejegyzések már léteznek."
    read -p "Felülírod? (i/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ii]$ ]]; then
        sudo sed -i.bak '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null || \
        sudo sed -i '' '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null
        echo "" | sudo tee -a "$HOSTS_FILE" >/dev/null
        echo "$HOSTS_ENTRIES" | sudo tee -a "$HOSTS_FILE" >/dev/null
        HOSTS_MODIFIED=1
        print_success "Hosts fájl frissítve"
    else
        print_info "Hosts módosítás kihagyva"
    fi
else
    echo "" | sudo tee -a "$HOSTS_FILE" >/dev/null
    echo "$HOSTS_ENTRIES" | sudo tee -a "$HOSTS_FILE" >/dev/null
    HOSTS_MODIFIED=1
    print_success "Hosts fájl frissítve"
fi

# ── 4. Loopback IP-k beállítása (sudo szükséges) ─────────────────
print_step "Loopback IP-k beállítása..."

OS_TYPE="$(uname -s)"
LOOPBACK_IPS=(127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5)

for ip in "${LOOPBACK_IPS[@]}"; do
    if [ "$OS_TYPE" = "Darwin" ]; then
        if ! ifconfig lo0 | grep -q "$ip"; then
            sudo ifconfig lo0 alias "$ip"
            print_success "$ip hozzáadva"
        else
            print_info "$ip már létezik"
        fi
    else
        if ! ip addr show lo | grep -q "$ip"; then
            sudo ip addr add "$ip/8" dev lo
            print_success "$ip hozzáadva"
        else
            print_info "$ip már létezik"
        fi
    fi
done

# DNS cache ürítés
print_step "DNS cache ürítése..."
if [ "$OS_TYPE" = "Darwin" ]; then
    sudo dscacheutil -flushcache 2>/dev/null || true
    sudo killall -HUP mDNSResponder 2>/dev/null || true
else
    if command -v systemd-resolve &>/dev/null; then
        sudo systemd-resolve --flush-caches 2>/dev/null || true
    fi
fi
print_success "DNS cache ürítve"

# ── 5. .env fájl ellenőrzés ──────────────────────────────────────
print_step ".env fájl ellenőrzése..."
if [ ! -f "onlyfix/.env" ]; then
    cp "onlyfix/.env.example" "onlyfix/.env"
    if [ $? -ne 0 ]; then
        print_error ".env fájl létrehozása sikertelen."
        rollback ""
    fi
    ENV_CREATED=1
    print_success ".env fájl létrehozva (.env.example alapján)"
else
    print_success ".env fájl már létezik"
fi

# ── 6. Docker images build ───────────────────────────────────────
print_step "Docker image-ek építése..."
$COMPOSE_CMD build
if [ $? -ne 0 ]; then
    print_error "Docker build sikertelen."
    rollback ""
fi
IMAGES_BUILT=1
print_success "Docker image-ek elkészültek"

# ── 7. Konténerek indítása ───────────────────────────────────────
print_step "Konténerek indítása..."
$COMPOSE_CMD up -d
if [ $? -ne 0 ]; then
    print_error "Konténerek indítása sikertelen."
    rollback ""
fi
CONTAINERS_STARTED=1
print_success "Konténerek elindultak"

# ── 8. Wait for MySQL readiness ──────────────────────────────────
wait_for_mysql "" || rollback ""

# ── 9. Composer install ──────────────────────────────────────────
print_step "Composer függőségek telepítése..."
$COMPOSE_CMD exec -T app composer install --no-interaction --optimize-autoloader
if [ $? -ne 0 ]; then
    print_error "Composer install sikertelen."
    rollback ""
fi
print_success "Composer függőségek telepítve"

# ── 10. Laravel app key generálás ─────────────────────────────────
print_step "Laravel alkalmazáskulcs generálása..."
$COMPOSE_CMD exec -T app php artisan key:generate --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Alkalmazáskulcs generálás sikertelen."
    rollback ""
fi
print_success "Alkalmazáskulcs generálva"

# ── 11. Migrate fresh + seed ─────────────────────────────────────
print_step "Adatbázis migrálása és seedelése..."
$COMPOSE_CMD exec -T app php artisan migrate:fresh --seed --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Adatbázis migrálás sikertelen."
    rollback ""
fi
print_success "Adatbázis migrálva és seedelve"

# ── 12. Storage link ─────────────────────────────────────────────
print_step "Storage link létrehozása..."
$COMPOSE_CMD exec -T app php artisan storage:link --force --no-interaction
if [ $? -ne 0 ]; then
    print_error "Storage link létrehozása sikertelen."
    rollback ""
fi
print_success "Storage link létrehozva"

# ── 13. Wayfinder route generálás ────────────────────────────────
print_step "Wayfinder útvonalak generálása..."
$COMPOSE_CMD exec -T app php artisan wayfinder:generate --with-form --no-interaction
if [ $? -ne 0 ]; then
    print_error "Wayfinder generálás sikertelen."
    rollback ""
fi
print_success "Wayfinder útvonalak generálva"

# ── 14. NPM install + Vite build (fresh node container) ──────────
print_step "Frontend assets építése (npm install + vite build)..."
$COMPOSE_CMD run --rm node sh -c "npm install && npm run build"
if [ $? -ne 0 ]; then
    print_error "Frontend build sikertelen."
    rollback ""
fi
print_success "Frontend assets elkészültek"

# ── Befejezés ────────────────────────────────────────────────────
# Disable ERR trap for successful completion
trap - ERR

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   [OK] OnlyFix sikeresen inicializálva!  ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "Elérhetőségek:"
echo -e "   App:        ${CYAN}http://onlyfix.local${NC}"
echo -e "   Mailpit:    ${CYAN}http://mailpit.onlyfix.local:8025${NC}"
echo -e "   phpMyAdmin: ${CYAN}http://phpmyadmin.onlyfix.local:8080${NC}"
echo ""
echo -e "Teszt fiókok:"
echo -e "   Admin:    admin@example.com / password"
echo -e "   Mechanic: mechanic@example.com / password"
echo -e "   User:     test@example.com / password"
echo ""
echo "  --------------------------------------------------------"
echo "  UNDO / eltávolítás:"
echo "    1. cd $PROJECT_DIR"
echo "    2. $COMPOSE_CMD down -v --rmi local"
echo "    3. Töröld az OnlyFix blokkot a /etc/hosts fájlból"
echo "    4. Töröld az onlyfix/.env fájlt ha tiszta újrakezdést akarsz"
echo "  --------------------------------------------------------"
echo ""
