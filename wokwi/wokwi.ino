#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <DHT.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

// ============ WIFI & MQTT ============
const char* WIFI_SSID = "Wokwi-GUEST";
const char* WIFI_PASS = "";
const char* MQTT_BROKER = "broker.hivemq.com";
const int MQTT_PORT = 1883;
const char* TOPIC_SENSOR = "greenhouse/sensor";
const char* TOPIC_CONTROL = "greenhouse/control";
const char* CLIENT_ID = "greenhouse-esp32-001";

WiFiClient espClient;
PubSubClient mqtt(espClient);

// ============ PIN DEFINITIONS ============
#define DHTPIN 15
#define DHTTYPE DHT22
#define GAS_PIN 34
#define LDR_PIN 35
#define AIR_PIN 32           // Potensiometer proxy HC-SR04
#define RELAY_GROWLIGHT 26
#define RELAY_EXHAUST 27
#define RELAY_PUMP 14
#define SERVO_PIN 13
#define BUZZER_PIN 25
#define BTN_MODE 4           // Pindah dari 32 → 4
#define BTN_MANUAL 2         // Pindah dari 33 → 2

// ============ OBJECTS ============
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 16, 2);
Servo atapServo;

// ============ THRESHOLDS ============
const float SUHU_ALARM_HIGH = 35.0;
const float SUHU_ALARM_LOW = 15.0;
const float SUHU_MAX = 32.0;
const int GAS_MIN = 1000;
const int GAS_ALARM = 1500;
const int CAHAYA_MIN_PCT = 30;
const float AIR_TANDON_MIN = 20.0;
const float AIR_TANDON_ALARM = 10.0;

// ============ STATE ============
bool modeOtomatis = true;
bool growLightOn = false;
bool exhaustOn = false;
bool pumpOn = false;
bool atapTerbuka = false;
bool alarmActive = false;

unsigned long lastRead = 0;
unsigned long lastLcd = 0;
unsigned long lastSerial = 0;
unsigned long lastMqtt = 0;
unsigned long lastPumpCycle = 0;
const unsigned long INTERVAL_READ = 2000;
const unsigned long INTERVAL_LCD = 3000;
const unsigned long INTERVAL_SERIAL = 5000;
const unsigned long INTERVAL_MQTT = 3000;
const unsigned long PUMP_CYCLE = 30000;
const unsigned long PUMP_DURATION = 5000;

// ============ SENSOR VALUES ============
float suhu = 25.0;
float kelembapan = 60.0;
int gas = 0;
int cahaya = 0;
int airRaw = 0;
float levelAir = 100.0;
float jarakAir = 5.0;

// ============ MQTT CALLBACK ============
void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) message += (char)payload[i];
  message.trim();
  if (message.length() == 0 || !message.startsWith("{")) return;
  
  StaticJsonDocument<256> doc;
  if (deserializeJson(doc, message)) return;
  
  if (doc.containsKey("action") && doc.containsKey("value")) {
    String action = doc["action"];
    String value = doc["value"];
    Serial.print("[MQTT] Terima: ");
    Serial.print(action);
    Serial.print(" = ");
    Serial.println(value);
    
    if (action == "growlight") {
      growLightOn = (value == "on");
      digitalWrite(RELAY_GROWLIGHT, growLightOn ? HIGH : LOW);
      Serial.print("[EVENT] Grow Light -> ");
      Serial.println(growLightOn ? "ON (MQTT)" : "OFF (MQTT)");
    }
    else if (action == "exhaust") {
      exhaustOn = (value == "on");
      digitalWrite(RELAY_EXHAUST, exhaustOn ? HIGH : LOW);
      Serial.print("[EVENT] Exhaust -> ");
      Serial.println(exhaustOn ? "ON (MQTT)" : "OFF (MQTT)");
    }
    else if (action == "pompa") {
      pumpOn = (value == "on");
      digitalWrite(RELAY_PUMP, pumpOn ? HIGH : LOW);
      Serial.print("[EVENT] Pompa -> ");
      Serial.println(pumpOn ? "ON (MQTT)" : "OFF (MQTT)");
    }
    else if (action == "atap") {
      atapTerbuka = (value == "on");
      atapServo.write(atapTerbuka ? 90 : 0);
      Serial.print("[EVENT] Atap -> ");
      Serial.println(atapTerbuka ? "BUKA (MQTT)" : "TUTUP (MQTT)");
    }
    else if (action == "mode") {
      modeOtomatis = (value == "auto");
      Serial.print("[EVENT] Mode -> ");
      Serial.println(modeOtomatis ? "OTOMATIS (MQTT)" : "MANUAL (MQTT)");
    }
  }
}

// ============ SETUP WIFI ============
void setupWiFi() {
  Serial.print("[WiFi] Connecting to ");
  Serial.println(WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500); Serial.print("."); attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.print("[WiFi] Connected! IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n[WiFi] Failed!");
  }
}

// ============ SETUP MQTT ============
void reconnectMQTT() {
  int attempts = 0;
  while (!mqtt.connected() && attempts < 5) {
    Serial.print("[MQTT] Connecting...");
    if (mqtt.connect(CLIENT_ID)) {
      Serial.println("connected!");
      mqtt.subscribe(TOPIC_CONTROL);
    } else {
      Serial.print("failed rc=");
      Serial.print(mqtt.state());
      Serial.println(", retry in 5s");
      delay(5000);
      attempts++;
    }
  }
}

void setupMQTT() {
  mqtt.setServer(MQTT_BROKER, MQTT_PORT);
  mqtt.setCallback(callback);
  mqtt.setBufferSize(1024);
  reconnectMQTT();
}

// ============ SETUP ============
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println();
  Serial.println("=== GREENHOUSE V2.5 STARTING ===");

  dht.begin();
  pinMode(GAS_PIN, INPUT);
  pinMode(LDR_PIN, INPUT);
  pinMode(AIR_PIN, INPUT);

  pinMode(RELAY_GROWLIGHT, OUTPUT);
  pinMode(RELAY_EXHAUST, OUTPUT);
  pinMode(RELAY_PUMP, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(RELAY_GROWLIGHT, LOW);
  digitalWrite(RELAY_EXHAUST, LOW);
  digitalWrite(RELAY_PUMP, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  atapServo.attach(SERVO_PIN);
  atapServo.write(0);

  pinMode(BTN_MODE, INPUT_PULLUP);
  pinMode(BTN_MANUAL, INPUT_PULLUP);

  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Greenhouse V2.5");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");
  delay(1000);
  lcd.clear();

  setupWiFi();
  setupMQTT();

  lcd.setCursor(0, 0);
  lcd.print("WiFi: OK");
  lcd.setCursor(0, 1);
  lcd.print("MQTT: OK");
  delay(2000);
  lcd.clear();

  Serial.println("[SYS] Setup selesai!");
  Serial.println();
}

// ============ BACA SENSOR ============
void bacaSensor() {
  float nt = dht.readTemperature();
  float nh = dht.readHumidity();
  if (!isnan(nt)) suhu = nt;
  if (!isnan(nh)) kelembapan = nh;

  gas = analogRead(GAS_PIN);
  cahaya = analogRead(LDR_PIN);

  // Level air (proxy potensiometer)
  // 0 = kosong (0%), 4095 = penuh (100%)
  airRaw = analogRead(AIR_PIN);
  levelAir = map(airRaw, 0, 4095, 0, 100);
  levelAir = constrain(levelAir, 0, 100);
  
  // Simulasi jarak HC-SR04 (5-30 cm)
  // 100% = 5cm (penuh), 0% = 30cm (kosong)
  jarakAir = map(levelAir, 100, 0, 5, 30);
}

// ============ KONVERSI ============
int cahayaPersen() {
  int p = map(cahaya, 4095, 0, 0, 100);
  return constrain(p, 0, 100);
}

int gasPPM() {
  return map(gas, 0, 4095, 0, 2000);
}

const char* gasStatus() {
  if (gas > GAS_ALARM) return "BAHAYA";
  if (gas > GAS_MIN)   return "TINGGI";
  return "NORMAL";
}

const char* cahayaStatus() {
  return (cahayaPersen() < CAHAYA_MIN_PCT) ? "GELAP" : "TERANG";
}

const char* airStatus() {
  if (levelAir < AIR_TANDON_ALARM) return "KRITIS";
  if (levelAir < AIR_TANDON_MIN)   return "RENDAH";
  return "NORMAL";
}

// ============ LOGIKA OTOMATIS ============
void logikaOtomatis() {
  // Grow Light
  bool perluCahaya = (cahayaPersen() < CAHAYA_MIN_PCT);
  if (perluCahaya != growLightOn) {
    growLightOn = perluCahaya;
    digitalWrite(RELAY_GROWLIGHT, growLightOn ? HIGH : LOW);
    Serial.print("[EVENT] Grow Light -> ");
    Serial.print(growLightOn ? "ON" : "OFF");
    Serial.print(" (cahaya ");
    Serial.print(cahayaPersen());
    Serial.println("%)");
  }

  // Exhaust
  bool perluExhaust = (suhu > SUHU_MAX) || (gas > GAS_MIN);
  if (perluExhaust != exhaustOn) {
    exhaustOn = perluExhaust;
    digitalWrite(RELAY_EXHAUST, exhaustOn ? HIGH : LOW);
    if (exhaustOn) {
      Serial.print("[EVENT] Exhaust -> ON (suhu ");
      Serial.print(suhu, 1);
      Serial.print(" C, gas ");
      Serial.print(gas);
      Serial.println(")");
    } else {
      Serial.println("[EVENT] Exhaust -> OFF");
    }
  }

  // Atap
  bool perluBukaAtap = (suhu > SUHU_MAX) || (gas > GAS_MIN);
  if (perluBukaAtap != atapTerbuka) {
    atapTerbuka = perluBukaAtap;
    atapServo.write(atapTerbuka ? 90 : 0);
    Serial.print("[EVENT] Atap -> ");
    Serial.println(atapTerbuka ? "BUKA" : "TUTUP");
  }

  // Water Pump
  unsigned long now = millis();
  static unsigned long pumpStartTime = 0;

  if (levelAir < AIR_TANDON_MIN && pumpOn) {
    pumpOn = false;
    digitalWrite(RELAY_PUMP, LOW);
    pumpStartTime = 0;
    Serial.print("[EVENT] Pompa -> OFF (safety, air ");
    Serial.print(levelAir, 0);
    Serial.println("%)");
  }
  
  if (now - lastPumpCycle >= PUMP_CYCLE && !pumpOn && levelAir > AIR_TANDON_MIN) {
    pumpOn = true;
    digitalWrite(RELAY_PUMP, HIGH);
    pumpStartTime = now;
    lastPumpCycle = now;
    Serial.print("[EVENT] Pompa -> ON (siklus, air ");
    Serial.print(levelAir, 0);
    Serial.println("%)");
  }
  
  if (pumpOn && pumpStartTime > 0 && (now - pumpStartTime >= PUMP_DURATION)) {
    pumpOn = false;
    digitalWrite(RELAY_PUMP, LOW);
    pumpStartTime = 0;
    Serial.println("[EVENT] Pompa -> OFF (selesai siklus)");
  }

  // Alarm
  bool alarmSuhu = (suhu > SUHU_ALARM_HIGH) || (suhu < SUHU_ALARM_LOW);
  bool alarmGas  = (gas > GAS_ALARM);
  bool alarmAir  = (levelAir < AIR_TANDON_ALARM);
  bool alarmBaru = alarmSuhu || alarmGas || alarmAir;

  if (alarmBaru != alarmActive) {
    alarmActive = alarmBaru;
    
    if (alarmActive) {
      Serial.print("[EVENT] Alarm -> AKTIF (trigger: ");
      if (alarmSuhu) Serial.print("SUHU ");
      if (alarmGas)  Serial.print("GAS ");
      if (alarmAir)  Serial.print("AIR ");
      Serial.println(")");
      tone(BUZZER_PIN, 1000, 500);
    } else {
      Serial.println("[EVENT] Alarm -> OFF");
      noTone(BUZZER_PIN);
    }
  }
  
  if (alarmActive) {
    static unsigned long lastBeep = 0;
    if (now - lastBeep >= 3000) {
      lastBeep = now;
      tone(BUZZER_PIN, 1500, 150);
    }
  }
}

// ============ KIRIM DATA KE MQTT ============
void kirimDataMQTT() {
  if (!mqtt.connected()) return;
  
  StaticJsonDocument<384> doc;
  doc["suhu"]          = suhu;
  doc["kelembapan"]    = kelembapan;
  doc["gas"]           = gas;
  doc["gas_ppm"]       = gasPPM();
  doc["cahaya"]        = cahaya;
  doc["cahaya_persen"] = cahayaPersen();
  doc["level_air"]     = levelAir;
  doc["jarak_air"]     = jarakAir;
  doc["mode"]          = modeOtomatis ? "auto" : "manual";
  doc["growlight"]     = growLightOn;
  doc["exhaust"]       = exhaustOn;
  doc["pompa"]         = pumpOn;
  doc["atap"]          = atapTerbuka;
  doc["alarm"]         = alarmActive;
  
  char buffer[384];
  serializeJson(doc, buffer);
  mqtt.publish(TOPIC_SENSOR, buffer);
  
  Serial.print("[MQTT] -> Publish OK (");
  Serial.print(strlen(buffer));
  Serial.println(" bytes)");
}

// ============ LCD ============
void tampilLcd() {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("T:");
  lcd.print(suhu, 1);
  lcd.print("C H:");
  lcd.print(kelembapan, 0);
  lcd.print("%");
  lcd.setCursor(15, 0);
  lcd.print(modeOtomatis ? "A" : "M");

  lcd.setCursor(0, 1);
  lcd.print("G:");
  lcd.print(gasPPM());
  lcd.print(" Air:");
  lcd.print(levelAir, 0);
  lcd.print("%");
}

// ============ SERIAL LOG STATUS ============
void logStatus() {
  Serial.println();
  Serial.println("===== GREENHOUSE STATUS =====");
  
  Serial.println("[SENSOR]");
  
  Serial.print("Suhu: ");
  Serial.print(suhu, 1);
  Serial.print(" C | RH: ");
  Serial.print(kelembapan, 1);
  Serial.println(" %");
  
  Serial.print("Gas: ");
  Serial.print(gas);
  Serial.print(" ADC | ");
  Serial.print(gasPPM());
  Serial.print(" ppm | ");
  Serial.println(gasStatus());
  
  Serial.print("Cahaya: ");
  Serial.print(cahaya);
  Serial.print(" ADC | ");
  Serial.print(cahayaPersen());
  Serial.print(" % | ");
  Serial.println(cahayaStatus());
  
  Serial.print("Air: ");
  Serial.print(jarakAir, 1);
  Serial.print(" cm | ");
  Serial.print(levelAir, 0);
  Serial.print(" % | ");
  Serial.println(airStatus());
  
  Serial.println("[AKTUATOR]");
  Serial.print("GrowLight: ");
  Serial.print(growLightOn ? "ON" : "OFF");
  Serial.print(" | Exhaust: ");
  Serial.print(exhaustOn ? "ON" : "OFF");
  Serial.print(" | Pompa: ");
  Serial.println(pumpOn ? "ON" : "OFF");
  Serial.print("Atap: ");
  Serial.println(atapTerbuka ? "BUKA" : "TUTUP");
  
  Serial.println("[ALARM]");
  Serial.print("Status: ");
  Serial.print(alarmActive ? "AKTIF" : "OFF");
  Serial.print(" | Trigger: ");
  bool ada = false;
  if (suhu > SUHU_ALARM_HIGH || suhu < SUHU_ALARM_LOW) { Serial.print("SUHU "); ada = true; }
  if (gas > GAS_ALARM) { Serial.print("GAS "); ada = true; }
  if (levelAir < AIR_TANDON_ALARM) { Serial.print("AIR "); ada = true; }
  if (!ada) Serial.print("-");
  Serial.println();
  
  Serial.println("=============================");
}

// ============ TOMBOL ============
void cekTombol() {
  static bool lastModeBtn = HIGH;
  static bool lastManualBtn = HIGH;
  
  bool modeBtn = digitalRead(BTN_MODE);
  if (modeBtn == LOW && lastModeBtn == HIGH) {
    modeOtomatis = !modeOtomatis;
    Serial.print("[EVENT] Tombol Mode -> ");
    Serial.println(modeOtomatis ? "OTOMATIS" : "MANUAL");
    delay(250);
  }
  lastModeBtn = modeBtn;

  bool manualBtn = digitalRead(BTN_MANUAL);
  if (manualBtn == LOW && lastManualBtn == HIGH && !modeOtomatis) {
    static int step = 0;
    step = (step + 1) % 5;
    Serial.print("[EVENT] Tombol Manual -> step ");
    Serial.println(step);

    switch(step) {
      case 0:
        growLightOn = exhaustOn = pumpOn = false;
        atapTerbuka = false;
        digitalWrite(RELAY_GROWLIGHT, LOW);
        digitalWrite(RELAY_EXHAUST, LOW);
        digitalWrite(RELAY_PUMP, LOW);
        atapServo.write(0);
        Serial.println("[EVENT] Manual: Semua OFF");
        break;
      case 1:
        growLightOn = true;
        digitalWrite(RELAY_GROWLIGHT, HIGH);
        Serial.println("[EVENT] Manual: Grow Light ON");
        break;
      case 2:
        exhaustOn = true;
        digitalWrite(RELAY_EXHAUST, HIGH);
        Serial.println("[EVENT] Manual: Exhaust ON");
        break;
      case 3:
        if (levelAir > AIR_TANDON_MIN) {
          pumpOn = true;
          digitalWrite(RELAY_PUMP, HIGH);
          Serial.println("[EVENT] Manual: Pompa ON");
        } else {
          Serial.print("[EVENT] Manual: Pompa DITOLAK (air ");
          Serial.print(levelAir, 0);
          Serial.println("%)");
        }
        break;
      case 4:
        atapTerbuka = true;
        atapServo.write(90);
        Serial.println("[EVENT] Manual: Atap BUKA");
        break;
    }
    delay(250);
  }
  lastManualBtn = manualBtn;
}

// ============ LOOP ============
void loop() {
  unsigned long now = millis();

  if (!mqtt.connected()) reconnectMQTT();
  mqtt.loop();

  if (now - lastRead >= INTERVAL_READ) {
    lastRead = now;
    bacaSensor();
    if (modeOtomatis) logikaOtomatis();
  }

  if (now - lastLcd >= INTERVAL_LCD) {
    lastLcd = now;
    tampilLcd();
  }

  if (now - lastSerial >= INTERVAL_SERIAL) {
    lastSerial = now;
    logStatus();
  }

  if (now - lastMqtt >= INTERVAL_MQTT) {
    lastMqtt = now;
    kirimDataMQTT();
  }

  cekTombol();
}