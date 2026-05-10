#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include "DHT.h"

// --- KONFIGURASI WIFI ---
const char* ssid = "KASYIFA KEY";
const char* password = "PINGINSURGAibadah";

// --- KONFIGURASI API LARAVEL ---
// Isi dengan alamat web Laravel yang bisa diakses NodeMCU.
// Untuk server lokal, gunakan IP laptop/PC, bukan 127.0.0.1 atau localhost.
const char* apiBaseUrl = "http://43.133.155.101:8099";

// Isi jika .env Laravel memakai IOT_API_TOKEN. Kosongkan jika tidak dipakai.
const char* iotToken = "c131e10fe1608540ee2b446a4bf9529846c883893dfdf261e288cf6124f26dfc";

const char* sensorPath = "/api/iot/sensor";
const char* controlPath = "/api/iot/control";

// --- KONFIGURASI PIN (NodeMCU ESP8266) ---
#define DHTPIN D4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);

const int relayLampu1 = D1;
const int relayLampu2 = D2;
const int relayLampu3 = D5;
const int relayPompa = D6;

// Kebanyakan modul relay biru adalah active low: LOW = ON, HIGH = OFF.
const bool relayActiveLow = true;

unsigned long previousMillis = 0;
const unsigned long syncIntervalMs = 5000;

String apiUrl(const char* path) {
  String base = apiBaseUrl;

  if (base.endsWith("/")) {
    base.remove(base.length() - 1);
  }

  return base + path;
}

void addApiHeaders(HTTPClient& http, bool withJsonBody) {
  http.addHeader("Accept", "application/json");

  if (withJsonBody) {
    http.addHeader("Content-Type", "application/json");
  }

  if (strlen(iotToken) > 0) {
    http.addHeader("X-IOT-TOKEN", iotToken);
  }
}

void writeRelay(int pin, int status) {
  bool isOn = status == 1;
  int activeLevel = relayActiveLow ? LOW : HIGH;
  int inactiveLevel = relayActiveLow ? HIGH : LOW;

  digitalWrite(pin, isOn ? activeLevel : inactiveLevel);
}

void applyRelayState(int lampu1, int lampu2, int lampu3, int pompa) {
  writeRelay(relayLampu1, lampu1);
  writeRelay(relayLampu2, lampu2);
  writeRelay(relayLampu3, lampu3);
  writeRelay(relayPompa, pompa);

  Serial.print("Relay => lampu1=");
  Serial.print(lampu1);
  Serial.print(" lampu2=");
  Serial.print(lampu2);
  Serial.print(" lampu3=");
  Serial.print(lampu3);
  Serial.print(" pompa=");
  Serial.println(pompa);
}

void setAllRelaysOff() {
  applyRelayState(0, 0, 0, 0);
}

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.print("\nMenghubungkan ke WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nBerhasil terhubung ke WiFi!");
  Serial.print("IP Address ESP8266: ");
  Serial.println(WiFi.localIP());
}

bool ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  Serial.println("WiFi terputus, mencoba konek ulang...");
  WiFi.reconnect();
  delay(1000);

  return WiFi.status() == WL_CONNECTED;
}

void sendSensorData() {
  float suhu = dht.readTemperature();
  float kelembaban = dht.readHumidity();

  if (isnan(suhu) || isnan(kelembaban)) {
    Serial.println("Gagal membaca sensor DHT. Kontrol relay tetap dicek.");
    return;
  }

  StaticJsonDocument<160> requestDoc;
  requestDoc["suhu"] = suhu;
  requestDoc["kelembaban"] = kelembaban;

  String requestBody;
  serializeJson(requestDoc, requestBody);

  WiFiClient client;
  HTTPClient http;
  String url = apiUrl(sensorPath);

  if (!http.begin(client, url)) {
    Serial.println("Gagal mulai HTTP sensor.");
    return;
  }

  addApiHeaders(http, true);

  int statusCode = http.POST(requestBody);
  String response = http.getString();

  if (statusCode >= 200 && statusCode < 300) {
    Serial.print("Sensor terkirim: suhu=");
    Serial.print(suhu, 1);
    Serial.print(" kelembaban=");
    Serial.println(kelembaban, 1);
  } else {
    Serial.print("Gagal kirim sensor. HTTP ");
    Serial.print(statusCode);
    Serial.print(" => ");
    Serial.println(response);
  }

  http.end();
}

void fetchAndApplyControl() {
  WiFiClient client;
  HTTPClient http;
  String url = apiUrl(controlPath);

  if (!http.begin(client, url)) {
    Serial.println("Gagal mulai HTTP control.");
    return;
  }

  addApiHeaders(http, false);

  int statusCode = http.GET();
  String response = http.getString();

  if (statusCode != 200) {
    Serial.print("Gagal ambil kontrol. HTTP ");
    Serial.print(statusCode);
    Serial.print(" => ");
    Serial.println(response);
    http.end();
    return;
  }

  StaticJsonDocument<256> responseDoc;
  DeserializationError error = deserializeJson(responseDoc, response);

  if (error) {
    Serial.print("Gagal membaca JSON control: ");
    Serial.println(error.c_str());
    http.end();
    return;
  }

  int lampu1 = responseDoc["lampu1"] | 0;
  int lampu2 = responseDoc["lampu2"] | 0;
  int lampu3 = responseDoc["lampu3"] | 0;
  int pompa = responseDoc["pompa"] | 0;

  applyRelayState(lampu1, lampu2, lampu3, pompa);
  http.end();
}

void setup() {
  Serial.begin(115200);

  pinMode(relayLampu1, OUTPUT);
  pinMode(relayLampu2, OUTPUT);
  pinMode(relayLampu3, OUTPUT);
  pinMode(relayPompa, OUTPUT);

  setAllRelaysOff();
  dht.begin();
  connectWiFi();
}

void loop() {
  unsigned long currentMillis = millis();

  if (currentMillis - previousMillis < syncIntervalMs) {
    return;
  }

  previousMillis = currentMillis;

  if (!ensureWiFi()) {
    return;
  }

  sendSensorData();
  fetchAndApplyControl();
}
