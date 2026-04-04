#!/bin/bash
# =============================================================================
# build-yenten.sh
# Builds Yenten (YTN) daemon from source on Ubuntu 22.04 / 24.04
#
# Algorithm: YespowerR16 (CPU only, no GPU/ASIC)
# Ports:     P2P 9981 / RPC 9982
# Network:   Active — 15+ pools, 5+ exchanges, live peers
#
# Fixes baked in from URX learnings:
#   1. BDB 4.8          — build from Oracle source if not present
#   2. Boost 1.83       — reinterpret_cast patch on type_result.members.obj_ptr
#   3. GCC 13 headers   — missing <array>, <stdexcept> in various source files
#   4. OpenSSL 3.0      — OPENSSL_API_COMPAT=0x10100000L for RIPEMD160
#   5. autogen.sh       — chmod +x after git clone
#
# Usage: bash build-yenten.sh
# Time:  ~10 min on 2-core VPS
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
BUILD_DIR="/tmp/build-yenten"
BOOST_PATCH_DIR="/tmp/boost-patch-ytn"
LOG="$BUILD_DIR/build.log"

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Building Yenten (YTN) — Ubuntu 22/24 LTS             ${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# =============================================================================
# STEP 1 — Dependencies
# =============================================================================
info "Installing build dependencies..."
apt-get update -qq
apt-get install -y -qq \
    build-essential git curl wget \
    libssl-dev libminiupnpc-dev libzmq3-dev \
    libevent-dev autoconf automake libtool pkg-config \
    libboost-all-dev
log "Dependencies ready"

# =============================================================================
# STEP 2 — Berkeley DB 4.8
# =============================================================================
if [ -f "/usr/local/lib/libdb_cxx-4.8.a" ]; then
    log "BDB 4.8 already present — skipping"
else
    info "Building Berkeley DB 4.8..."
    mkdir -p /tmp/bdb48 && cd /tmp/bdb48
    wget -q "https://download.oracle.com/berkeley-db/db-4.8.30.NC.tar.gz" \
        -O db-4.8.30.NC.tar.gz
    tar -xzf db-4.8.30.NC.tar.gz
    cd db-4.8.30.NC/build_unix
    sed -i 's/__atomic_compare_exchange/__atomic_compare_exchange_db/g' \
        ../dbinc/atomic.h
    ../dist/configure \
        --enable-cxx --disable-shared --disable-replication \
        --with-pic --prefix=/usr/local 2>&1 | tail -3
    make -j$(nproc) 2>&1 | tail -3
    make install
    log "BDB 4.8 installed"
fi

# =============================================================================
# STEP 3 — Boost 1.83 local patch
# Lines 635+651 of function_base.hpp use static_cast<Functor*>(void*)
# which is invalid C++ for function pointer types — needs reinterpret_cast
# =============================================================================
info "Patching Boost 1.83 function_base.hpp..."
mkdir -p "$BOOST_PATCH_DIR/boost/function"
cp /usr/include/boost/function/function_base.hpp \
   "$BOOST_PATCH_DIR/boost/function/function_base.hpp"

sed -i \
    's/return static_cast<Functor\*>(type_result\.members\.obj_ptr)/return reinterpret_cast<Functor*>(type_result.members.obj_ptr)/g' \
    "$BOOST_PATCH_DIR/boost/function/function_base.hpp"
sed -i \
    's/return static_cast<const Functor\*>(type_result\.members\.obj_ptr)/return reinterpret_cast<const Functor*>(type_result.members.obj_ptr)/g' \
    "$BOOST_PATCH_DIR/boost/function/function_base.hpp"

PATCHED=$(grep -c "reinterpret_cast<.*Functor" \
    "$BOOST_PATCH_DIR/boost/function/function_base.hpp" || true)
[ "$PATCHED" -ge 2 ] && log "Boost patch applied ($PATCHED lines)" || \
    warn "Boost patch incomplete — may fail on init.o"

# =============================================================================
# STEP 4 — Clone Yenten
# =============================================================================
info "Cloning Yenten..."
rm -rf "$BUILD_DIR"
git clone --depth=1 https://github.com/yentencoin/yenten.git "$BUILD_DIR"
cd "$BUILD_DIR"
find . -name "*.sh" -exec chmod +x {} \;
chmod +x autogen.sh
log "Cloned Yenten — $(git rev-parse --short HEAD)"

# =============================================================================
# STEP 5 — Source patches for GCC 13
# GCC 13 requires explicit includes that older compilers pulled in transitively
# =============================================================================
info "Patching source files for GCC 13..."

# Helper: add include if symbol used but header missing
add_include() {
    local FILE="$1" HEADER="$2" SYMBOL="$3"
    [ -f "$FILE" ] || return
    if grep -q "$SYMBOL" "$FILE" && ! grep -q "#include $HEADER" "$FILE"; then
        sed -i "1s/^/#include $HEADER\n/" "$FILE"
        log "$(basename $FILE) ← added $HEADER"
    fi
}

# <array> — std::array
add_include "src/net_processing.cpp"  "<array>"      "std::array"
add_include "src/net.cpp"             "<array>"      "std::array"

# <stdexcept> — std::runtime_error, std::logic_error
add_include "src/support/lockedpool.cpp" "<stdexcept>" "runtime_error"
add_include "src/net.cpp"                "<stdexcept>" "runtime_error"
add_include "src/net_processing.cpp"     "<stdexcept>" "runtime_error"
add_include "src/validation.cpp"         "<stdexcept>" "runtime_error"
add_include "src/txmempool.cpp"          "<stdexcept>" "runtime_error"
add_include "src/wallet/wallet.cpp"      "<stdexcept>" "runtime_error"

# <cstring> — memset/memcpy sometimes needed explicitly
add_include "src/crypto/sha256.cpp"   "<cstring>"    "memset"

# <limits> — std::numeric_limits
add_include "src/net.cpp"             "<limits>"     "numeric_limits"

log "Source patches applied"

# =============================================================================
# STEP 6 — autogen + configure
# =============================================================================
info "Running autogen..."
./autogen.sh 2>&1 | tail -3

info "Configuring..."
./configure \
    --with-incompatible-bdb \
    --disable-tests \
    --disable-bench \
    --without-gui \
    CXXFLAGS="-O2 -fpermissive -DBOOST_BIND_GLOBAL_PLACEHOLDERS -DBOOST_ALLOW_DEPRECATED_HEADERS" \
    CPPFLAGS="-I$BOOST_PATCH_DIR -I/usr/local/include -DOPENSSL_API_COMPAT=0x10100000L -DHAVE_BUILD_INFO -D__STDC_FORMAT_MACROS" \
    LDFLAGS="-L/usr/local/lib" \
    2>&1 | tail -8

log "Configure complete"

# =============================================================================
# STEP 7 — Compile
# =============================================================================
info "Compiling Yenten (~10 min)..."
make -j$(nproc) 2>&1 | tee "$LOG" | \
    grep -E "^  (CXX|CC|AR|LINK|CXXLD)" | \
    awk '{printf "\r  %-60s", $0; fflush()}'
echo ""

# =============================================================================
# STEP 8 — Install
# =============================================================================
info "Installing binaries..."

if [ -f "src/yentend" ]; then
    cp src/yentend "$BIN_DIR/yentend"
    chmod +x "$BIN_DIR/yentend"
    [ -f "src/yenten-cli" ] && cp src/yenten-cli "$BIN_DIR/yenten-cli" && \
        chmod +x "$BIN_DIR/yenten-cli"
else
    echo ""
    echo -e "${RED}Build failed. Errors:${NC}"
    grep "error:" "$LOG" | grep -v "^In file\|note:\|warning:" | head -15
    echo ""
    echo "Full log: $LOG"
    exit 1
fi

# =============================================================================
# STEP 9 — Create data dir + config
# =============================================================================
info "Setting up node config..."
mkdir -p /home/crypto/.yenten
chown -R crypto:crypto /home/crypto/.yenten

if [ ! -f /home/crypto/.yenten/yenten.conf ]; then
    RPC_PASS=$(openssl rand -hex 24)
    cat > /home/crypto/.yenten/yenten.conf << EOF
rpcuser=isekai_ytn
rpcpassword=$RPC_PASS
rpcport=9982
rpcallowip=127.0.0.1
port=9981
server=1
daemon=1
txindex=1
listen=1
maxconnections=64
shrinkdebugfile=1
EOF
    chown crypto:crypto /home/crypto/.yenten/yenten.conf
    chmod 600 /home/crypto/.yenten/yenten.conf
    echo "RPC Password: $RPC_PASS" > /root/yenten-credentials.txt
    chmod 600 /root/yenten-credentials.txt
    log "Config created — credentials saved to /root/yenten-credentials.txt"
else
    log "Config already exists — keeping existing credentials"
fi

# =============================================================================
# STEP 10 — Systemd service
# =============================================================================
info "Creating systemd service..."
cat > /etc/systemd/system/isekai-yenten.service << 'EOF'
[Unit]
Description=isekai Yenten (YTN) node
After=network.target
Wants=network.target

[Service]
User=crypto
Group=crypto
Type=forking
ExecStart=/usr/local/bin/yentend -datadir=/home/crypto/.yenten -conf=/home/crypto/.yenten/yenten.conf -daemon
ExecStop=/usr/local/bin/yenten-cli -datadir=/home/crypto/.yenten stop
Restart=on-failure
RestartSec=30
TimeoutStopSec=60
LimitNOFILE=65536
Nice=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=isekai-yenten

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable isekai-yenten

# Open P2P port
ufw allow 9981/tcp 2>/dev/null || true

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║         Yenten built and installed!                 ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
"$BIN_DIR/yentend" --version 2>&1 | head -2
echo ""
echo -e "${BOLD}Start syncing:${NC}"
echo "  systemctl start isekai-yenten"
echo "  journalctl -u isekai-yenten -f"
echo ""
echo -e "${BOLD}Test RPC (after ~10s):${NC}"
RPC_PASS=$(grep rpcpassword /home/crypto/.yenten/yenten.conf | cut -d= -f2)
echo "  yenten-cli -datadir=/home/crypto/.yenten -rpcport=9982 -rpcuser=isekai_ytn -rpcpassword=$RPC_PASS getblockcount"
echo ""
