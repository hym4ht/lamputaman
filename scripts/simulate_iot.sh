#!/usr/bin/env bash
# =============================================================================
# simulate_iot.sh — Simulasi NodeMCU kirim data ke server lokal
# =============================================================================
# Endpoint yang disimulasikan:
#   POST /api/iot/sensor          — data suhu, kelembaban, jarak_air, status_air
#   POST /api/iot/smart-watering  — laporan pompa smart watering
#   GET  /api/iot/control         — ambil status relay (opsional, info saja)
#
# Cara pakai:
#   chmod +x scripts/simulate_iot.sh
#   ./scripts/simulate_iot.sh              # mode interaktif
#   ./scripts/simulate_iot.sh --loop       # kirim terus tiap 5 detik
#   ./scripts/simulate_iot.sh --once       # kirim satu kali lalu keluar
#   ./scripts/simulate_iot.sh --scenario=rendah  # paksa skenario tertentu
# =============================================================================

set -uo pipefail

# ─── KONFIGURASI ─────────────────────────────────────────────────────────────
BASE_URL="${IOT_BASE_URL:-http://127.0.0.1:8000}"
IOT_TOKEN="${IOT_API_TOKEN:-c131e10fe1608540ee2b446a4bf9529846c883893dfdf261e288cf6124f26dfc}"
INTERVAL="${IOT_INTERVAL:-5}"   # detik antar kiriman saat --loop

# ─── WARNA TERMINAL ──────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'
BLUE='\033[0;34m'; MAGENTA='\033[0;35m'; DIM='\033[2m'

# ─── PARSE ARGUMEN ───────────────────────────────────────────────────────────
MODE="interactive"
FORCE_SCENARIO=""

for arg in "$@"; do
  case "$arg" in
    --loop)      MODE="loop" ;;
    --once)      MODE="once" ;;
    --scenario=*) FORCE_SCENARIO="${arg#*=}" ;;
    --help|-h)
      echo -e "${BOLD}Simulasi IoT NodeMCU${RESET}"
      echo ""
      echo "Usage: $0 [opsi]"
      echo ""
      echo "Opsi:"
      echo "  --loop              Kirim data terus-menerus tiap ${INTERVAL} detik"
      echo "  --once              Kirim satu kali lalu keluar"
      echo "  --scenario=NAMA     Paksa skenario tertentu:"
      echo "                        tinggi   — air penuh (FULL)"
      echo "                        sedang   — air sedang"
      echo "                        rendah   — air hampir habis (HABIS)"
      echo "                        panas    — suhu tinggi, smart watering ON"
      echo "                        kering   — kelembaban rendah, smart watering ON"
      echo "                        normal   — kondisi normal, smart watering OFF"
      echo "                        acak     — random setiap iterasi"
      echo ""
      echo "Variabel lingkungan:"
      echo "  IOT_BASE_URL        URL server (default: http://127.0.0.1:8000)"
      echo "  IOT_API_TOKEN       Token IoT (default: token dari iotcode.ino)"
      echo "  IOT_INTERVAL        Detik antar kiriman di mode loop (default: 5)"
      echo ""
      echo "Contoh:"
      echo "  ./scripts/simulate_iot.sh --loop --scenario=rendah"
      echo "  IOT_BASE_URL=http://localhost:8080 ./scripts/simulate_iot.sh --once"
      exit 0
      ;;
  esac
done

# ─── CEK DEPENDENCY ──────────────────────────────────────────────────────────
if ! command -v curl &>/dev/null; then
  echo -e "${RED}❌ curl tidak ditemukan. Install: sudo apt install curl${RESET}"
  exit 1
fi
if ! command -v jq &>/dev/null; then
  echo -e "${YELLOW}⚠️  jq tidak ditemukan. Output JSON tidak akan diformat.${RESET}"
  echo -e "${DIM}   Install: sudo apt install jq${RESET}"
  USE_JQ=false
else
  USE_JQ=true
fi

# ─── FUNGSI HELPERS ──────────────────────────────────────────────────────────
print_header() {
  clear
  echo -e "${BOLD}${CYAN}"
  echo "╔══════════════════════════════════════════════════════════╗"
  echo "║         🌿  Simulasi IoT NodeMCU — LampuTaman           ║"
  echo "╚══════════════════════════════════════════════════════════╝"
  echo -e "${RESET}"
  echo -e "  ${DIM}Server   : ${RESET}${CYAN}${BASE_URL}${RESET}"
  echo -e "  ${DIM}Token    : ${RESET}${DIM}${IOT_TOKEN:0:16}...${RESET}"
  echo -e "  ${DIM}Mode     : ${RESET}${BOLD}${MODE}${RESET}"
  echo ""
}

format_json() {
  if $USE_JQ; then
    echo "$1" | jq '.' 2>/dev/null || echo "$1"
  else
    echo "$1"
  fi
}

# Pilih skenario secara acak
random_scenario() {
  local scenarios=("tinggi" "tinggi" "sedang" "sedang" "rendah" "panas" "kering" "normal")
  echo "${scenarios[$((RANDOM % ${#scenarios[@]}))]}"
}

# ─── Helper: float random dalam range [base + 0..range] dengan 1 desimal ─────
# Pemakaian: rand_float BASE RANGE_TENTHS
# Contoh: rand_float 24 40  → antara 24.0 – 28.0
rand_float() {
  local base=$1
  local tenths=$2
  local r=$(( RANDOM % (tenths + 1) ))
  awk -v b="$base" -v r="$r" 'BEGIN { printf "%.1f\n", b + r/10 }'
}

# Generate nilai sensor berdasarkan skenario
generate_sensor_data() {
  local scenario="$1"

  case "$scenario" in
    tinggi)
      SUHU=$(rand_float 24 40)
      KELEMBABAN=$(rand_float 72 150)
      JARAK_AIR=$(rand_float 3 10)    # 3.0–4.0 cm → FULL
      STATUS_AIR="FULL"
      SMART_WATERING=0
      SCENARIO_LABEL="💧 Air Tinggi (FULL)"
      ;;
    sedang)
      SUHU=$(rand_float 26 30)
      KELEMBABAN=$(rand_float 65 80)
      JARAK_AIR=$(rand_float 5 10)    # 5.0–6.0 cm → SEDANG
      STATUS_AIR="SEDANG"
      SMART_WATERING=0
      SCENARIO_LABEL="🌊 Air Sedang"
      ;;
    rendah)
      SUHU=$(rand_float 27 30)
      KELEMBABAN=$(rand_float 60 80)
      JARAK_AIR=$(rand_float 8 10)    # 8.0–9.0 cm → HABIS
      STATUS_AIR="HABIS"
      SMART_WATERING=0
      SCENARIO_LABEL="⚠️  Air Rendah (HABIS)"
      ;;
    panas)
      SUHU=$(rand_float 31 50)        # >30°C → smart watering
      KELEMBABAN=$(rand_float 72 80)
      JARAK_AIR=$(rand_float 4 20)
      STATUS_AIR="SEDANG"
      SMART_WATERING=1
      SCENARIO_LABEL="🔥 Suhu Tinggi — Smart Watering ON"
      ;;
    kering)
      SUHU=$(rand_float 27 30)
      KELEMBABAN=$(rand_float 55 80)  # <70% → smart watering
      JARAK_AIR=$(rand_float 4 20)
      STATUS_AIR="FULL"
      SMART_WATERING=1
      SCENARIO_LABEL="💨 Kelembaban Rendah — Smart Watering ON"
      ;;
    normal|*)
      SUHU=$(rand_float 25 40)
      KELEMBABAN=$(rand_float 72 100)
      JARAK_AIR=$(rand_float 4 20)
      STATUS_AIR="FULL"
      SMART_WATERING=0
      SCENARIO_LABEL="✅ Kondisi Normal"
      ;;
  esac
}

# ─── FUNGSI KIRIM SENSOR ─────────────────────────────────────────────────────
send_sensor() {
  local scenario="$1"
  generate_sensor_data "$scenario"

  local payload
  payload=$(cat <<EOF
{
  "suhu": ${SUHU},
  "kelembaban": ${KELEMBABAN},
  "jarak_air": ${JARAK_AIR},
  "status_air": "${STATUS_AIR}"
}
EOF
)

  echo -e "\n${BOLD}📡 [$(date '+%H:%M:%S')] ${SCENARIO_LABEL}${RESET}"
  echo -e "  ${DIM}Suhu     : ${RESET}${YELLOW}${SUHU}°C${RESET}"
  echo -e "  ${DIM}Kelembab : ${RESET}${CYAN}${KELEMBABAN}%${RESET}"
  echo -e "  ${DIM}Jarak    : ${RESET}${BLUE}${JARAK_AIR} cm${RESET}"

  case "$STATUS_AIR" in
    FULL)  echo -e "  ${DIM}Status   : ${RESET}${GREEN}${STATUS_AIR} → Tinggi${RESET}" ;;
    SEDANG) echo -e "  ${DIM}Status   : ${RESET}${YELLOW}${STATUS_AIR} → Sedang${RESET}" ;;
    HABIS) echo -e "  ${DIM}Status   : ${RESET}${RED}${STATUS_AIR} → Rendah${RESET}" ;;
  esac

  echo -e "  ${DIM}SmartWtr : ${RESET}$([ "$SMART_WATERING" = "1" ] && echo "${GREEN}ON${RESET}" || echo "${DIM}OFF${RESET}")"
  echo -e ""

  # Kirim ke /api/iot/sensor
  echo -e "  ${DIM}→ POST ${BASE_URL}/api/iot/sensor${RESET}"
  local response
  local http_code
  response=$(curl -s -w "\n%{http_code}" -X POST \
    "${BASE_URL}/api/iot/sensor" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-IOT-TOKEN: ${IOT_TOKEN}" \
    -d "$payload" \
    --connect-timeout 5 \
    --max-time 10 2>/dev/null) || {
      echo -e "  ${RED}❌ Koneksi gagal — pastikan server berjalan (php artisan serve)${RESET}"
      return 1
    }

  local body http_status
  http_status=$(echo "$response" | tail -1)
  body=$(echo "$response" | head -n -1)

  if [[ "$http_status" == "201" || "$http_status" == "200" ]]; then
    echo -e "  ${GREEN}✅ Sensor OK [HTTP ${http_status}]${RESET}"
    if $USE_JQ; then
      echo "$body" | jq -r '  "  id=" + (.data.id | tostring) + " | suhu=" + (.data.suhu | tostring) + "°C | lembab=" + (.data.kelembaban | tostring) + "%"' 2>/dev/null || true
    fi
  else
    echo -e "  ${RED}❌ Sensor GAGAL [HTTP ${http_status}]${RESET}"
    echo -e "  ${DIM}$(format_json "$body")${RESET}"
    return 1
  fi

  # Kirim ke /api/iot/smart-watering
  echo -e "  ${DIM}→ POST ${BASE_URL}/api/iot/smart-watering${RESET}"
  local sw_payload="{\"pump\": ${SMART_WATERING}}"
  local sw_response sw_status sw_body
  sw_response=$(curl -s -w "\n%{http_code}" -X POST \
    "${BASE_URL}/api/iot/smart-watering" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-IOT-TOKEN: ${IOT_TOKEN}" \
    -d "$sw_payload" \
    --connect-timeout 5 \
    --max-time 10 2>/dev/null) || return 1

  sw_status=$(echo "$sw_response" | tail -1)
  sw_body=$(echo "$sw_response" | head -n -1)

  if [[ "$sw_status" == "200" ]]; then
    echo -e "  ${GREEN}✅ Smart Watering OK [HTTP ${sw_status}]${RESET}"
  else
    echo -e "  ${RED}❌ Smart Watering GAGAL [HTTP ${sw_status}]${RESET}"
    echo -e "  ${DIM}$(format_json "$sw_body")${RESET}"
  fi
}

# ─── FUNGSI CEK CONTROL (opsional info) ──────────────────────────────────────
fetch_control() {
  echo -e "\n  ${DIM}→ GET ${BASE_URL}/api/iot/control${RESET}"
  local response
  response=$(curl -s -w "\n%{http_code}" -X GET \
    "${BASE_URL}/api/iot/control" \
    -H "Accept: application/json" \
    -H "X-IOT-TOKEN: ${IOT_TOKEN}" \
    --connect-timeout 5 \
    --max-time 10 2>/dev/null) || return 0

  local body http_status
  http_status=$(echo "$response" | tail -1)
  body=$(echo "$response" | head -n -1)

  if [[ "$http_status" == "200" ]]; then
    echo -e "  ${GREEN}✅ Control state [HTTP 200]${RESET}"
    if $USE_JQ; then
      echo "$body" | jq -r '
        "  lampu1=" + (.lampu1 | tostring) +
        " | lampu2=" + (.lampu2 | tostring) +
        " | lampu3=" + (.lampu3 | tostring) +
        " | pompa=" + (.pompa | tostring)
      ' 2>/dev/null || true
    fi
  fi
}

# ─── MODE INTERAKTIF ─────────────────────────────────────────────────────────
interactive_menu() {
  print_header
  echo -e "${BOLD}Pilih skenario pengiriman:${RESET}"
  echo ""
  echo "  [1] 💧 Air Tinggi (FULL)       — jarak ~3-4 cm"
  echo "  [2] 🌊 Air Sedang              — jarak ~5-6 cm"
  echo "  [3] ⚠️  Air Rendah (HABIS)      — jarak ~8-9 cm"
  echo "  [4] 🔥 Suhu Tinggi             — smart watering ON"
  echo "  [5] 💨 Kelembaban Rendah       — smart watering ON"
  echo "  [6] ✅ Kondisi Normal           — semua aman"
  echo "  [7] 🎲 Acak                    — random tiap kirim"
  echo "  [8] 🔄 Loop otomatis (5 detik) — semua skenario bergantian"
  echo "  [0] ❌ Keluar"
  echo ""
  read -rp "$(echo -e "${BOLD}Pilihan [0-8]: ${RESET}")" choice

  case "$choice" in
    1) MODE="once"; FORCE_SCENARIO="tinggi" ;;
    2) MODE="once"; FORCE_SCENARIO="sedang" ;;
    3) MODE="once"; FORCE_SCENARIO="rendah" ;;
    4) MODE="once"; FORCE_SCENARIO="panas"  ;;
    5) MODE="once"; FORCE_SCENARIO="kering" ;;
    6) MODE="once"; FORCE_SCENARIO="normal" ;;
    7) MODE="once"; FORCE_SCENARIO="acak"   ;;
    8) MODE="loop"; FORCE_SCENARIO="acak"   ;;
    0|q|Q) echo -e "\n${DIM}Selesai.${RESET}\n"; exit 0 ;;
    *) echo -e "${RED}Pilihan tidak valid.${RESET}"; sleep 1; interactive_menu; return ;;
  esac

  # Tanya apakah mau loop setelah pilih skenario tunggal
  if [[ "$MODE" == "once" && "$choice" != "8" ]]; then
    echo ""
    read -rp "$(echo -e "${BOLD}Loop terus? (y/N): ${RESET}")" loop_ans
    [[ "$loop_ans" =~ ^[Yy]$ ]] && MODE="loop"
  fi
}

# ─── MAIN ────────────────────────────────────────────────────────────────────
main() {
  [[ "$MODE" == "interactive" ]] && interactive_menu

  local active_scenario="${FORCE_SCENARIO:-acak}"

  if [[ "$MODE" == "loop" ]]; then
    print_header
    echo -e "${BOLD}Mode LOOP — tekan ${RED}Ctrl+C${RESET}${BOLD} untuk berhenti${RESET}"
    echo -e "${DIM}Interval: ${INTERVAL} detik | Skenario: ${active_scenario}${RESET}"

    local count=0
    trap 'echo -e "\n\n${DIM}[Dihentikan oleh user]${RESET}\n"; exit 0' INT

    while true; do
      count=$((count + 1))
      local scenario
      if [[ "$active_scenario" == "acak" ]]; then
        scenario=$(random_scenario)
      else
        scenario="$active_scenario"
      fi

      echo -e "\n${DIM}─────────────────────────────── iterasi #${count} ───${RESET}"
      send_sensor "$scenario" || true
      fetch_control || true

      echo -e "\n${DIM}  Menunggu ${INTERVAL} detik...${RESET}"
      sleep "$INTERVAL"
    done
  else
    # Mode once
    local scenario
    if [[ "$active_scenario" == "acak" ]]; then
      scenario=$(random_scenario)
    else
      scenario="$active_scenario"
    fi

    send_sensor "$scenario"
    fetch_control

    echo ""
    echo -e "${DIM}─────────────────────────────────────────────────${RESET}"
    echo -e "  ${GREEN}✅ Selesai. Cek dashboard di:${RESET}"
    echo -e "  ${CYAN}   ${BASE_URL}/dashboard${RESET}"
    echo ""
  fi
}

main
