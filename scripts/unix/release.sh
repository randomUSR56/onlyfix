#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_DIR"

source "$SCRIPT_DIR/helpers.sh"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   OnlyFix – Kiadói build                 ║${NC}"
echo -e "${GREEN}║   (névfeloldással, egyedi IP-vel)        ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""

# ── 1. Docker ellenőrzés ──────────────────────────────────────────
check_docker
detect_compose

# ── 2. Hosts fájl bejegyzések (sudo szükséges) ───────────────────
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
    echo -e "${YELLOW}⚠️  OnlyFix hosts bejegyzések már léteznek.${NC}"
    read -p "Felülírod? (i/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ii]$ ]]; then
        sudo sed -i.bak '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null || \
        sudo sed -i '' '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null
        echo "" | sudo tee -a "$HOSTS_FILE" >/dev/null
        echo "$HOSTS_ENTRIES" | sudo tee -a "$HOSTS_FILE" >/dev/null
        print_success "Hosts fájl frissítve"
    else
        echo -e "${YELLOW}⏭️  Hosts módosítás kihagyva${NC}"
    fi
else
    echo "" | sudo tee -a "$HOSTS_FILE" >/dev/null
    echo "$HOSTS_ENTRIES" | sudo tee -a "$HOSTS_FILE" >/dev/null
    print_success "Hosts fájl frissítve"
fi

# ── 3. Loopback IP-k beállítása (sudo szükséges) ─────────────────
print_step "Loopback IP-k beállítása..."

OS_TYPE="$(uname -s)"
LOOPBACK_IPS=(127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5)

for ip in "${LOOPBACK_IPS[@]}"; do
    if [ "$OS_TYPE" = "Darwin" ]; then
        if ! ifconfig lo0 | grep -q "$ip"; then
            sudo ifconfig lo0 alias "$ip"
            echo -e "  ${GREEN}✅ $ip hozzáadva${NC}"
        else
            echo -e "  ${YELLOW}⚠️  $ip már létezik${NC}"
        fi
    else
        if ! ip addr show lo | grep -q "$ip"; then
            sudo ip addr add "$ip/8" dev lo
            echo -e "  ${GREEN}✅ $ip hozzáadva${NC}"
        else
            echo -e "  ${YELLOW}⚠️  $ip már létezik${NC}"
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

# ── 4. .env fájl ellenőrzés ──────────────────────────────────────
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

# ── 5. Docker images build (production) ──────────────────────────
print_step "Docker image-ek építése (production)..."
$COMPOSE_CMD build --no-cache
print_success "Docker image-ek elkészültek (production)"

# ── 6. Konténerek indítása ───────────────────────────────────────
print_step "Konténerek indítása..."
$COMPOSE_CMD up -d
print_success "Konténerek elindultak"

# ── 7. Composer install ──────────────────────────────────────────
print_step "Composer függőségek telepítése..."
$COMPOSE_CMD exec -it app composer install
print_success "Composer függőségek telepítve"

# ── 8. NPM install + production build ───────────────────────────
print_step "NPM függőségek telepítése és production build..."
$COMPOSE_CMD exec -it app npm install
$COMPOSE_CMD exec -it app npm run build
print_success "NPM production build elkészült"

# ── 9. Laravel app key generálás ─────────────────────────────────
print_step "Laravel alkalmazáskulcs generálása..."
$COMPOSE_CMD exec -it app php artisan key:generate
print_success "Alkalmazáskulcs generálva"

# ── 10. Migrate (adatok megmaradnak) ─────────────────────────────
print_step "Adatbázis migrálása..."
$COMPOSE_CMD exec -it app php artisan migrate
print_success "Adatbázis migrálva"

# ── 11. Storage link ─────────────────────────────────────────────
print_step "Storage link létrehozása..."
$COMPOSE_CMD exec app php artisan storage:link
print_success "Storage link létrehozva"

# ── 12. Wayfinder route generálás ────────────────────────────────
print_step "Wayfinder útvonalak generálása..."
$COMPOSE_CMD exec app php artisan wayfinder:generate --with-form
print_success "Wayfinder útvonalak generálva"

# ── 13. Cache ürítés ─────────────────────────────────────────────
print_step "Laravel cache-ek ürítése..."
$COMPOSE_CMD exec app php artisan cache:clear
$COMPOSE_CMD exec app php artisan config:clear
$COMPOSE_CMD exec app php artisan route:clear
$COMPOSE_CMD exec app php artisan view:clear
print_success "Cache-ek ürítve"

# ── 14. Laravel Boost telepítés ──────────────────────────────────
print_step "Laravel Boost MCP telepítése..."
$COMPOSE_CMD exec -it app php artisan boost:install
print_success "Laravel Boost telepítve"

# ── Befejezés ────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   🚀 Kiadói verzió elindítva!            ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "🌐 Elérhetőségek:"
echo -e "   App:        ${CYAN}http://onlyfix.local${NC}"
echo -e "   Mailpit:    ${CYAN}http://mailpit.onlyfix.local:8025${NC}"
echo -e "   phpMyAdmin: ${CYAN}http://phpmyadmin.onlyfix.local:8080${NC}"
echo ""
echo -e "🔑 Teszt fiókok:"
echo -e "   Admin:    admin@example.com / password"
echo -e "   Mechanic: mechanic@example.com / password"
echo -e "   User:     test@example.com / password"
echo ""
