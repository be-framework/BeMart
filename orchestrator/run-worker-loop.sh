#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="${PROJECT_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${PHP_BIN:-php}"
SLEEP_SECONDS="${SLEEP_SECONDS:-5}"
LOG_DIR="${LOG_DIR:-$PROJECT_ROOT/.migrate/logs}"
LOG_FILE="${LOG_FILE:-$LOG_DIR/worker-loop.log}"

mkdir -p "$LOG_DIR"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" | tee -a "$LOG_FILE"
}

log "worker loop started"

trap 'log "worker loop stopped"; exit 0' INT TERM

while true; do
  if "$PHP_BIN" "$PROJECT_ROOT/bin/orchestrator" worker once >>"$LOG_FILE" 2>&1; then
    :
  else
    log "worker once exited with a non-zero status"
  fi
  sleep "$SLEEP_SECONDS"
done
