#!/bin/bash
# =============================================================================
# vps-init.sh — Wire GitHub repo to VPS
# Run once after repo is cloned: bash vps-init.sh
# =============================================================================

set -e
GREEN='\033[0;32m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
log()  { echo -e "${GREEN}[OK]${NC}  $1"; }
info() { echo -e "${CYAN}[..]${NC}  $1"; }

REPO_DIR="/var/www/isekai-pool-api"
API_DIR="$REPO_DIR/api"

# =============================================================================
# Clone repo
# =============================================================================
info "Cloning repo..."
if [ ! -d "$REPO_DIR" ]; then
    git clone git@github.com:jeromeheuze/isekai-pool-api.git "$REPO_DIR"
else
    cd "$REPO_DIR" && git pull origin main
fi
log "Repo at $REPO_DIR"

# =============================================================================
# Laravel setup
# =============================================================================
info "Setting up Laravel API..."
cd "$API_DIR"

composer install --no-dev --optimize-autoloader --quiet

# .env from template if not exists
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
    log ".env created"
fi

# Inject RPC credentials from node configs
inject_rpc() {
    local KEY=$1 CONF=$2 VAR=$3
    if [ -f "$CONF" ]; then
        VAL=$(grep "$KEY" "$CONF" | cut -d= -f2)
        sed -i "s|^${VAR}=.*|${VAR}=${VAL}|" .env
    fi
}

inject_rpc "rpcpassword" "/home/crypto/.yenten/yenten.conf"   "YTN_RPC_PASS"
inject_rpc "rpcpassword" "/home/crypto/.koto/koto.conf"       "KOTO_RPC_PASS"
inject_rpc "rpcpassword" "/home/crypto/.tidecoin/tidecoin.conf" "TDC_RPC_PASS"

php artisan config:cache
php artisan route:cache

chown -R www-data:www-data "$REPO_DIR"
chmod -R 755 "$API_DIR/storage" "$API_DIR/bootstrap/cache"
log "Laravel ready"

# =============================================================================
# Nginx
# =============================================================================
info "Configuring nginx..."
cp "$REPO_DIR/nginx.conf" /etc/nginx/sites-available/isekai-pool
ln -sf /etc/nginx/sites-available/isekai-pool /etc/nginx/sites-enabled/isekai-pool
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
log "Nginx configured"

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}VPS init complete.${NC}"
echo ""
echo "Test the API:"
echo "  curl https://api.isekai-pool.com/api/v1/health"
echo ""
echo "Site is live at:"
echo "  https://isekai-pool.com"
echo ""
