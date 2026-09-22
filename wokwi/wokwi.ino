#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <DHT.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <time.h>

// ============ DEBUG MODE ============
// Set 1 = percepat waktu 60x (untuk testing)
// Set 0 = waktu normal (untuk hardware asli)
#define DEBUG_TIME_SCALE 1

// ============ WIFI & MQTT ============
const char* WIFI_SSID = "Wokwi-GUEST";
const char* WIFI_PASS = "";
const char* MQTT_BROKER = "broker.hivemq.com";
const int MQTT_PORT = 1883;
const char* TOPIC_SENSOR = "greenhouse/sensor";
const char* TOPIC_CONTROL = "greenhouse/control";
const char* TOPIC_SCHEDULE = "greenhouse/schedule";
const char* TOPIC_EVENTS = "greenhouse/events";

String clientId;

// ============ NTP ============
const char* NTP_SERVER = "pool.ntp.org";
const long GMT_OFFSET_SEC = 7 * 3600;
const int DAYLIGHT_OFFSET_SEC = 0;

WiFiClient espClient;
PubSubClient mqtt(espClient);

// ============ PIN DEFINITIONS ============
#define DHTPIN 15
#define DHTTYPE DHT22
#define GAS_PIN 34
#define LDR_PIN 35
#define AIR_PIN 32
#define RELAY_GROWLIGHT 26
#define RELAY_EXHAUST 27
#define RELAY_PUMP 14
#define SERVO_PIN 13
#define BUZZER_PIN 25
#define BTN_MODE 4
#define BTN_MANUAL 2

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

// ============ EVENT LOG THRESHOLDS ============
const float SUHU_LOG_DELTA = 1.0;
const unsigned long SUHU_LOG_INTERVAL = 300000UL;
const unsigned long ACTUATOR_LOG_DEBOUNCE = 10000UL;

// ============ SCHEDULE ============
#define MAX_SCHEDULES 5

struct Schedule {
  int hour;
  int minute;
  int duration;
  bool active;
  bool triggeredToday;   // ⭐ track sudah trigger hari ini
  int lastTriggeredDay;  // ⭐ track hari terakhir trigger
};

Schedule schedules[MAX_SCHEDULES];
int scheduleCount = 0;

// ============ STATE ============
bool modeOtomatis = true;
bool growLightOn = false;
bool exhaustOn = false;
bool pumpOn = false;
bool atapTerbuka = false;
bool alarmActive = false;

bool pumpManualOverride = false;
unsigned long pumpStartTime = 0;
unsigned long pumpDuration = 0;
String pumpTrigger = "idle";

// NTP
bool ntpSynced = false;
unsigned long fakeClockMs = 0;

// Event log state
float lastLoggedSuhu = -999.0;
unsigned long lastSuhuLogTime = 0;
unsigned long lastGrowLightLogTime = 0;
unsigned long lastExhaustLogTime = 0;
unsigned long lastAtapLogTime = 0;

// Timing
unsigned long lastRead = 0;
unsigned long lastLcd = 0;
unsigned long lastSerial = 0;
unsigned long lastMqtt = 0;
unsigned long lastScheduleCheck = 0;
unsigned long lastMqttReconnect = 0;

const unsigned long INTERVAL_READ = 2000;
const unsigned long INTERVAL_LCD = 3000;
const unsigned long INTERVAL_SERIAL = 5000;
const unsigned long INTERVAL_MQTT = 3000;
const unsigned long INTERVAL_SCHEDULE_CHECK = 100;   // ⭐ cek jadwal tiap 100ms
const unsigned long MQTT_RECONNECT_INTERVAL = 5000;

// ============ SENSOR VALUES ============
float suhu = 25.0;
float kelembapan = 60.0;
int gas = 0;
int cahaya = 0;
int airRaw = 0;
float levelAir = 100.0;
float jarakAir = 5.0;

int currentHour = 0;
int currentMinute = 0;
int currentSecond = 0;
int currentDay = 0;

// ============ UTIL: Dapatkan waktu sekarang ============
bool getCurrentTime(int &hour, int &minute, int &second, int &day) {
  if (ntpSynced) {
    struct tm timeinfo;
    if (getLocalTime(&timeinfo, 100)) {
      hour = timeinfo.tm_hour;
      minute = timeinfo.tm_min;
      second = timeinfo.tm_sec;
      day = timeinfo.tm_yday;
      return true;
    }
    return false;
  } else {
    unsigned long totalSec = fakeClockMs / 1000;
    #if DEBUG_TIME_SCALE
    totalSec = totalSec * 60;
    #endif
    hour = (totalSec / 3600) % 24;
    minute = (totalSec / 60) % 60;
    second = totalSec % 60;
    day = (totalSec / 86400) % 365;
    return true;
  }
}

void print2digits(int v) {
  if (v < 0) {
    Serial.print("-");
    v = -v;
  }
  if (v < 10) Serial.print("0");
  Serial.print(v);
}

// ============ LOG EVENT KE MQTT ============
void logEvent(String type, String title, String desc, String severity, String icon = "", String event = "") {
  if (!mqtt.connected()) return;
  
  StaticJsonDocument<384> doc;
  doc["type"] = type;
  doc["title"] = title;
  doc["description"] = desc;
  doc["severity"] = severity;
  if (icon.length() > 0) doc["icon"] = icon;
  if (event.length() > 0) doc["event"] = event;
  
  char buffer[384];
  serializeJson(doc, buffer);
  mqtt.publish(TOPIC_EVENTS, buffer);
  
  Serial.print("[EVENT->MQTT] ");
  Serial.print(severity);
  Serial.print(": ");
  Serial.println(title);
}

// ============ LOG SUHU ============
void checkTemperatureLog() {
  unsigned long now = millis();
  
  bool perubahanSignifikan = false;
  if (lastLoggedSuhu < -900) {
    perubahanSignifikan = true;
  } else if (abs(suhu - lastLoggedSuhu) >= SUHU_LOG_DELTA) {
    perubahanSignifikan = true;
  }
  
  bool waktunyaLog = (now - lastSuhuLogTime) >= SUHU_LOG_INTERVAL;
  
  if (perubahanSignifikan || waktunyaLog) {
    String severity = "info";
    if (suhu > SUHU_ALARM_HIGH || suhu < SUHU_ALARM_LOW) {
      severity = "danger";
    } else if (suhu > SUHU_MAX) {
      severity = "warning";
    }
    
    String title = "Suhu: ";
    title += String(suhu, 1);
    title += " C";
    
    String desc;
    if (perubahanSignifikan && lastLoggedSuhu > -900) {
      desc = "Perubahan dari ";
      desc += String(lastLoggedSuhu, 1);
      desc += " C (";
      float delta = suhu - lastLoggedSuhu;
      if (delta > 0) desc += "+";
      desc += String(delta, 1);
      desc += " C)";
    } else if (waktunyaLog) {
      desc = "Monitoring berkala (heartbeat)";
    } else {
      desc = "Monitoring dimulai";
    }
    
    logEvent("sensor", title, desc, severity, "temperature", "temperature");
    
    lastLoggedSuhu = suhu;
    lastSuhuLogTime = now;
  }
}

// ============ MQTT CALLBACK ============
void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) message += (char)payload[i];
  message.trim();
  
  if (message.length() == 0 || !message.startsWith("{")) return;
  
  StaticJsonDocument<512> doc;
  if (deserializeJson(doc, message)) return;
  
  String action = doc["action"] | "";
  
  // ===== HANDLE SCHEDULE =====
  if (action == "set_schedules") {
    Serial.println("[SCHEDULE] Menerima jadwal dari Laravel...");
    
    int oldCount = scheduleCount;
    scheduleCount = 0;
    
    JsonArray arr = doc["schedules"].as<JsonArray>();
    for (JsonObject s : arr) {
      if (scheduleCount >= MAX_SCHEDULES) break;
      
      schedules[scheduleCount].hour = s["hour"] | 0;
      schedules[scheduleCount].minute = s["minute"] | 0;
      schedules[scheduleCount].duration = s["duration"] | 10;
      schedules[scheduleCount].active = true;
      schedules[scheduleCount].triggeredToday = false;   // reset
      schedules[scheduleCount].lastTriggeredDay = -1;
      
      Serial.print("  [");
      Serial.print(scheduleCount);
      Serial.print("] ");
      print2digits(schedules[scheduleCount].hour);
      Serial.print(":");
      print2digits(schedules[scheduleCount].minute);
      Serial.print(" (");
      Serial.print(schedules[scheduleCount].duration);
      Serial.println(" detik)");
      
      scheduleCount++;
    }
    
    Serial.print("[SCHEDULE] Total ");
    Serial.print(scheduleCount);
    Serial.println(" jadwal disimpan");
    return;
  }
  
  // ===== HANDLE CONTROL =====
  if (doc.containsKey("value")) {
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
      
      logEvent("control", 
        String("Grow Light ") + (growLightOn ? "ON" : "OFF"),
        "Dikontrol dari dashboard",
        growLightOn ? "success" : "info",
        "growlight", "growlight_manual");
    }
    else if (action == "exhaust") {
      exhaustOn = (value == "on");
      digitalWrite(RELAY_EXHAUST, exhaustOn ? HIGH : LOW);
      Serial.print("[EVENT] Exhaust -> ");
      Serial.println(exhaustOn ? "ON (MQTT)" : "OFF (MQTT)");
      
      logEvent("control",
        String("Exhaust ") + (exhaustOn ? "ON" : "OFF"),
        "Dikontrol dari dashboard",
        exhaustOn ? "warning" : "info",
        "exhaust", "exhaust_manual");
    }
    else if (action == "pompa") {
      bool newState = (value == "on");
      
      if (newState && levelAir < AIR_TANDON_MIN) {
        Serial.println("[EVENT] Pompa DITOLAK (air rendah)");
        logEvent("safety", "Pompa DITOLAK",
          "Air " + String(levelAir, 0) + " % (min 20%)",
          "warning", "warning", "pump_rejected");
      } else {
        pumpOn = newState;
        pumpManualOverride = newState;
        digitalWrite(RELAY_PUMP, pumpOn ? HIGH : LOW);
        pumpTrigger = pumpOn ? "manual" : "idle";
        
        if (pumpOn) pumpStartTime = millis();
        
        Serial.print("[EVENT] Pompa -> ");
        Serial.println(pumpOn ? "ON (MQTT Manual)" : "OFF (MQTT Manual)");
        
        logEvent("control",
          String("Pompa ") + (pumpOn ? "ON" : "OFF"),
          "Dikontrol dari dashboard",
          pumpOn ? "success" : "info",
          "pump", "pump_manual");
      }
    }
    else if (action == "atap") {
      atapTerbuka = (value == "on");
      atapServo.write(atapTerbuka ? 90 : 0);
      Serial.print("[EVENT] Atap -> ");
      Serial.println(atapTerbuka ? "BUKA (MQTT)" : "TUTUP (MQTT)");
      
      logEvent("control",
        String("Atap ") + (atapTerbuka ? "BUKA" : "TUTUP"),
        "Dikontrol dari dashboard",
        "info", "atap", "atap_manual");
    }
    else if (action == "mode") {
      modeOtomatis = (value == "auto");
      Serial.print("[EVENT] Mode -> ");
      Serial.println(modeOtomatis ? "OTOMATIS (MQTT)" : "MANUAL (MQTT)");
      
      // Reset pump override saat pindah ke AUTO
      if (modeOtomatis) {
        pumpManualOverride = false;
        // Reset schedule triggeredToday biar cek ulang
        for (int i = 0; i < scheduleCount; i++) {
          schedules[i].triggeredToday = false;
        }
      }
      
      logEvent("control",
        String("Mode ") + (modeOtomatis ? "OTOMATIS" : "MANUAL"),
        "Dikontrol dari dashboard",
        "info", "settings", "mode_change");
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
    delay(500);
    Serial.print(".");
    attempts++;
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("[WiFi] Connected! IP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("[WiFi] Failed!");
  }
}

// ============ SETUP NTP ============
void setupNTP() {
  Serial.print("[NTP] Syncing...");
  configTime(GMT_OFFSET_SEC, DAYLIGHT_OFFSET_SEC, NTP_SERVER);
  
  struct tm timeinfo;
  int attempts = 0;
  while (!getLocalTime(&timeinfo, 1000) && attempts < 10) {
    Serial.print(".");
    attempts++;
  }
  Serial.println();
  
  if (getLocalTime(&timeinfo, 100)) {
    ntpSynced = true;
    Serial.print("[NTP] Synced! Waktu: ");
    print2digits(timeinfo.tm_hour);
    Serial.print(":");
    print2digits(timeinfo.tm_min);
    Serial.print(":");
    print2digits(timeinfo.tm_sec);
    Serial.println();
  } else {
    ntpSynced = false;
    Serial.println("[NTP] Gagal sync - pakai jam internal");
  }
}

// ============ SETUP MQTT ============
void reconnectMQTT() {
  if (millis() - lastMqttReconnect < MQTT_RECONNECT_INTERVAL) return;
  lastMqttReconnect = millis();
  
  if (mqtt.connected()) return;
  
  Serial.print("[MQTT] Connecting as ");
  Serial.print(clientId);
  Serial.print("...");
  
  if (mqtt.connect(clientId.c_str())) {
    Serial.println("connected!");
    mqtt.subscribe(TOPIC_CONTROL);
    mqtt.subscribe(TOPIC_SCHEDULE);
    Serial.print("[MQTT] Subscribed: ");
    Serial.print(TOPIC_CONTROL);
    Serial.print(", ");
    Serial.println(TOPIC_SCHEDULE);
    
    delay(500);
    mqtt.publish(TOPIC_SENSOR, "{\"action\":\"ready\"}");
    Serial.println("[MQTT] -> Publish: ready (minta jadwal)");
  } else {
    Serial.print("failed rc=");
    Serial.print(mqtt.state());
    Serial.println(", retry in 5s");
  }
}

void setupMQTT() {
  mqtt.setServer(MQTT_BROKER, MQTT_PORT);
  mqtt.setCallback(callback);
  mqtt.setBufferSize(2048);
  mqtt.setKeepAlive(60);
  mqtt.setSocketTimeout(15);
  reconnectMQTT();
}

// ============ SETUP ============
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println();
  Serial.println("=== GREENHOUSE V3.3 STARTING ===");
  
  clientId = "gh-" + String(random(100000, 999999));
  Serial.print("[SYS] Client ID: ");
  Serial.println(clientId);
  
  #if DEBUG_TIME_SCALE
  Serial.println("[DEBUG] Time scale: 60x");
  #endif

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
  lcd.print("Greenhouse V3.3");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");
  delay(1000);
  lcd.clear();

  setupWiFi();
  setupNTP();
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

  airRaw = analogRead(AIR_PIN);
  levelAir = map(airRaw, 0, 4095, 0, 100);
  levelAir = constrain(levelAir, 0, 100);
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

// ============ CEK JADWAL POMPA (NEW LOGIC) ============
void cekJadwalPompa() {
  if (!modeOtomatis) return;
  if (pumpManualOverride) return;
  if (levelAir < AIR_TANDON_MIN) return;
  
  int hour, minute, second, day;
  if (!getCurrentTime(hour, minute, second, day)) return;
  
  int currentTotalMin = hour * 60 + minute;
  
  for (int i = 0; i < scheduleCount; i++) {
    if (!schedules[i].active) continue;
    
    // Reset triggeredToday kalau hari berubah
    if (schedules[i].lastTriggeredDay != day) {
      schedules[i].triggeredToday = false;
    }
    
    // Skip kalau sudah trigger hari ini
    if (schedules[i].triggeredToday) continue;
    
    int schedTotalMin = schedules[i].hour * 60 + schedules[i].minute;
    
    // ⭐ Trigger kalau waktu sekarang >= jadwal (catch-up)
    // Tapi batasi jangan lebih dari 5 menit lewat
    int diff = currentTotalMin - schedTotalMin;
    
    // Handle lewat tengah malam
    if (diff < -720) diff += 1440;
    if (diff > 720) diff -= 1440;
    
    if (diff >= 0 && diff <= 5) {   // 0-5 menit lewat
      Serial.print("[SCHEDULE] TRIGGER #");
      Serial.print(i);
      Serial.print(" (");
      print2digits(hour);
      Serial.print(":");
      print2digits(minute);
      Serial.print(" - jadwal ");
      print2digits(schedules[i].hour);
      Serial.print(":");
      print2digits(schedules[i].minute);
      Serial.print(", ");
      Serial.print(schedules[i].duration);
      Serial.println(" detik)");
      
      pumpOn = true;
      pumpStartTime = millis();
      pumpDuration = schedules[i].duration * 1000UL;
      pumpTrigger = "schedule";
      digitalWrite(RELAY_PUMP, HIGH);
      
      String t = "Pompa ON (jadwal)";
      String d = "Trigger ";
      if (hour < 10) d += "0";
      d += String(hour);
      d += ":";
      if (minute < 10) d += "0";
      d += String(minute);
      d += " (" + String(schedules[i].duration) + " detik)";
      
      logEvent("schedule", t, d, "success", "pump", "pump_schedule");
      
      schedules[i].triggeredToday = true;
      schedules[i].lastTriggeredDay = day;
      break;   // hanya 1 jadwal per cek
    }
  }
}

// ============ LOGIKA OTOMATIS ============
void logikaOtomatis() {
  unsigned long now = millis();
  
  // GROW LIGHT
  bool perluCahaya = (cahayaPersen() < CAHAYA_MIN_PCT);
  if (perluCahaya != growLightOn) {
    growLightOn = perluCahaya;
    digitalWrite(RELAY_GROWLIGHT, growLightOn ? HIGH : LOW);
    Serial.print("[EVENT] Grow Light -> ");
    Serial.print(growLightOn ? "ON" : "OFF");
    Serial.print(" (cahaya ");
    Serial.print(cahayaPersen());
    Serial.println("%)");
    
    if (now - lastGrowLightLogTime >= ACTUATOR_LOG_DEBOUNCE) {
      String t = "Grow Light " + String(growLightOn ? "ON" : "OFF");
      String d = "Cahaya " + String(cahayaPersen()) + " %";
      logEvent("actuator", t, d,
        growLightOn ? "success" : "info",
        "growlight", "growlight");
      lastGrowLightLogTime = now;
    }
  }

  // EXHAUST
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
    
    if (now - lastExhaustLogTime >= ACTUATOR_LOG_DEBOUNCE) {
      String t = "Exhaust " + String(exhaustOn ? "ON" : "OFF");
      String d = "Suhu " + String(suhu, 1) + " C, gas " + String(gas);
      logEvent("actuator", t, d,
        exhaustOn ? "warning" : "info",
        "exhaust", "exhaust");
      lastExhaustLogTime = now;
    }
  }

  // ATAP
  bool perluBukaAtap = (suhu > SUHU_MAX) || (gas > GAS_MIN);
  if (perluBukaAtap != atapTerbuka) {
    atapTerbuka = perluBukaAtap;
    atapServo.write(atapTerbuka ? 90 : 0);
    Serial.print("[EVENT] Atap -> ");
    Serial.println(atapTerbuka ? "BUKA" : "TUTUP");
    
    if (now - lastAtapLogTime >= ACTUATOR_LOG_DEBOUNCE) {
      String t = "Atap " + String(atapTerbuka ? "BUKA" : "TUTUP");
      String d = "Ventilasi otomatis";
      logEvent("actuator", t, d, "info", "atap", "atap");
      lastAtapLogTime = now;
    }
  }

  // WATER PUMP — safety & auto-off
  if (levelAir < AIR_TANDON_MIN && pumpOn) {
    pumpOn = false;
    pumpManualOverride = false;
    digitalWrite(RELAY_PUMP, LOW);
    pumpTrigger = "idle";
    Serial.print("[EVENT] Pompa -> OFF (safety, air ");
    Serial.print(levelAir, 0);
    Serial.println("%)");
    
    String d = "Air " + String(levelAir, 0) + " % (min 20%)";
    logEvent("safety", "Pompa OFF (safety)", d, "warning", "warning", "pump_safety");
  }
  
  if (pumpOn && pumpTrigger == "schedule" && pumpDuration > 0) {
    if (now - pumpStartTime >= pumpDuration) {
      pumpOn = false;
      digitalWrite(RELAY_PUMP, LOW);
      pumpTrigger = "idle";
      pumpDuration = 0;
      Serial.println("[EVENT] Pompa -> OFF (jadwal selesai)");
      
      logEvent("schedule", "Pompa OFF", "Jadwal selesai", "info", "pump", "pump_schedule_done");
    }
  }

  // ALARM
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
      
      String t = "";
      if (alarmSuhu) t += "SUHU ";
      if (alarmGas)  t += "GAS ";
      if (alarmAir)  t += "AIR ";
      
      String d = "Suhu " + String(suhu, 1) + " C, gas " + String(gas) + ", air " + String(levelAir, 0) + " %";
      logEvent("alarm", "Alarm AKTIF", "Trigger: " + t + " | " + d, "danger", "warning", "alarm_on");
    } else {
      Serial.println("[EVENT] Alarm -> OFF");
      noTone(BUZZER_PIN);
      
      logEvent("alarm", "Alarm NORMAL", "Kondisi kembali normal", "success", "check", "alarm_off");
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
  
  int hour, minute, second, day;
  getCurrentTime(hour, minute, second, day);
  
  StaticJsonDocument<512> doc;
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
  doc["pompa_trigger"] = pumpTrigger;
  doc["atap"]          = atapTerbuka;
  doc["alarm"]         = alarmActive;
  doc["hour"]          = hour;
  doc["minute"]        = minute;
  doc["ntp_synced"]    = ntpSynced;
  
  char buffer[512];
  serializeJson(doc, buffer);
  mqtt.publish(TOPIC_SENSOR, buffer);
  
  Serial.print("[MQTT] -> Publish OK (");
  Serial.print(strlen(buffer));
  Serial.print(" bytes) | ");
  print2digits(hour);
  Serial.print(":");
  print2digits(minute);
  Serial.print(":");
  print2digits(second);
  Serial.println();
}

// ============ LCD ============
void tampilLcd() {
  int hour, minute, second, day;
  getCurrentTime(hour, minute, second, day);
  
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
  lcd.print("Air:");
  lcd.print(levelAir, 0);
  lcd.print("% ");
  if (hour < 10) lcd.print("0");
  lcd.print(hour);
  lcd.print(":");
  if (minute < 10) lcd.print("0");
  lcd.print(minute);
}

// ============ SERIAL LOG STATUS ============
void logStatus() {
  int hour, minute, second, day;
  getCurrentTime(hour, minute, second, day);
  
  Serial.println();
  Serial.println("===== GREENHOUSE V3.3 STATUS =====");
  
  Serial.print("Waktu: ");
  print2digits(hour);
  Serial.print(":");
  print2digits(minute);
  Serial.print(":");
  print2digits(second);
  Serial.print(" (");
  Serial.print(ntpSynced ? "NTP" : "internal");
  Serial.println(")");
  
  #if DEBUG_TIME_SCALE
  Serial.println("Mode: DEBUG 60x");
  #endif
  
  Serial.print("Jadwal tersimpan: ");
  Serial.println(scheduleCount);
  
  // ⭐ Tampilkan detail jadwal + status triggeredToday
  for (int i = 0; i < scheduleCount; i++) {
    Serial.print("  [");
    Serial.print(i);
    Serial.print("] ");
    if (schedules[i].hour < 10) Serial.print("0");
    Serial.print(schedules[i].hour);
    Serial.print(":");
    if (schedules[i].minute < 10) Serial.print("0");
    Serial.print(schedules[i].minute);
    Serial.print(" (");
    Serial.print(schedules[i].duration);
    Serial.print("s) - ");
    Serial.println(schedules[i].triggeredToday ? "TRIGGERED" : "waiting");
  }
  
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
  Serial.print(pumpOn ? "ON" : "OFF");
  Serial.print(" (");
  Serial.print(pumpTrigger);
  Serial.println(")");
  
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
  
  Serial.println("==================================");
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
    
    if (modeOtomatis) {
      pumpManualOverride = false;
      for (int i = 0; i < scheduleCount; i++) {
        schedules[i].triggeredToday = false;
      }
    }
    
    logEvent("control", 
      String("Mode ") + (modeOtomatis ? "OTOMATIS" : "MANUAL"),
      "Dikontrol dari tombol fisik",
      "info", "settings", "mode_button");
    
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
        pumpManualOverride = false;
        pumpTrigger = "idle";
        digitalWrite(RELAY_GROWLIGHT, LOW);
        digitalWrite(RELAY_EXHAUST, LOW);
        digitalWrite(RELAY_PUMP, LOW);
        atapServo.write(0);
        Serial.println("[EVENT] Manual: Semua OFF");
        logEvent("control", "Manual: Semua OFF", "Step 0", "info", "power", "manual_step0");
        break;
      case 1:
        growLightOn = true;
        digitalWrite(RELAY_GROWLIGHT, HIGH);
        Serial.println("[EVENT] Manual: Grow Light ON");
        logEvent("control", "Manual: Grow Light ON", "Step 1", "success", "growlight", "manual_step1");
        break;
      case 2:
        exhaustOn = true;
        digitalWrite(RELAY_EXHAUST, HIGH);
        Serial.println("[EVENT] Manual: Exhaust ON");
        logEvent("control", "Manual: Exhaust ON", "Step 2", "success", "exhaust", "manual_step2");
        break;
      case 3:
        if (levelAir > AIR_TANDON_MIN) {
          pumpOn = true;
          pumpManualOverride = true;
          pumpTrigger = "manual";
          digitalWrite(RELAY_PUMP, HIGH);
          Serial.println("[EVENT] Manual: Pompa ON");
          logEvent("control", "Manual: Pompa ON", "Step 3", "success", "pump", "manual_step3");
        } else {
          Serial.print("[EVENT] Manual: Pompa DITOLAK (air ");
          Serial.print(levelAir, 0);
          Serial.println("%)");
          logEvent("safety", "Manual: Pompa DITOLAK",
            "Air " + String(levelAir, 0) + " %", "warning", "warning", "pump_rejected");
        }
        break;
      case 4:
        atapTerbuka = true;
        atapServo.write(90);
        Serial.println("[EVENT] Manual: Atap BUKA");
        logEvent("control", "Manual: Atap BUKA", "Step 4", "success", "atap", "manual_step4");
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

  if (!ntpSynced) {
    fakeClockMs = now;
  }

  int hour, minute, second, day;
  getCurrentTime(hour, minute, second, day);
  currentHour = hour;
  currentMinute = minute;
  currentSecond = second;
  currentDay = day;

  // Baca sensor & logika otomatis
  if (now - lastRead >= INTERVAL_READ) {
    lastRead = now;
    bacaSensor();
    if (modeOtomatis) logikaOtomatis();
    checkTemperatureLog();
  }

  // ⭐ CEK JADWAL tiap 100ms (jauh lebih sering, biar tidak kelewat)
  if (now - lastScheduleCheck >= INTERVAL_SCHEDULE_CHECK) {
    lastScheduleCheck = now;
    if (modeOtomatis) cekJadwalPompa();
  }

  // LCD
  if (now - lastLcd >= INTERVAL_LCD) {
    lastLcd = now;
    tampilLcd();
  }

  // Serial log
  if (now - lastSerial >= INTERVAL_SERIAL) {
    lastSerial = now;
    logStatus();
  }

  // MQTT publish
  if (now - lastMqtt >= INTERVAL_MQTT) {
    lastMqtt = now;
    kirimDataMQTT();
  }

  cekTombol();
}