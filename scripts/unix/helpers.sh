#!/usr/bin/env bash
# OnlyFix - Közös segédfüggvények
# Ezt a fájlt a többi szkript source-olja, ne futtasd közvetlenül.

# Színek
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ─── Kimeneti függvények ───────────────────────────────────────────

print_success() {
    echo -e "${GREEN}[  OK] $*${NC}"
}

print_error() {
    echo -e "${RED}[ERROR] $*${NC}"
}

print_step() {
    echo -e "${CYAN}[STEP] $*${NC}"
}

print_warn() {
    echo -e "${YELLOW}[WARN] $*${NC}"
}

print_info() {
    echo -e "[ INFO] $*"
}

# ─── Docker ellenőrzés ────────────────────────────────────────────

check_docker() {
    print_step "Docker ellenőrzése..."

    if ! command -v docker &>/dev/null; then
        print_error "Docker nincs telepítve!"
        echo -e "${YELLOW}   Telepítsd a Docker Desktop-ot: https://docs.docker.com/get-docker/${NC}"
        exit 1
    fi

    if ! docker info &>/dev/null; then
        print_error "Docker daemon nem fut!"
        echo -e "${YELLOW}   Indítsd el a Docker Desktop alkalmazást és próbáld újra.${NC}"
        exit 1
    fi

    print_success "Docker elérhető és fut"
}

# ─── Docker Compose detektálás ────────────────────────────────────

detect_compose() {
    if docker compose version &>/dev/null; then
        COMPOSE_CMD="docker compose"
    elif command -v docker-compose &>/dev/null; then
        COMPOSE_CMD="docker-compose"
    else
        print_error "Docker Compose nem található!"
        echo -e "${YELLOW}   Telepítsd: https://docs.docker.com/compose/install/${NC}"
        exit 1
    fi

    print_success "Docker Compose: $COMPOSE_CMD"
}

# ─── MySQL readiness wait ─────────────────────────────────────────

wait_for_mysql() {
    local compose_flags="${1:-}"
    print_step "Várakozás a MySQL elindulására..."

    local db_ready=0
    for i in $(seq 1 60); do
        if $COMPOSE_CMD $compose_flags exec -T db mysqladmin ping -h localhost -u root --password=rootSecurePassword123! --silent &>/dev/null; then
            db_ready=1
            print_success "MySQL elérhető. Várakozás: ${i} másodperc."
            break
        else
            if (( i % 5 == 0 )); then
                print_info "Még várakozás a MySQL-re... ${i} másodperc"
            fi
            sleep 1
        fi
    done

    if [ "$db_ready" -eq 0 ]; then
        print_error "MySQL nem indult el 60 másodpercen belül."
        return 1
    fi
}

# ─── Rollback helper ──────────────────────────────────────────────

rollback() {
    local compose_flags="${1:-}"

    echo ""
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo -e "${RED}  [FAIL] Az inicializálás sikertelen. Visszaállítás...${NC}"
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo ""

    if [ "${CONTAINERS_STARTED:-0}" -eq 1 ]; then
        echo "[UNDO] Konténerek és volume-ok eltávolítása..."
        $COMPOSE_CMD $compose_flags down -v >/dev/null 2>&1 || true
        echo "[UNDO] Konténerek és volume-ok eltávolítva."
    fi

    if [ "${IMAGES_BUILT:-0}" -eq 1 ]; then
        echo "[UNDO] Docker image-ek eltávolítása..."
        $COMPOSE_CMD $compose_flags down --rmi local >/dev/null 2>&1 || true
        echo "[UNDO] Docker image-ek eltávolítva."
    fi

    if [ "${ENV_CREATED:-0}" -eq 1 ]; then
        if [ -f "$PROJECT_DIR/onlyfix/.env" ]; then
            rm -f "$PROJECT_DIR/onlyfix/.env"
            echo "[UNDO] .env fájl eltávolítva."
        fi
    fi

    if [ "${HOSTS_MODIFIED:-0}" -eq 1 ]; then
        echo "[UNDO] OnlyFix bejegyzések eltávolítása a hosts fájlból..."
        sudo sed -i.bak '/# OnlyFix Project/,/^$/d' /etc/hosts 2>/dev/null || \
        sudo sed -i '' '/# OnlyFix Project/,/^$/d' /etc/hosts 2>/dev/null || true
        echo "[UNDO] Hosts fájl megtisztítva."
    fi

    echo ""
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo -e "${RED}  Visszaállítás kész. Javítsd a fenti hibát és futtasd újra.${NC}"
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo ""
    exit 1
}

# ─── Checked exec: run a compose command and rollback on failure ──

checked_exec() {
    local compose_flags="$1"
    shift
    local description="$1"
    shift

    if ! $COMPOSE_CMD $compose_flags "$@"; then
        print_error "$description"
        rollback "$compose_flags"
    fi
}
