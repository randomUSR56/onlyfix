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
    echo -e "${GREEN}✅ $*${NC}"
}

print_error() {
    echo -e "${RED}❌ $*${NC}"
    exit 1
}

print_step() {
    echo -e "${CYAN}🔧 $*${NC}"
}

# ─── Docker ellenőrzés ────────────────────────────────────────────

check_docker() {
    print_step "Docker ellenőrzése..."

    if ! command -v docker &>/dev/null; then
        echo -e "${RED}❌ Docker nincs telepítve!${NC}"
        echo -e "${YELLOW}   Telepítsd a Docker Desktop-ot: https://docs.docker.com/get-docker/${NC}"
        exit 1
    fi

    if ! docker info &>/dev/null; then
        echo -e "${RED}❌ Docker daemon nem fut!${NC}"
        echo -e "${YELLOW}   Indítsd el a Docker Desktop alkalmazást és próbáld újra.${NC}"
        exit 1
    fi

    print_success "Docker elérhető és fut"
}

# ─── Docker Compose detektálás ────────────────────────────────────

detect_compose() {
    if command -v docker-compose &>/dev/null; then
        COMPOSE_CMD="docker-compose"
    elif docker compose version &>/dev/null; then
        COMPOSE_CMD="docker compose"
    else
        echo -e "${RED}❌ Docker Compose nem található!${NC}"
        echo -e "${YELLOW}   Telepítsd: https://docs.docker.com/compose/install/${NC}"
        exit 1
    fi

    print_success "Docker Compose: $COMPOSE_CMD"
}
