#!/usr/bin/env bash
set -u

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_VERSION="2.4.0"
FORCE_RESTART="${1:-}"

chmod 666 "$DIR/detected_codes.json" >/dev/null 2>&1 || true

if [ "$FORCE_RESTART" != "--force" ]; then
    STATUS_JSON="$(curl -s --max-time 2 http://127.0.0.1:5001/api/status 2>/dev/null || true)"
    if echo "$STATUS_JSON" | grep -q "\"version\":\"$TARGET_VERSION\""; then
        exit 0
    fi
fi

NODE_BIN=""
for cand in \
    "$(command -v node 2>/dev/null || true)" \
    /www/server/nodejs/*/bin/node \
    /www/server/nvm/versions/node/*/bin/node \
    /root/.nvm/versions/node/*/bin/node \
    /usr/local/bin/node \
    /usr/bin/node; do
    if [ -n "$cand" ] && [ -x "$cand" ]; then
        VER="$("$cand" -v 2>/dev/null || true)"
        case "$VER" in
            v*)
                NODE_BIN="$cand"
                break
                ;;
        esac
    fi
done

if [ -z "$NODE_BIN" ]; then
    echo "[WA-GATEWAY] ERROR: Binary node tidak ditemukan!" >&2
    exit 1
fi

export PATH="$(dirname "$NODE_BIN"):$PATH"

# Hanya hentikan proses wa-gateway spesifik di direktori ini (JANGAN gunakan grep "server.js" umum karena akan membunuh pg.nandarstore.my.id & wabotai!)
for pid in $(ps -ef | grep "[n]ode" | grep "$DIR/server\.js" | awk '{print $2}'); do
    if [ "$pid" != "$$" ] && [ "$pid" != "${PPID:-0}" ]; then
        kill -9 "$pid" >/dev/null 2>&1 || true
    fi
done

fuser -k 5001/tcp >/dev/null 2>&1 || true
sleep 1

# Cek apakah PM2/systemd sudah menyalakan ulang secara otomatis
STATUS_AFTER_KILL="$(curl -s --max-time 1 http://127.0.0.1:5001/api/status 2>/dev/null || true)"
if ! echo "$STATUS_AFTER_KILL" | grep -q "\"version\""; then
    cd "$DIR" || exit 1
    nohup "$NODE_BIN" "$DIR/server.js" >> "$DIR/wa-gateway.log" 2>&1 < /dev/null &
fi

for i in 1 2 3 4 5 6 7 8 9 10; do
    sleep 0.5
    CHECK_JSON="$(curl -s --max-time 1 http://127.0.0.1:5001/api/status 2>/dev/null || true)"
    if echo "$CHECK_JSON" | grep -q "\"version\""; then
        chmod 666 "$DIR/detected_codes.json" >/dev/null 2>&1 || true
        echo "[WA-GATEWAY] Aktif di port 5001 ($NODE_BIN): $CHECK_JSON"
        exit 0
    fi
done

echo "[WA-GATEWAY] Proses dijalankan di background ($NODE_BIN)."
exit 0
