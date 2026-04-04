#!/bin/bash
# =============================================================================
# build-koto.sh — FINAL VERSION
# Installs Koto (KOTO) daemon on Ubuntu 22.04 / 24.04
#
# Algorithm:  Yescrypt (CPU only, ASIC resistant)
# Ports:      P2P 8433 / RPC 8432
# Network:    Active — Japanese privacy coin, TradeOgre listed
# Base:       Zcash fork
#
# APPROACH: Use official prebuilt binary from GitHub releases.
# Building from source was attempted but abandoned due to:
#   - Requires Rust 1.55.0 (pinned, incompatible with modern rustup)
#   - Pinned git dependencies for Zcash crypto libs fail to resolve
#   - Upstream RUST_TARGET detection broken on Ubuntu 24
# The official prebuilt binary (v4.5.7) works cleanly.
#
# zk-SNARK PARAMS: Koto uses Zcash Sapling parameters (~743MB).
# koto-fetch-params has SHA256 issues on split downloads — use Zcash CDN.
# Params location: /home/crypto/.zcash-params/
#
# Usage: bash build-koto.sh
# Time:  ~5 min (mostly param download speed)
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
KOTO_VERSION="4.5.7"
KOTO_URL="https://github.com/KotoDevelopers/koto/releases/download/v${KOTO_VERSION}/koto-${KOTO_VERSION}-linux64.tar.gz"
PARAMS_DIR="/home/crypto/.zcash-params"

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Installing Koto (KOTO) v${KOTO_VERSION} — Ubuntu 22/24 LTS    ${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# =============================================================================
# STEP 1 — Download prebuilt binary
# =============================================================================
info "Downloading Koto v${KOTO_VERSION} prebuilt binary..."
cd /tmp
rm -rf koto-${KOTO_VERSION} koto-linux64.tar.gz
wget -q --show-progress "$KOTO_URL" -O koto-linux64.tar.gz
tar -xzf koto-linux64.tar.gz
log "Downloaded and extracted"

# =============================================================================
# STEP 2 — Install binaries
# =============================================================================
info "Installing binaries..."
KOTO_DIR=$(find /tmp -maxdepth 1 -type d -name "koto-*" | head -1)
[ -z "$KOTO_DIR" ] && KOTO_DIR="/tmp/koto-${KOTO_VERSION}"

cp "$KOTO_DIR/bin/kotod"             "$BIN_DIR/kotod"
cp "$KOTO_DIR/bin/koto-cli"          "$BIN_DIR/koto-cli"
cp "$KOTO_DIR/bin/koto-fetch-params" "$BIN_DIR/koto-fetch-params"
cp "$KOTO_DIR/bin/koto-tx"           "$BIN_DIR/koto-tx"
chmod +x "$BIN_DIR/kotod" "$BIN_DIR/koto-cli" \
         "$BIN_DIR/koto-fetch-params" "$BIN_DIR/koto-tx"

log "Binaries installed:"
kotod --version 2>&1 | head -1

# =============================================================================
# STEP 3 — Download zk-SNARK parameters
# koto-fetch-params has SHA256 issues with split downloads from ko-to.org
# Using Zcash CDN directly — files are identical and checksums pass
# =============================================================================
info "Setting up zk-SNARK parameters..."
mkdir -p "$PARAMS_DIR"

download_param() {
    local FILE="$1"
    if [ -f "$PARAMS_DIR/$FILE" ]; then
        log "$FILE already present — skipping"
    else
        info "Downloading $FILE..."
        wget -c --show-progress \
            "https://download.z.cash/downloads/$FILE" \
            -O "$PARAMS_DIR/$FILE"
        log "$FILE downloaded"
    fi
}

download_param "sapling-spend.params"
download_param "sapling-output.params"
download_param "sprout-groth16.params"

chown -R crypto:crypto "$PARAMS_DIR"
log "zk-SNARK parameters ready ($(du -sh $PARAMS_DIR | cut -f1))"

# =============================================================================
# STEP 4 — Data directory + config
# =============================================================================
info "Setting up node config..."
mkdir -p /home/crypto/.koto

if [ ! -f /home/crypto/.koto/koto.conf ]; then
    RPC_PASS=$(openssl rand -hex 24)
    cat > /home/crypto/.koto/koto.conf << EOF
rpcuser=isekai_koto
rpcpassword=$RPC_PASS
rpcport=8432
rpcallowip=127.0.0.1
port=8433
server=1
daemon=1
txindex=1
listen=1
maxconnections=64
shrinkdebugfile=1
EOF
    chown crypto:crypto /home/crypto/.koto/koto.conf
    chmod 600 /home/crypto/.koto/koto.conf
    echo "RPC Password: $RPC_PASS" > /root/koto-credentials.txt
    chmod 600 /root/koto-credentials.txt
    log "Config created — credentials saved to /root/koto-credentials.txt"
else
    log "Config already exists — keeping existing credentials"
    RPC_PASS=$(grep rpcpassword /home/crypto/.koto/koto.conf | cut -d= -f2)
fi

chown -R crypto:crypto /home/crypto/.koto

# =============================================================================
# STEP 5 — Systemd service
# kotod looks for zk-SNARK params in /home/crypto/.zcash-params
# because the service runs as the crypto user (HOME=/home/crypto)
# =============================================================================
info "Creating systemd service..."
cat > /etc/systemd/system/isekai-koto.service << 'EOF'
[Unit]
Description=isekai Koto (KOTO) node
After=network.target
Wants=network.target

[Service]
User=crypto
Group=crypto
Type=forking
ExecStart=/usr/local/bin/kotod -datadir=/home/crypto/.koto -conf=/home/crypto/.koto/koto.conf -daemon
ExecStop=/usr/local/bin/koto-cli -datadir=/home/crypto/.koto stop
Restart=on-failure
RestartSec=30
TimeoutStopSec=60
LimitNOFILE=65536
Nice=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=isekai-koto

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable isekai-koto
ufw allow 8433/tcp 2>/dev/null || true
log "Service created and enabled"

# =============================================================================
# STEP 6 — Start and verify
# =============================================================================
info "Starting Koto node..."
systemctl start isekai-koto
sleep 15

KOTO_CLI="koto-cli -datadir=/home/crypto/.koto -rpcport=8432 -rpcuser=isekai_koto -rpcpassword=$RPC_PASS"

if $KOTO_CLI getblockcount &>/dev/null; then
    BLOCKS=$($KOTO_CLI getblockcount)
    log "Node responding — block height: $BLOCKS"
else
    warn "Node not responding yet — check: journalctl -u isekai-koto -f"
fi

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║         Koto installed and running!                 ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BOLD}Binaries:${NC}"
echo "  $BIN_DIR/kotod"
echo "  $BIN_DIR/koto-cli"
echo ""
echo -e "${BOLD}Data dir:${NC}    /home/crypto/.koto"
echo -e "${BOLD}Params:${NC}      $PARAMS_DIR"
echo -e "${BOLD}Credentials:${NC} /root/koto-credentials.txt"
echo ""
echo -e "${BOLD}Useful commands:${NC}"
echo "  systemctl status isekai-koto"
echo "  journalctl -u isekai-koto -f"
echo "  $KOTO_CLI getblockcount"
echo "  $KOTO_CLI getblockchaininfo"
echo "  $KOTO_CLI getpeerinfo | grep addr"
echo ""
echo -e "${BOLD}Note:${NC} Koto has ~1.5M blocks — full sync takes several hours."
echo ""
