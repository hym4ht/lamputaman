#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_AHTX0.h>
#include <Adafruit_BMP280.h>

// --- KONFIGURASI WIFI ---
const char* ssid = "Makoto.wifi";
const char* password = "harikamis";

// --- KONFIGURASI API LARAVEL ---
const char* apiBaseUrl = "http://43.133.155.101:8099";
const char* iotToken = "c131e10fe1608540ee2b446a4bf9529846c883893dfdf261e288cf6124f26dfc";

const char* sensorPath = "/api/iot/sensor";
const char* controlPath = "/api/iot/control";
const char* smartWateringReportPath = "/api/iot/smart-watering";

// --- INISIALISASI SENSOR AHT20 & BMP280 ---
Adafruit_AHTX0 aht;
Adafruit_BMP280 bmp; 

// --- KONFIGURASI PIN (NodeMCU ESP8266) ---
const int relayLampu1 = D3; 
const int relayLampu2 = D5;
const int relayLampu3 = D6;
const int relayPompa  = D7;
const int ledWiFi = D4;
const int trigPin = D0;
const int echoPin = D8;

const bool relayActiveLow = true;

unsigned long previousMillis = 0;
const unsigned long syncIntervalMs = 5000;

// --- VARIABEL GLOBAL SENSOR ---
float currentSuhu = 0.0;
float currentKelembaban = 0.0;
float currentTekanan = 0.0;
float currentJarakAir = 0.0;
String statusAir = "UNKNOWN"; 

// --- SMART WATERING (logika di IoT, berdasarkan sensor) ---
// Pompa otomatis nyala jika suhu melebihi threshold ATAU kelembaban di bawah threshold
const float SW_SUHU_THRESHOLD    = 30.0;  // derajat Celsius
const float SW_LEMBAB_THRESHOLD  = 70.0;  // persen (%)
bool smartWateringActive = false;          // status saat ini

// ==========================================
// KONFIGURASI TANDON (Berdasarkan Uji Coba)
// ==========================================
// Berapa jarak saat air mencapai bibir atas (penuh)
const float JARAK_PENUH = 3.0; 

// Berapa jarak saat air sudah menyentuh dasar wadah (habis)
const float JARAK_HABIS = 9.0; 
// ==========================================

String apiUrl(const char* path) {
  String base = apiBaseUrl;
  if (base.endsWith("/")) base.remove(base.length() - 1);
  return base + path;
}

void addApiHeaders(HTTPClient& http, bool withJsonBody) {
  http.addHeader("Accept", "application/json");
  if (withJsonBody) http.addHeader("Content-Type", "application/json");
  if (strlen(iotToken) > 0) http.addHeader("X-IOT-TOKEN", iotToken);
}

void writeRelay(int pin, int status) {
  bool isOn = (status == 1);
  int activeLevel = relayActiveLow ? LOW : HIGH;
  int inactiveLevel = relayActiveLow ? HIGH : LOW;
  digitalWrite(pin, isOn ? activeLevel : inactiveLevel);
}

void applyRelayState(int lampu1, int lampu2, int lampu3, int pompa) {
  writeRelay(relayLampu1, lampu1);
  writeRelay(relayLampu2, lampu2);
  writeRelay(relayLampu3, lampu3);
  writeRelay(relayPompa, pompa);
}

void setAllRelaysOff() {
  applyRelayState(0, 0, 0, 0);
}

// --- FUNGSI BACA ULTRASONIK ---
float bacaJarakAir() {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  
  long duration = pulseIn(echoPin, HIGH, 30000); 
  if (duration == 0) return -1; 
  return (duration * 0.034 / 2); 
}

// --- FUNGSI INDIKATOR WIFI ---
void wifiConnectedIndicator() {
  for(int i = 0; i < 3; i++) {
    digitalWrite(ledWiFi, HIGH); delay(150);
    digitalWrite(ledWiFi, LOW); delay(150);
  }
  digitalWrite(ledWiFi, HIGH); 
}

void wifiDisconnectedIndicator() {
  digitalWrite(ledWiFi, HIGH); delay(500);
  digitalWrite(ledWiFi, LOW); delay(500);
}

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  digitalWrite(ledWiFi, LOW); 

  Serial.print("\nMenghubungkan ke WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Terhubung!");
  wifiConnectedIndicator();
}

bool ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    digitalWrite(ledWiFi, HIGH);
    return true;
  }
  wifiDisconnectedIndicator();
  WiFi.reconnect();
  delay(2000); 
  if (WiFi.status() == WL_CONNECTED) {
      wifiConnectedIndicator();
      return true;
  }
  return false;
}

// --- FUNGSI PENGIRIMAN DATA SENSOR ---
void sendSensorData() {
  sensors_event_t humidity, temp;
  aht.getEvent(&humidity, &temp);
  float suhu = temp.temperature; 
  float kelembaban = humidity.relative_humidity;
  float tekanan = bmp.readPressure() / 100.0F;
  float jarak = bacaJarakAir();

  currentSuhu = suhu;
  currentKelembaban = kelembaban;
  currentTekanan = tekanan;
  
  if (jarak != -1) {
    currentJarakAir = jarak;
  }

  // --- LOGIKA PEMBAGIAN 3 ZONA AIR ---
  // Menghitung sepertiga dan dua pertiga dari rentang jarak
  float rentang = JARAK_HABIS - JARAK_PENUH; 
  float batasFull = JARAK_PENUH + (rentang / 3.0);      
  float batasSedang = JARAK_PENUH + ((rentang * 2.0) / 3.0); 

  if (currentJarakAir == 0.0 && jarak == -1) {
      statusAir = "TIDAK TERBACA";
  } else {
      if (currentJarakAir > 0 && currentJarakAir <= batasFull) {
        statusAir = "FULL";
      } else if (currentJarakAir > batasFull && currentJarakAir <= batasSedang) {
        statusAir = "SEDANG";
      } else if (currentJarakAir > batasSedang) {
        statusAir = "HABIS";
      } else {
        statusAir = "TIDAK TERBACA"; 
      }
  }

  // Debug Serial Monitor
  Serial.print("[DEBUG] Suhu: "); Serial.print(suhu);
  Serial.print(" | Lembab: "); Serial.print(kelembaban);
  Serial.print(" | Tekanan: "); Serial.print(tekanan);
  Serial.print(" | Jarak: "); 
  
  if (jarak == -1) {
      Serial.print("GAGAL_BACA");
  } else {
      Serial.print(currentJarakAir); Serial.print("cm");
  }
  
  Serial.print(" -> Status: "); Serial.println(statusAir);

  bool isSensorError = false;
  if (isnan(suhu) || isnan(kelembaban)) {
    Serial.println("❌ MASALAH: AHT20 gagal memberikan data!");
    isSensorError = true;
  }
  if (isnan(tekanan) || tekanan <= 0.01) {
    Serial.println("❌ MASALAH: BMP280 gagal memberikan data!");
    isSensorError = true;
  }
  if (isSensorError) return;

  // Siapkan Payload JSON
  StaticJsonDocument<256> requestDoc;
  requestDoc["suhu"] = currentSuhu;
  requestDoc["kelembaban"] = currentKelembaban;
  requestDoc["tekanan_udara"] = currentTekanan;
  
  if (currentJarakAir > 0) {
    requestDoc["jarak_air"] = currentJarakAir;
    requestDoc["status_air"] = statusAir; 
  }

  String requestBody;
  serializeJson(requestDoc, requestBody);

  WiFiClient client;
  HTTPClient http;
  
  if (!http.begin(client, apiUrl(sensorPath))) return;

  addApiHeaders(http, true);
  int statusCode = http.POST(requestBody);
  
  if (statusCode >= 200 && statusCode < 300) {
    Serial.println("✅ Data berhasil dikirim ke server.\n");
  } else {
    Serial.print("❌ Gagal kirim sensor. HTTP "); Serial.println(statusCode);
  }

  http.end();
}

// --- CEK KONDISI SMART WATERING & LAPOR KE BACKEND ---
// Dipanggil setiap loop setelah data sensor diperbarui.
// Jika kondisi sensor memenuhi → pompa relay langsung ON + POST ke backend.
// Saat kondisi normal kembali → pompa relay OFF + POST ke backend.
void checkSmartWatering() {
  // Abaikan jika data sensor belum valid
  if (currentSuhu <= 0.0 && currentKelembaban <= 0.0) return;

  bool kondisiPerluSiram = (currentSuhu > SW_SUHU_THRESHOLD) ||
                           (currentKelembaban > 0.0 && currentKelembaban < SW_LEMBAB_THRESHOLD);

  if (kondisiPerluSiram == smartWateringActive) return; // Tidak ada perubahan, skip

  smartWateringActive = kondisiPerluSiram;

  if (smartWateringActive) {
    Serial.println("[SmartWatering] Kondisi perlu siram! Pompa ON.");
    if (currentSuhu > SW_SUHU_THRESHOLD)
      Serial.print("  -> Suhu: "); Serial.println(currentSuhu);
    if (currentKelembaban < SW_LEMBAB_THRESHOLD)
      Serial.print("  -> Kelembaban: "); Serial.println(currentKelembaban);
  } else {
    Serial.println("[SmartWatering] Kondisi normal. Pompa OFF.");
  }

  // Kirim laporan ke backend agar toggle pompa terupdate di dashboard
  WiFiClient client;
  HTTPClient http;
  if (!http.begin(client, apiUrl(smartWateringReportPath))) return;
  addApiHeaders(http, true);

  StaticJsonDocument<64> doc;
  doc["pump"] = smartWateringActive ? 1 : 0;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);
  if (code >= 200 && code < 300) {
    Serial.print("[SmartWatering] Laporan terkirim, pompa=");
    Serial.println(smartWateringActive ? "ON" : "OFF");
  } else {
    Serial.print("[SmartWatering] Gagal POST. HTTP "); Serial.println(code);
  }
  http.end();
}

// --- FUNGSI MENGAMBIL STATUS RELAY DARI BACKEND ---
void fetchAndApplyControl() {
  WiFiClient client;
  HTTPClient http;
  
  if (!http.begin(client, apiUrl(controlPath))) return;
  addApiHeaders(http, false);
  
  int statusCode = http.GET();
  if (statusCode != 200) {
    http.end();
    return;
  }

  StaticJsonDocument<256> responseDoc;
  DeserializationError error = deserializeJson(responseDoc, http.getString());
  if (error) {
    http.end();
    return;
  }

  int lampu1 = responseDoc["lampu1"] | 0;
  int lampu2 = responseDoc["lampu2"] | 0;
  int lampu3 = responseDoc["lampu3"] | 0;
  int pompa  = responseDoc["pompa"]  | 0;

  // Jika Smart Watering sedang aktif secara lokal, pastikan pompa ON
  // (backend akan reflect ini lewat toggle pompa, tapi ini backup lokal)
  if (smartWateringActive) {
    pompa = 1;
  }

  applyRelayState(lampu1, lampu2, lampu3, pompa);
  http.end();
}

void setup() {
  Serial.begin(115200);

  pinMode(relayLampu1, OUTPUT);
  pinMode(relayLampu2, OUTPUT);
  pinMode(relayLampu3, OUTPUT);
  pinMode(relayPompa,  OUTPUT);
  pinMode(ledWiFi,     OUTPUT);
  pinMode(trigPin,     OUTPUT);
  pinMode(echoPin,     INPUT);

  setAllRelaysOff();

  Wire.begin();
  
  if (!aht.begin()) {
    Serial.println("Gagal menemukan modul AHT20!");
  }
  
  if (!bmp.begin(0x77)) {
    Serial.println("Gagal menemukan modul BMP280!");
  } else {
    bmp.setSampling(Adafruit_BMP280::MODE_NORMAL,     
                    Adafruit_BMP280::SAMPLING_X2,     
                    Adafruit_BMP280::SAMPLING_X16,    
                    Adafruit_BMP280::FILTER_X16,      
                    Adafruit_BMP280::STANDBY_MS_500); 
  }
  
  connectWiFi();
}

void loop() {
  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis < syncIntervalMs) return;
  previousMillis = currentMillis;

  if (!ensureWiFi()) return;

  sendSensorData();         // Kirim data sensor ke backend + update currentSuhu/currentKelembaban
  checkSmartWatering();     // Cek kondisi sensor, nyalakan/matikan pompa + lapor ke backend
  fetchAndApplyControl();   // Ambil perintah relay dari backend, terapkan ke hardware
}