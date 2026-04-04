#!/bin/bash
# =============================================================================
# build-uraniumx.sh
# Builds UraniumX (URX) daemon from source on Ubuntu 22.04 / 24.04
#
# Fixes baked in (discovered through iterative debugging):
#   1. BDB 4.8   — Ubuntu ships 5.x; we build 4.8 from Oracle source
#   2. Boost 1.83 — static_cast<Functor*>(void*) regression in function_base.hpp
#                   lines 635+651 use type_result.members.obj_ptr
#   3. GCC 13    — no longer implicitly includes headers old Bitcoin forks relied on:
#                   net_processing.cpp  → missing <array>
#                   support/lockedpool.cpp → missing <stdexcept>
#   4. autogen.sh — git clone doesn't preserve +x on scripts; chmod fix required
#
# Usage: bash build-uraniumx.sh
# Time:  ~15 min on a 2-core VPS (mostly compile time)
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
BUILD_DIR="/tmp/build-uraniumx"
BOOST_PATCH_DIR="/tmp/boost-patch-urx"
LOG="$BUILD_DIR/build.log"

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Building UraniumX (URX) — Ubuntu 22/24 LTS            ${NC}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# =============================================================================
# STEP 1 — System dependencies
# =============================================================================
info "Installing build dependencies..."
apt-get update -qq
apt-get install -y -qq \
    build-essential git curl wget \
    libssl-dev libminiupnpc-dev libzmq3-dev \
    libevent-dev autoconf automake libtool pkg-config \
    libboost-all-dev
log "Dependencies installed"

# =============================================================================
# STEP 2 — Berkeley DB 4.8
# Ubuntu 22/24 ships BDB 5.x; Bitcoin-era forks require 4.8 exactly.
# We build it as a static lib in /usr/local so it doesn't conflict with system BDB.
# =============================================================================
if [ -f "/usr/local/lib/libdb_cxx-4.8.a" ]; then
    log "BDB 4.8 already installed — skipping"
else
    info "Building Berkeley DB 4.8 from source..."
    mkdir -p /tmp/bdb48 && cd /tmp/bdb48
    wget -q "https://download.oracle.com/berkeley-db/db-4.8.30.NC.tar.gz" \
        -O db-4.8.30.NC.tar.gz
    tar -xzf db-4.8.30.NC.tar.gz
    cd db-4.8.30.NC/build_unix

    # Patch required for GCC 4.6+ (atomic.h name conflict)
    sed -i 's/__atomic_compare_exchange/__atomic_compare_exchange_db/g' \
        ../dbinc/atomic.h

    ../dist/configure \
        --enable-cxx --disable-shared --disable-replication \
        --with-pic --prefix=/usr/local \
        2>&1 | tail -3
    make -j$(nproc) 2>&1 | tail -3
    make install
    log "BDB 4.8 installed to /usr/local"
fi

# =============================================================================
# STEP 3 — Patch Boost 1.83 function_base.hpp (local copy, no system changes)
#
# Boost 1.83 regression: get_functor_pointer() uses static_cast<Functor*>(void*)
# which is invalid C++ when Functor is a function pointer type (can't static_cast
# void* to function pointer — requires reinterpret_cast).
#
# Lines to patch (confirmed against Boost 1.83.0-2.1ubuntu3.2):
#   635: return static_cast<Functor*>(type_result.members.obj_ptr);
#   651: return static_cast<const Functor*>(type_result.members.obj_ptr);
# =============================================================================
info "Creating patched local Boost function_base.hpp..."
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
    warn "Boost patch may not have applied fully — build may fail on init.o"

# =============================================================================
# STEP 4 — Clone UraniumX
# =============================================================================
info "Cloning UraniumX..."
rm -rf "$BUILD_DIR"
git clone --depth=1 \
    https://github.com/UraniumX-URX/UraniumX-2.0.0.git "$BUILD_DIR"
cd "$BUILD_DIR"

# Fix execute permissions (git clone doesn't preserve +x on scripts)
find . -name "*.sh" -exec chmod +x {} \;
chmod +x autogen.sh
log "Cloned UraniumX — $(git rev-parse --short HEAD)"

# =============================================================================
# STEP 5 — Source patches (missing headers for GCC 13)
#
# GCC 13 / libstdc++ 13 no longer implicitly includes headers that earlier
# versions pulled in transitively. Old Bitcoin forks relied on this behavior.
# We add the missing explicit includes.
# =============================================================================
info "Patching source files for GCC 13 compatibility..."

# net_processing.cpp — std::array requires <array>
if ! grep -q '#include <array>' src/net_processing.cpp; then
    sed -i '0,/#include/s//#include <array>\n#include/' src/net_processing.cpp
    log "net_processing.cpp ← added <array>"
fi

# support/lockedpool.cpp — std::runtime_error requires <stdexcept>
if ! grep -q '#include <stdexcept>' support/lockedpool.cpp; then
    sed -i '1s/^/#include <stdexcept>\n/' support/lockedpool.cpp
    log "support/lockedpool.cpp ← added <stdexcept>"
fi

# Proactive patches for other likely victims (same GCC 13 header issue)
for FILE in net.cpp validation.cpp txmempool.cpp wallet/wallet.cpp; do
    [ -f "src/$FILE" ] || continue
    if grep -q "runtime_error\|bad_alloc\|logic_error" "src/$FILE" && \
       ! grep -q '#include <stdexcept>' "src/$FILE"; then
        sed -i '1s/^/#include <stdexcept>\n/' "src/$FILE"
        log "$FILE ← added <stdexcept> (proactive)"
    fi
done

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
info "Compiling UraniumX (~10 min on 2 cores)..."
make -j$(nproc) 2>&1 | tee "$LOG" | grep -E "^  (CXX|CC|AR|LINK|CXXLD)" | \
    awk '{printf "\r  %-60s", $0; fflush()}'
echo ""

# =============================================================================
# STEP 8 — Install binaries
# =============================================================================
info "Installing binaries..."

DAEMON=""
[ -f "src/uraniumxd" ] && DAEMON="src/uraniumxd"
[ -z "$DAEMON" ] && [ -f "src/bitcoind" ] && DAEMON="src/bitcoind"

[ -z "$DAEMON" ] && {
    echo ""
    echo -e "${RED}Build failed. Errors:${NC}"
    grep "error:" "$LOG" | grep -v "^In file\|note:\|warning:" | head -15
    echo ""
    echo "Full log: $LOG"
    exit 1
}

cp "$DAEMON" "$BIN_DIR/uraniumxd"
chmod +x "$BIN_DIR/uraniumxd"

CLI=$(find src/ -maxdepth 1 -type f -executable -name "*-cli" 2>/dev/null | head -1)
[ -n "$CLI" ] && cp "$CLI" "$BIN_DIR/uraniumx-cli" && chmod +x "$BIN_DIR/uraniumx-cli"

# =============================================================================
# Done
# =============================================================================
echo ""
echo -e "${BOLD}${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BOLD}${GREEN}║         UraniumX built and installed!               ║${NC}"
echo -e "${BOLD}${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
"$BIN_DIR/uraniumxd" --version 2>&1 | head -2
echo ""
echo -e "${BOLD}Installed:${NC}"
echo "  $BIN_DIR/uraniumxd"
[ -f "$BIN_DIR/uraniumx-cli" ] && echo "  $BIN_DIR/uraniumx-cli"
echo ""
echo -e "${BOLD}To run as a service (if isekai-bootstrap was already run):${NC}"
echo "  systemctl start isekai-uraniumx"
echo "  journalctl -u isekai-uraniumx -f"
echo ""
echo -e "${BOLD}To run manually:${NC}"
echo "  uraniumxd -daemon -datadir=/home/crypto/.uraniumx"
echo "  uraniumx-cli getblockcount"
echo ""
