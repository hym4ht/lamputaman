#!/usr/bin/env bash

set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$ROOT_DIR/.env}"
DEFAULT_IOT_API_TOKEN="c131e10fe1608540ee2b446a4bf9529846c883893dfdf261e288cf6124f26dfc"

usage() {
    cat <<'EOF'
Dummy realtime sensor untuk Smart Garden IoT Dashboard.

Usage:
  ./scripts/dummy-sensor.sh
  ./scripts/dummy-sensor.sh --once
  ./scripts/dummy-sensor.sh --interval 2 --runs 30
  IOT_API_TOKEN=token ./scripts/dummy-sensor.sh --interval 2
  API_URL=https://domain-kamu.com/api/iot/sensor IOT_API_TOKEN=token ./scripts/dummy-sensor.sh

Options:
  --once             Kirim 1 data lalu berhenti.
  --runs N           Kirim N data lalu berhenti. Default 0 = jalan terus.
  --interval SECOND  Jeda antar kirim data. Default 5.
  --url URL          Endpoint sensor, default http://43.133.155.101:8099/api/iot/sensor.
  -h, --help         Tampilkan bantuan.

Env override:
  API_URL, IOT_API_TOKEN, INTERVAL, RUNS, TEMP_MIN, TEMP_MAX, HUM_MIN, HUM_MAX

Catatan:
  Script ini sudah punya token VPS hardcode sebagai fallback.
EOF
}

read_env_value() {
    local key="$1"
    local fallback="${2:-}"
    local line
    local value

    if [[ -f "$ENV_FILE" ]]; then
        line="$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 || true)"

        if [[ -n "$line" ]]; then
            value="${line#*=}"
            value="${value%$'\r'}"
            value="${value%\"}"
            value="${value#\"}"
            value="${value%\'}"
            value="${value#\'}"
            printf '%s' "$value"
            return
        fi
    fi

    printf '%s' "$fallback"
}

require_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo "Error: command '$1' belum tersedia." >&2
        exit 1
    fi
}

trim_trailing_slash() {
    local value="$1"
    printf '%s' "${value%/}"
}

append_unique_candidate() {
    local candidate="$1"

    [[ -z "$candidate" ]] && return

    for existing in "${BASE_CANDIDATES[@]}"; do
        [[ "$existing" == "$candidate" ]] && return
    done

    BASE_CANDIDATES+=("$candidate")
}

is_bare_local_url() {
    local value="$1"

    [[ "$value" == "http://localhost" ||
        "$value" == "https://localhost" ||
        "$value" == "http://127.0.0.1" ||
        "$value" == "https://127.0.0.1" ]]
}

resolve_api_url() {
    if [[ -n "${API_URL:-}" ]]; then
        printf '%s' "$API_URL"
        return
    fi

    local app_url
    local default_vps_url
    local candidate
    local health_url

    default_vps_url="http://43.133.155.101:8099"
    app_url="$(trim_trailing_slash "$(read_env_value APP_URL "http://127.0.0.1:8000")")"
    BASE_CANDIDATES=()

    append_unique_candidate "$default_vps_url"

    if is_bare_local_url "$app_url"; then
        append_unique_candidate "http://127.0.0.1:8002"
        append_unique_candidate "http://127.0.0.1:8000"
        append_unique_candidate "http://127.0.0.1:8001"
        append_unique_candidate "http://localhost:8002"
        append_unique_candidate "http://localhost:8000"
        append_unique_candidate "$app_url"
    else
        append_unique_candidate "$app_url"
        append_unique_candidate "http://127.0.0.1:8002"
        append_unique_candidate "http://127.0.0.1:8000"
        append_unique_candidate "http://127.0.0.1:8001"
        append_unique_candidate "http://localhost:8002"
        append_unique_candidate "http://localhost:8000"
    fi

    for candidate in "${BASE_CANDIDATES[@]}"; do
        health_url="$(trim_trailing_slash "$candidate")/up"

        if curl -fsS --max-time 1 "$health_url" >/dev/null 2>&1; then
            printf '%s/api/iot/sensor' "$(trim_trailing_slash "$candidate")"
            return
        fi
    done

    if is_bare_local_url "$app_url"; then
        printf '%s/api/iot/sensor' "$default_vps_url"
        return
    fi

    printf '%s/api/iot/sensor' "$app_url"
}

random_float() {
    local min="$1"
    local max="$2"
    local seed="$RANDOM$RANDOM"

    awk -v min="$min" -v max="$max" -v seed="$seed" 'BEGIN {
        srand(seed + systime());
        printf "%.1f", min + rand() * (max - min);
    }'
}

send_reading() {
    local suhu
    local kelembaban
    local payload
    local timestamp
    local response
    local curl_args

    suhu="$(random_float "$TEMP_MIN" "$TEMP_MAX")"
    kelembaban="$(random_float "$HUM_MIN" "$HUM_MAX")"
    payload="$(printf '{"suhu":%s,"kelembaban":%s}' "$suhu" "$kelembaban")"
    timestamp="$(date '+%Y-%m-%d %H:%M:%S')"

    curl_args=(
        -fsS
        --max-time "$HTTP_TIMEOUT"
        -X POST "$RESOLVED_API_URL"
        -H "Accept: application/json"
        -H "Content-Type: application/json"
        -d "$payload"
    )

    if [[ -n "$IOT_TOKEN" ]]; then
        curl_args+=(-H "X-IOT-TOKEN: $IOT_TOKEN")
    fi

    if response="$(curl "${curl_args[@]}")"; then
        printf '[%s] OK suhu=%s kelembaban=%s -> %s\n' "$timestamp" "$suhu" "$kelembaban" "$response"
    else
        printf '[%s] GAGAL suhu=%s kelembaban=%s endpoint=%s\n' "$timestamp" "$suhu" "$kelembaban" "$RESOLVED_API_URL" >&2
    fi
}

require_command curl
require_command awk

INTERVAL="${INTERVAL:-5}"
RUNS="${RUNS:-0}"
TEMP_MIN="${TEMP_MIN:-24}"
TEMP_MAX="${TEMP_MAX:-34}"
HUM_MIN="${HUM_MIN:-55}"
HUM_MAX="${HUM_MAX:-90}"
HTTP_TIMEOUT="${HTTP_TIMEOUT:-10}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --once)
            RUNS=1
            ;;
        --runs)
            RUNS="${2:-}"
            shift
            ;;
        --interval)
            INTERVAL="${2:-}"
            shift
            ;;
        --url)
            API_URL="${2:-}"
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Argumen tidak dikenal: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
    shift
done

IOT_TOKEN="${IOT_API_TOKEN:-$(read_env_value IOT_API_TOKEN "")}"

if [[ -z "$IOT_TOKEN" ]]; then
    IOT_TOKEN="$DEFAULT_IOT_API_TOKEN"
fi

RESOLVED_API_URL="$(resolve_api_url)"

echo "Dummy sensor realtime aktif."
echo "Endpoint : $RESOLVED_API_URL"
echo "Token    : $([[ -n "$IOT_TOKEN" ]] && echo "aktif" || echo "kosong")"
echo "Interval : ${INTERVAL}s"
echo "Runs     : $([[ "$RUNS" == "0" ]] && echo "infinite" || echo "$RUNS")"
echo "Range    : suhu ${TEMP_MIN}-${TEMP_MAX} C, kelembaban ${HUM_MIN}-${HUM_MAX}%"
echo "Stop     : Ctrl+C"
echo

count=0

trap 'echo; echo "Dummy sensor berhenti."; exit 0' INT TERM

while true; do
    count=$((count + 1))
    send_reading

    if [[ "$RUNS" != "0" && "$count" -ge "$RUNS" ]]; then
        break
    fi

    sleep "$INTERVAL"
done
