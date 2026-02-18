#!/bin/bash
# OnlyFix Unix Setup Script (Linux/macOS)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${GREEN}🚀 OnlyFix Unix Setup${NC}"
echo -e "${GREEN}=====================${NC}\n"

# Detect platform
OS="$(uname -s)"
case "${OS}" in
    Linux*)     PLATFORM=Linux;;
    Darwin*)    PLATFORM=macOS;;
    *)          PLATFORM="UNKNOWN:${OS}"
esac

echo -e "${CYAN}Platform: ${PLATFORM}${NC}\n"

# Check root privileges
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ sudo privileges required!${NC}"
    echo -e "${YELLOW}Run: sudo ./scripts/setup-unix.sh${NC}"
    echo -e "${CYAN}Or: sudo make setup${NC}\n"
    exit 1
fi

# Loopback interface setup
echo -e "${CYAN}🔧 Configuring loopback interfaces...${NC}"

if [ "$PLATFORM" = "Linux" ]; then
    # Linux loopback setup
    for ip in 127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5; do
        if ! ip addr show lo | grep -q "$ip"; then
            ip addr add $ip/8 dev lo
            echo -e "${GREEN}  ✅ Added $ip${NC}"
        else
            echo -e "${YELLOW}  ⚠️  $ip already exists${NC}"
        fi
    done
    
    # Create systemd service for persistence
    cat > /etc/systemd/system/onlyfix-loopback.service << 'EOF'
[Unit]
Description=OnlyFix Loopback Interfaces
After=network.target

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/bin/bash -c 'for ip in 127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5; do ip addr add $ip/8 dev lo 2>/dev/null || true; done'
ExecStop=/bin/bash -c 'for ip in 127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5; do ip addr del $ip/8 dev lo 2>/dev/null || true; done'

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable onlyfix-loopback.service
    systemctl start onlyfix-loopback.service
    echo -e "${GREEN}✅ Systemd service created and enabled${NC}\n"
    
elif [ "$PLATFORM" = "macOS" ]; then
    # macOS loopback setup
    for ip in 127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5; do
        if ! ifconfig lo0 | grep -q "$ip"; then
            ifconfig lo0 alias $ip
            echo -e "${GREEN}  ✅ Added $ip${NC}"
        else
            echo -e "${YELLOW}  ⚠️  $ip already exists${NC}"
        fi
    done
    
    # Create LaunchDaemon for persistence
    cat > /Library/LaunchDaemons/local.onlyfix.loopback.plist << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>local.onlyfix.loopback</string>
    <key>ProgramArguments</key>
    <array>
        <string>/bin/sh</string>
        <string>-c</string>
        <string>for ip in 127.0.1.1 127.0.1.2 127.0.1.3 127.0.1.4 127.0.1.5; do ifconfig lo0 alias $ip; done</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
</dict>
</plist>
EOF
    
    launchctl load /Library/LaunchDaemons/local.onlyfix.loopback.plist 2>/dev/null || true
    echo -e "${GREEN}✅ LaunchDaemon created and loaded${NC}\n"
fi

# Hosts file configuration
HOSTS_FILE="/etc/hosts"
HOSTS_BACKUP="/etc/hosts.backup.$(date +%Y%m%d_%H%M%S)"

echo -e "${CYAN}📋 Creating hosts file backup...${NC}"
cp "$HOSTS_FILE" "$HOSTS_BACKUP"
echo -e "${GREEN}✅ Backup saved: $HOSTS_BACKUP${NC}\n"

# OnlyFix hosts entries
HOSTS_ENTRIES="
# OnlyFix Project - Docker Services
127.0.1.1       onlyfix.local
127.0.1.2       db.onlyfix.local
127.0.1.3       mailpit.onlyfix.local
127.0.1.4       node.onlyfix.local
127.0.1.5       phpmyadmin.onlyfix.local
"

# Check if entries already exist
if grep -q "OnlyFix Project" "$HOSTS_FILE"; then
    echo -e "${YELLOW}⚠️  OnlyFix hosts entries already exist!${NC}"
    read -p "Overwrite? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Remove old entries
        sed -i.bak '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null || \
        sed -i '' '/# OnlyFix Project/,/^$/d' "$HOSTS_FILE" 2>/dev/null
        
        # Add new entries
        echo "$HOSTS_ENTRIES" >> "$HOSTS_FILE"
        echo -e "${GREEN}✅ Hosts file updated!${NC}\n"
    else
        echo -e "${YELLOW}⏭️  Skipping hosts modification${NC}\n"
    fi
else
    echo "$HOSTS_ENTRIES" >> "$HOSTS_FILE"
    echo -e "${GREEN}✅ Hosts file updated!${NC}\n"
fi

# Flush DNS cache
echo -e "${CYAN}🔄 Flushing DNS cache...${NC}"

if [ "$PLATFORM" = "macOS" ]; then
    dscacheutil -flushcache 2>/dev/null || true
    killall -HUP mDNSResponder 2>/dev/null || true
elif [ "$PLATFORM" = "Linux" ]; then
    if command -v systemd-resolve &> /dev/null; then
        systemd-resolve --flush-caches 2>/dev/null || true
    elif command -v nscd &> /dev/null; then
        nscd -i hosts 2>/dev/null || true
    fi
fi

echo -e "${GREEN}✅ DNS cache cleared!${NC}\n"

# Test hosts configuration
echo -e "${CYAN}🧪 Testing hosts configuration...${NC}\n"

DOMAINS=(
    "onlyfix.local"
    "db.onlyfix.local"
    "mailpit.onlyfix.local"
    "node.onlyfix.local"
    "phpmyadmin.onlyfix.local"
)

for domain in "${DOMAINS[@]}"; do
    if ping -c 1 -W 1 "$domain" &> /dev/null; then
        echo -e "  ${GREEN}✅ $domain${NC}"
    else
        echo -e "  ${YELLOW}⚠️  $domain (not responding)${NC}"
    fi
done

# Check Docker
echo -e "\n${CYAN}🐳 Checking Docker...${NC}"

if command -v docker &> /dev/null; then
    DOCKER_VERSION=$(docker --version)
    echo -e "${GREEN}✅ Docker installed: $DOCKER_VERSION${NC}"
    
    if command -v docker-compose &> /dev/null; then
        COMPOSE_VERSION=$(docker-compose --version)
        echo -e "${GREEN}✅ Docker Compose installed: $COMPOSE_VERSION${NC}\n"
    else
        echo -e "${RED}❌ Docker Compose not installed!${NC}"
        echo -e "${YELLOW}Install: https://docs.docker.com/compose/install/${NC}\n"
        exit 1
    fi
else
    echo -e "${RED}❌ Docker not installed or not running!${NC}"
    echo -e "${YELLOW}Install: https://docs.docker.com/get-docker/${NC}\n"
    exit 1
fi

# Check .env file
echo -e "${CYAN}⚙️  Checking .env file...${NC}"

if [ -f "onlyfix/.env" ]; then
    echo -e "${GREEN}✅ .env file exists${NC}\n"
else
    if [ -f "onlyfix/.env.example" ]; then
        cp "onlyfix/.env.example" "onlyfix/.env"
        echo -e "${GREEN}✅ .env file created from .env.example${NC}\n"
    else
        echo -e "${YELLOW}⚠️  .env.example not found!${NC}\n"
    fi
fi

# Check Node.js
echo -e "${CYAN}📦 Checking Node.js...${NC}"

if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo -e "${GREEN}✅ Node.js installed: $NODE_VERSION${NC}"
    
    # Install NPM dependencies on host (for IntelliSense)
    echo -e "${CYAN}📦 Installing NPM dependencies on host (for VS Code IntelliSense)...${NC}"
    cd onlyfix
    if npm install &> /dev/null; then
        echo -e "${GREEN}✅ NPM dependencies installed on host${NC}\n"
    else
        echo -e "${YELLOW}⚠️  NPM install failed (non-critical)${NC}\n"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠️  Node.js not installed!${NC}"
    echo -e "${CYAN}Install: https://nodejs.org/${NC}\n"
    echo -e "${YELLOW}Note: Docker will still work, but VS Code IntelliSense may not work properly.${NC}\n"
fi

# Summary
echo -e "${GREEN}🎉 Setup completed!${NC}\n"
echo -e "${CYAN}Next steps:${NC}"
echo -e "  make build       - Build Docker images"
echo -e "  make start       - Start containers"
echo -e "  make install     - Install dependencies"
echo -e "  make migrate     - Run database migrations"
echo -e "\nOr simply run:"
echo -e "  ${YELLOW}make init${NC}        - Complete automatic setup\n"
echo -e "${CYAN}Access URLs:${NC}"
echo -e "  🌐 http://onlyfix.local"
echo -e "  📧 http://mailpit.onlyfix.local:8025"
echo -e "  🗄️  http://phpmyadmin.onlyfix.local:8080\n"
