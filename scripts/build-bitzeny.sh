#!/bin/bash
# =============================================================================
# build-bitzeny.sh
# Builds BitZeny (ZNY) daemon from source on Ubuntu 22.04 / 24.04
#
# Algorithm: YescryptR8 (CPU only)
# Ports:     P2P 9252 / RPC 9253
# Network:   Active — Japanese community, multiple exchanges
#
# Fixes baked in:
#   1. BDB 4.8          — build from Oracle source if not present
#   2. Boost 1.83       — reinterpret_cast patch on type_result.members.obj_ptr
#   3. httpserver.cpp   — queue -> this->queue (GCC template name lookup)
#   4. GCC 13 headers   — missing <array>, <stdexcept> in source files
#   5. OpenSSL 3.0      — OPENSSL_API_COMPAT=0x10100000L
#   6. autogen.sh       — chmod +x after git clone
#
# Usage: bash build-bitzeny.sh
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
BUILD_DIR="/tmp/build-bitzeny"
BOOST_PATCH_DIR="/tmp/boost-patch-zny"
LOG="$BUILD_DIR/build.log"

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Building BitZeny (ZNY) — Ubuntu 22/24 LTS            ${NC}"
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
# STEP 4 — Clone BitZeny
# =============================================================================
info "Cloning BitZeny..."
rm -rf "$BUILD_DIR"
git clone --depth=1 --branch z3.0.x \
    https://github.com/BitzenyCoreDevelopers/bitzeny.git "$BUILD_DIR"
cd "$BUILD_DIR"
find . -name "*.sh" -exec chmod +x {} \;
chmod +x autogen.sh
log "Cloned BitZeny — $(git rev-parse --short HEAD)"

# =============================================================================
# STEP 5 — Source patches
# =============================================================================
info "Patching source files..."

# httpserver.cpp — bare 'queue' not found in template scope (GCC name lookup)
# Fix: qualify all member accesses with this->
sed -i \
    -e 's/\bqueue\.front()/this->queue.front()/g' \
    -e 's/\bqueue\.empty()/this->queue.empty()/g' \
    -e 's/\bqueue\.pop()/this->queue.pop()/g' \
    -e 's/\bqueue\.push(/this->queue.push(/g' \
    -e 's/\bqueue\.size()/this->queue.size()/g' \
    src/httpserver.cpp
grep -q '#include <queue>' src/httpserver.cpp || \
    sed -i '1s/^/#include <queue>\n/' src/httpserver.cpp
log "httpserver.cpp patched (queue -> this->queue)"

# Helper: add include if needed
add_include() {
    local FILE="$1" HEADER="$2" SYMBOL="$3"
    [ -f "$FILE" ] || return
    if grep -q "$SYMBOL" "$FILE" && ! grep -q "#include $HEADER" "$FILE"; then
        sed -i "1s/^/#include $HEADER\n/" "$FILE"
        log "$(basename $FILE) ← added $HEADER"
    fi
}

# GCC 13 missing headers
add_include "src/net_processing.cpp"     "<array>"      "std::array"
add_include "src/net.cpp"                "<array>"      "std::array"
add_include "src/support/lockedpool.cpp" "<stdexcept>"  "runtime_error"
add_include "src/net.cpp"                "<stdexcept>"  "runtime_error"
add_include "src/net_processing.cpp"     "<stdexcept>"  "runtime_error"
add_include "src/validation.cpp"         "<stdexcept>"  "runtime_error"
add_include "src/txmempool.cpp"          "<stdexcept>"  "runtime_error"
add_include "src/wallet/wallet.cpp"      "<stdexcept>"  "runtime_error"
add_include "src/net.cpp"                "<limits>"     "numeric_limits"

# Boost 1.83 moved _1/_2/_3 placeholders to boost::placeholders namespace
# Old Bitcoin forks used them unqualified — add explicit using declaration
# Must be added BEFORE any other includes to guarantee correct resolution
BIND_HEADER='#include <boost/bind/bind.hpp>\nusing namespace boost::placeholders;'
for FILE in \
    "src/validation.cpp" \
    "src/validationinterface.cpp" \
    "src/net.cpp" \
    "src/net_processing.cpp" \
    "src/init.cpp" \
    "src/wallet/wallet.cpp" \
    "src/rpc/server.cpp"
do
    [ -f "$FILE" ] || continue
    if grep -qE '\b_1\b|\b_2\b|\b_3\b' "$FILE" && \
       ! grep -q 'boost::placeholders' "$FILE"; then
        sed -i "1s|^|$BIND_HEADER\n|" "$FILE"
        log "$(basename $FILE) ← added boost::placeholders"
    fi
done

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
info "Compiling BitZeny (~10 min)..."
make -j$(nproc) 2>&1 | tee "$LOG" | \
    grep -E "^  (CXX|CC|AR|LINK|CXXLD)" | \
    awk '{printf "\r  %-60s", $0; fflush()}'
echo ""

# =============================================================================
# STEP 8 — Install
# =============================================================================
info "Installing binaries..."

if [ -f "src/bitzenyd" ]; then
    cp src/bitzenyd "$BIN_DIR/bitzenyd"
    chmod +x "$BIN_DIR/bitzenyd"
    [ -f "src/bitzeny-cli" ] && cp src/bitzeny-cli "$BIN_DIR/bitzeny-cli" && \
        chmod +x "$BIN_DIR/bitzeny-cli"
else
    echo ""
    echo -e "${RED}Build failed. Errors:${NC}"
    grep "error:" "$LOG" | grep -v "^In file\|note:\|warning:" | head -15
    echo ""
    echo "Full log: $LOG"
    exit 1
fi

# =============================================================================
# STEP 9 — Data dir + config
# =============================================================================
info "Setting up node config..."
mkdir -p /home/crypto/.bitzeny
chown -R crypto:crypto /home/crypto/.bitzeny

if [ ! -f /home/crypto/.bitzeny/bitzeny.conf ]; then
    RPC_PASS=$(openssl rand -hex 24)
    cat > /home/crypto/.bitzeny/bitzeny.conf << EOF
rpcuser=isekai_zny
rpcpassword=$RPC_PASS
rpcport=9253
rpcallowip=127.0.0.1
port=9252
server=1
daemon=1
txindex=1
listen=1
maxconnections=64
shrinkdebugfile=1
EOF
    chown crypto:crypto /home/crypto/.bitzeny/bitzeny.conf
    chmod 600 /home/crypto/.bitzeny/bitzeny.conf
    echo "RPC Password: $RPC_PASS" > /root/bitzeny-credentials.txt
    chmod 600 /root/bitzeny-credentials.txt
    log "Config created — credentials at /root/bitzeny-credentials.txt"
else
    log "Config already exists — keeping existing credentials"
fi

# =============================================================================
# STEP 10 — Systemd service
# =============================================================================
info "Creating systemd service..."
cat > /etc/systemd/system/isekai-bitzeny.service << 'EOF'
[Unit]
Description=isekai BitZeny (ZNY) node
After=network.target
Wants=network.target

[Service]
User=crypto
Group=crypto
Type=forking
ExecStart=/usr/local/bin/bitzenyd -datadir=/home/crypto/.bitzeny -conf=/home/crypto/.bitzeny/bitzeny.conf -daemon
ExecStop=/usr/local/bin/bitzeny-cli -datadir=/home/crypto/.bitzeny stop
Restart=on-failure
RestartSec=30
TimeoutStopSec=60
LimitNOFILE=65536
Nice=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=isekai-bitzeny

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable isekai-bitzeny
ufw allow 9252/tcp 2>/dev/null || true

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║         BitZeny built and installed!                ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
"$BIN_DIR/bitzenyd" --version 2>&1 | head -2
echo ""
echo -e "${BOLD}Start syncing:${NC}"
echo "  systemctl start isekai-bitzeny"
echo "  journalctl -u isekai-bitzeny -f"
echo ""
RPC_PASS=$(grep rpcpassword /home/crypto/.bitzeny/bitzeny.conf | cut -d= -f2)
echo -e "${BOLD}Test RPC (after ~10s):${NC}"
echo "  bitzeny-cli -datadir=/home/crypto/.bitzeny -rpcport=9253 -rpcuser=isekai_zny -rpcpassword=$RPC_PASS getblockcount"
echo ""
