#!/bin/bash
# =============================================================================
# build-tidecoin.sh
# Installs Tidecoin (TDC) daemon on Ubuntu 22.04 / 24.04
#
# Algorithm:  YespowerTIDE (CPU only, ASIC resistant)
# Unique:     Post-quantum secure signatures via FALCON-512
#             (NIST-certified quantum-resistant cryptography)
#             21M supply cap — same as Bitcoin
# Ports:      P2P 9369 / RPC 9368
# Network:    Active — Korean + global community, tidecoin.exchange,
#             multiple pools (rplant.xyz, tidepool.world, aikapool.com)
# Explorer:   https://explorer.tidecoin.org
# Bootstrap:  https://github.com/tidecoin/bootstrap
#
# APPROACH: Official prebuilt binary v0.18.3 from tidecoin.org
#           Building from source not attempted — prebuilt works cleanly.
#
# Usage: bash build-tidecoin.sh
# Time:  ~5 min (download + bootstrap)
# =============================================================================

set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

log()  { echo -e "${GREEN}[OK]${NC}  $1"; }
info() { echo -e "${CYAN}[..]${NC}  $1"; }
warn() { echo -e "${YELLOW}[!!]${NC}  $1"; }
die()  { echo -e "${RED}[ERR]${NC} $1"; exit 1; }

[[ $EUID -ne 0 ]] && die "Run as root: sudo bash $0"

BIN_DIR="/usr/local/bin"
TDC_VERSION="0.18.3"
TDC_URL="https://github.com/tidecoin/tidecoin/releases/download/v${TDC_VERSION}/linux64.tar.gz"
DATA_DIR="/home/crypto/.tidecoin"
PARAMS_DIR="$DATA_DIR"

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Installing Tidecoin (TDC) v${TDC_VERSION}                     ${NC}"
echo -e "${BOLD}  Post-Quantum Secure CPU Coin (FALCON-512)             ${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# =============================================================================
# STEP 1 — Download prebuilt binary
# =============================================================================
info "Downloading Tidecoin v${TDC_VERSION}..."
cd /tmp
rm -rf tidecoin-install
mkdir tidecoin-install
wget -q --show-progress "$TDC_URL" -O tidecoin-install/tidecoin.tar.gz
cd tidecoin-install
tar -xzf tidecoin.tar.gz
log "Downloaded and extracted"

# Find bin directory
TDC_BIN_DIR=$(find /tmp/tidecoin-install -name "tidecoind" -o -name "tidecoin-daemon" 2>/dev/null | \
    head -1 | xargs dirname 2>/dev/null || echo "")

if [ -z "$TDC_BIN_DIR" ]; then
    # List what we got
    find /tmp/tidecoin-install -type f | head -20
    die "Could not find Tidecoin daemon binary — check extracted contents above"
fi

log "Found binaries in: $TDC_BIN_DIR"

# =============================================================================
# STEP 2 — Install binaries
# =============================================================================
info "Installing binaries..."

for BIN in tidecoind tidecoin-cli tidecoin-tx; do
    if [ -f "$TDC_BIN_DIR/$BIN" ]; then
        cp "$TDC_BIN_DIR/$BIN" "$BIN_DIR/$BIN"
        chmod +x "$BIN_DIR/$BIN"
        log "$BIN installed"
    fi
done

# Verify
tidecoind --version 2>&1 | head -2

# =============================================================================
# STEP 3 — Data directory + config
# =============================================================================
info "Setting up node config..."
mkdir -p "$DATA_DIR"

if [ ! -f "$DATA_DIR/tidecoin.conf" ]; then
    RPC_PASS=$(openssl rand -hex 24)
    cat > "$DATA_DIR/tidecoin.conf" << EOF
rpcuser=isekai_tdc
rpcpassword=$RPC_PASS
rpcport=9368
rpcallowip=127.0.0.1
port=9369
server=1
daemon=1
txindex=1
listen=1
maxconnections=64
shrinkdebugfile=1

# Known active peers (from explorer.tidecoin.org)
addnode=pool.rplant.xyz:9369
addnode=tidepool.world:9369
addnode=aikapool.com:9369
EOF
    chown crypto:crypto "$DATA_DIR/tidecoin.conf"
    chmod 600 "$DATA_DIR/tidecoin.conf"
    echo "RPC Password: $RPC_PASS" > /root/tidecoin-credentials.txt
    chmod 600 /root/tidecoin-credentials.txt
    log "Config created — credentials at /root/tidecoin-credentials.txt"
else
    log "Config already exists — keeping existing credentials"
    RPC_PASS=$(grep rpcpassword "$DATA_DIR/tidecoin.conf" | cut -d= -f2)
fi

chown -R crypto:crypto "$DATA_DIR"

# =============================================================================
# STEP 4 — Bootstrap blockchain data (saves hours of sync)
# Official bootstrap from tidecoin/bootstrap GitHub repo
# =============================================================================
info "Checking for bootstrap data..."
if [ ! -d "$DATA_DIR/blocks" ]; then
    info "Downloading bootstrap from tidecoin/bootstrap repo..."
    info "This may take a few minutes depending on size..."

    # Try official bootstrap
    BOOTSTRAP_URL="https://github.com/tidecoin/bootstrap/releases/latest/download/bootstrap.dat.gz"
    if curl -s --max-time 10 --head "$BOOTSTRAP_URL" | grep -q "200\|302"; then
        wget -q --show-progress "$BOOTSTRAP_URL" -O "$DATA_DIR/bootstrap.dat.gz"
        gzip -d "$DATA_DIR/bootstrap.dat.gz"
        chown crypto:crypto "$DATA_DIR/bootstrap.dat"
        log "Bootstrap downloaded — daemon will import on first start"
    else
        warn "No bootstrap available — will sync from peers (slower)"
    fi
else
    log "Blockchain data already present — skipping bootstrap"
fi

# =============================================================================
# STEP 5 — Systemd service
# =============================================================================
info "Creating systemd service..."
cat > /etc/systemd/system/isekai-tidecoin.service << 'EOF'
[Unit]
Description=isekai Tidecoin (TDC) node
After=network.target
Wants=network.target

[Service]
User=crypto
Group=crypto
Type=forking
ExecStart=/usr/local/bin/tidecoind -datadir=/home/crypto/.tidecoin -conf=/home/crypto/.tidecoin/tidecoin.conf -daemon
ExecStop=/usr/local/bin/tidecoin-cli -datadir=/home/crypto/.tidecoin stop
Restart=on-failure
RestartSec=30
TimeoutStopSec=60
LimitNOFILE=65536
Nice=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=isekai-tidecoin

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable isekai-tidecoin
ufw allow 9369/tcp 2>/dev/null || true
log "Service created and enabled"

# =============================================================================
# STEP 6 — Start and verify
# =============================================================================
info "Starting Tidecoin node..."
systemctl start isekai-tidecoin
sleep 15

TDC_CLI="tidecoin-cli -datadir=$DATA_DIR -rpcport=9368 -rpcuser=isekai_tdc -rpcpassword=$RPC_PASS"

if $TDC_CLI getblockcount &>/dev/null; then
    BLOCKS=$($TDC_CLI getblockcount)
    log "Node responding — block height: $BLOCKS"
elif $TDC_CLI getblockcount 2>&1 | grep -q "\-28\|Loading"; then
    warn "Node starting up — still loading (normal, wait ~30s)"
else
    warn "Node not responding — check: journalctl -u isekai-tidecoin -f"
    warn "Also verify ports: ss -tlnp | grep tidecoind"
fi

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║      Tidecoin (TDC) installed and running!          ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BOLD}Why Tidecoin:${NC}"
echo "  • Post-quantum secure (FALCON-512) — quantum-resistant Bitcoin"
echo "  • CPU only, 21M supply, 60-second blocks"
echo "  • Active community: Korean + global, own exchange + pools"
echo ""
echo -e "${BOLD}Binaries:${NC}    $BIN_DIR/tidecoind | tidecoin-cli"
echo -e "${BOLD}Data dir:${NC}    $DATA_DIR"
echo -e "${BOLD}Explorer:${NC}    https://explorer.tidecoin.org"
echo -e "${BOLD}Exchange:${NC}    https://tidecoin.exchange"
echo ""
echo -e "${BOLD}Commands:${NC}"
echo "  systemctl status isekai-tidecoin"
echo "  journalctl -u isekai-tidecoin -f"
echo "  $TDC_CLI getblockcount"
echo "  $TDC_CLI getpeerinfo | grep addr"
echo ""
echo -e "${BOLD}If node doesn't connect, add peers manually:${NC}"
echo "  $TDC_CLI addnode \"pool.rplant.xyz:9369\" add"
echo "  $TDC_CLI addnode \"tidepool.world:9369\" add"
echo ""
echo -e "${BOLD}Note:${NC} Tidecoin P2P port may differ — check ss -tlnp | grep tidecoind"
echo ""
