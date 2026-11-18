#!/usr/bin/env bash
# Sobe backend Python (8010) e frontend PHP (8080).
set -euo pipefail
cd "$(dirname "$0")"

python3 backend/app.py 8010 &
API_PID=$!
trap 'kill $API_PID 2>/dev/null || true' EXIT

sleep 1
echo "Frontend: http://127.0.0.1:8080"
php -S 127.0.0.1:8080 -t frontend
