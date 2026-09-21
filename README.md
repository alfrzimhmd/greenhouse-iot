# 🌿 Greenhouse Mini — IoT Monitoring & Control System

Sistem monitoring dan kontrol greenhouse berbasis **Internet of Things (IoT)** yang menggabungkan simulasi **ESP32 (Wokwi)** dengan dashboard web modern berbasis **Laravel**.

Project ini memungkinkan pengguna untuk memantau kondisi lingkungan greenhouse secara **real-time** dan mengontrol aktuator dari jarak jauh melalui website.

---

## 📖 Daftar Isi

* [🌱 Pengertian](#-pengertian)
* [✨ Fitur Utama](#-fitur-utama)
* [🏗️ Arsitektur Sistem](#️-arsitektur-sistem)
* [🛠️ Tech Stack](#️-tech-stack)
* [📁 Struktur Project](#-struktur-project)
* [🔌 Komponen IoT](#-komponen-iot)
* [🔄 Alur Data](#-alur-data)
* [🗄️ Database Schema](#️-database-schema)
* [🌐 API Endpoints](#-api-endpoints)
* [🚀 Cara Menjalankan](#-cara-menjalankan)
* [🧪 Testing](#-testing)
* [🔧 Troubleshooting](#-troubleshooting)
* [🔮 Pengembangan Selanjutnya](#-pengembangan-selanjutnya)
* [📚 Referensi](#-referensi)
* [👨‍💻 Author](#-author)
* [📄 License](#-license)
* [🙏 Ucapan Terima Kasih](#-ucapan-terima-kasih)

---

## 🌱 Pengertian

**Greenhouse Mini** adalah sistem IoT yang dirancang untuk memantau dan mengontrol kondisi lingkungan greenhouse secara otomatis maupun manual.

Sistem ini terdiri dari tiga komponen utama:

1. **Device IoT (ESP32)** — Membaca sensor dan mengontrol aktuator.
2. **Backend (Laravel)** — Menerima data, menyimpan data ke database, dan menyediakan API.
3. **Frontend (Dashboard)** — Menampilkan data secara real-time dan menyediakan kontrol aktuator.

Sistem menggunakan protokol **MQTT** sebagai jembatan komunikasi antara device IoT dan backend sehingga data dapat mengalir secara real-time dengan latensi rendah.

### 🎯 Tujuan Project

* Memantau kondisi greenhouse seperti suhu, kelembapan, kelembapan tanah, dan cahaya secara real-time.
* Mengontrol aktuator seperti lampu, kipas, pompa, dan atap dari jarak jauh.
* Menyimpan history data sensor untuk kebutuhan analisis.
* Menyediakan mode otomatis berdasarkan threshold yang telah ditentukan.

---

## ✨ Fitur Utama

### 1. Monitoring Sensor

* 🌡️ **Suhu & Kelembapan Udara** — Menggunakan sensor DHT22.
* 🌱 **Kelembapan Tanah** — Menggunakan potentiometer sebagai simulasi soil moisture.
* 💡 **Intensitas Cahaya** — Menggunakan LDR/photoresistor.

### 2. Kontrol Aktuator

* 💡 **Lampu** — Relay, menyala otomatis saat kondisi gelap.
* 🌀 **Kipas** — Relay, menyala otomatis saat suhu tinggi.
* 💧 **Pompa** — LED indikator, menyala otomatis saat tanah kering.
* 🏠 **Atap** — Servo, membuka otomatis saat suhu tinggi.
* 🔔 **Buzzer** — Alarm saat suhu berada pada kondisi ekstrem.

### 3. Mode Operasi

* **Otomatis** — Sistem bekerja berdasarkan threshold yang telah ditentukan.
* **Manual** — Kontrol penuh melalui dashboard atau tombol fisik.

### 4. Dashboard

* Sensor cards dengan progress bar.
* Mode selector otomatis/manual.
* Tombol kontrol aktuator.
* Grafik history menggunakan bar chart.
* Auto-refresh setiap 1 detik.

### 5. Komunikasi

* MQTT publish/subscribe.
* REST API untuk frontend.
* WebSocket-ready untuk pengembangan selanjutnya.

---

## 🏗️ Arsitektur Sistem

```text
┌─────────────────────────────────────────────────────────────┐
│                    WOKWI ESP32 (Simulasi)                  │
│                                                             │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌─────────┐ │
│  │  DHT22   │   │   Soil   │   │   LDR    │   │ Button  │ │
│  │  Sensor  │   │  Sensor  │   │  Sensor  │   │         │ │
│  └────┬─────┘   └────┬─────┘   └────┬─────┘   └────┬────┘ │
│       │              │              │              │      │
│       └──────────────┴──────────────┴──────────────┘      │
│                          │                                 │
│                    ┌─────▼─────┐                           │
│                    │   ESP32   │                           │
│                    │  DevKit   │                           │
│                    └─────┬─────┘                           │
│                          │                                 │
│          ┌───────────────┼────────────────┐                │
│          │               │                │                │
│     ┌────▼────┐    ┌─────▼─────┐   ┌────▼─────┐          │
│     │  Relay  │    │   Servo   │   │   LCD    │          │
│     │  Lampu  │    │   Atap    │   │   16x2   │          │
│     │  Kipas  │    │           │   │          │          │
│     └─────────┘    └───────────┘   └──────────┘          │
└──────────────────────────┬─────────────────────────────────┘
                           │
                           │ MQTT (WiFi)
                           │
                           │ Publish:
                           │ greenhouse/sensor
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                      HIVEMQ BROKER                          │
│                  broker.hivemq.com:1883                     │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ MQTT
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL BACKEND                          │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              MqttSubscribe Command                    │  │
│  │                                                       │  │
│  │  • Subscribe greenhouse/sensor                       │  │
│  │  • Parse data                                         │  │
│  │  • Simpan ke MySQL                                    │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                     REST API                          │  │
│  │                                                       │  │
│  │  • GET  /api/sensor/latest                           │  │
│  │  • GET  /api/sensor/history                          │  │
│  │  • GET  /api/sensor/stats                            │  │
│  │  • POST /api/control                                 │  │
│  │  • GET  /api/control/history                         │  │
│  └───────────────────────────────────────────────────────┘  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ HTTP
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                   DASHBOARD (Browser)                      │
│                                                             │
│  • Sensor cards (suhu, kelembapan, tanah, cahaya)         │
│  • Mode selector (otomatis/manual)                         │
│  • Tombol kontrol aktuator                                 │
│  • Grafik history (ApexCharts)                             │
│  • Auto-refresh setiap 1 detik                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

### Backend

| Teknologi           | Versi | Keterangan            |
| ------------------- | ----: | --------------------- |
| **Laravel**         | 13.32 | Framework PHP         |
| **PHP**             |   8.4 | Bahasa pemrograman    |
| **MySQL**           |   8.0 | Database              |
| **php-mqtt/client** | 2.3.2 | MQTT client untuk PHP |

### Frontend

| Teknologi        |  Versi | Keterangan                |
| ---------------- | -----: | ------------------------- |
| **Blade**        |      — | Template engine Laravel   |
| **Tailwind CSS** |      — | Utility-first CSS via CDN |
| **Alpine.js**    | 3.13.5 | Reactive JavaScript       |
| **ApexCharts**   | 3.45.0 | Chart library             |

### IoT

| Teknologi                   | Versi | Keterangan         |
| --------------------------- | ----: | ------------------ |
| **ESP32 DevKit V1**         |     — | Microcontroller    |
| **Arduino CLI**             | 1.5.1 | Compile tool       |
| **Wokwi VS Code Extension** |     — | Simulator          |
| **HiveMQ**                  |     — | Public MQTT broker |

---

## 📁 Struktur Project

```text
greenhouse-iot/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── MqttSubscribe.php       # Command subscribe MQTT
│   │
│   ├── Http/
│   │   └── Controllers/
│   │       ├── DashboardController.php # Halaman dashboard
│   │       └── Api/
│   │           ├── SensorController.php # API sensor
│   │           └── ControlController.php # API control + publish MQTT
│   │
│   └── Models/
│       ├── SensorLog.php                # Model sensor
│       └── ControlLog.php               # Model control
│
├── database/
│   └── migrations/
│       ├── xxxx_create_sensor_logs_table.php
│       └── xxxx_create_control_logs_table.php
│
├── resources/
│   └── views/
│       ├── dashboard/
│       │   └── index.blade.php          # Dashboard utama
│       └── layouts/
│           └── app.blade.php            # Layout
│
├── routes/
│   ├── api.php                          # Route API
│   └── web.php                          # Route web
│
├── wokwi/
│   ├── wokwi.ino                        # Sketch ESP32
│   ├── diagram.json                     # Wiring Wokwi
│   ├── wokwi.toml                        # Config Wokwi VS Code
│   ├── libraries.txt                    # Daftar library
│   └── build/
│       └── esp32.esp32.esp32/           # Hasil compile
│           ├── wokwi.ino.bin
│           ├── wokwi.ino.elf
│           └── wokwi.ino.map
│
├── .vscode/
│   ├── settings.json                    # Setting VS Code
│   ├── tasks.json                       # Task compile
│   └── launch.json                      # Debug config
│
├── .env                                 # Environment
├── .gitignore
├── composer.json
└── README.md
```

---

## 🔌 Komponen IoT

### Komponen Utama

| Komponen               | Pin ESP32                    | Fungsi                                |
| ---------------------- | ---------------------------- | ------------------------------------- |
| **ESP32 DevKit V1**    | —                            | MCU + WiFi                            |
| **DHT22**              | GPIO 15                      | Sensor suhu & kelembapan udara        |
| **Potentiometer 10kΩ** | GPIO 34                      | Simulasi soil moisture                |
| **LDR Photoresistor**  | GPIO 35                      | Sensor intensitas cahaya              |
| **Relay 1 (Lampu)**    | GPIO 26                      | Kontrol lampu                         |
| **Relay 2 (Kipas)**    | GPIO 27                      | Kontrol kipas                         |
| **LED Red (Lampu)**    | GPIO 26                      | Indikator lampu melalui resistor 220Ω |
| **LED Red (Kipas)**    | GPIO 27                      | Indikator kipas melalui resistor 220Ω |
| **LED Green (Pompa)**  | GPIO 14                      | Indikator pompa melalui resistor 220Ω |
| **Servo SG90**         | GPIO 13                      | Kontrol atap otomatis                 |
| **LCD 16x2 I2C**       | GPIO 21 (SDA), GPIO 22 (SCL) | Display lokal                         |
| **Buzzer**             | GPIO 25                      | Alarm suhu ekstrem                    |
| **Push Button 1**      | GPIO 32                      | Tombol mode auto/manual               |
| **Push Button 2**      | GPIO 33                      | Tombol manual step                    |

### Komponen Tambahan

| Komponen          |   Qty | Fungsi            |
| ----------------- | ----: | ----------------- |
| **Resistor 220Ω** |     3 | Pembatas arus LED |
| **Resistor 10kΩ** |     1 | Pull-up LDR       |
| **Breadboard**    |     1 | Prototyping       |
| **Jumper Wires**  | 1 set | Koneksi           |

### Threshold Logika Otomatis

```cpp
const float SUHU_MIN = 22.0;      // Suhu minimum (°C)
const float SUHU_MAX = 30.0;      // Suhu maksimum (°C)
const float TANAH_MIN = 40.0;     // Kelembapan tanah minimum (%)
const int CAHAYA_MIN = 500;       // Intensitas cahaya minimum (ADC)
```

### Logika Otomatis

| Kondisi                 | Aksi            |
| ----------------------- | --------------- |
| Cahaya < 500            | Lampu ON        |
| Suhu > 30°C             | Kipas ON        |
| Tanah < 40%             | Pompa ON        |
| Suhu > 30°C             | Atap BUKA       |
| Suhu > 35°C atau < 15°C | Buzzer berbunyi |

---

## 🔄 Alur Data

### 1. Alur Monitoring — Device → Dashboard

```text
┌──────────────────────┐
│       Sensor         │
│                      │
│ • DHT22              │
│ • Potentiometer      │
│ • LDR                │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│        ESP32         │
│                      │
│ • Membaca sensor     │
│ • Proses data        │
│ • Logika otomatis    │
│ • Format JSON        │
└──────────┬───────────┘
           │
           │ MQTT Publish
           │ Topic:
           │ greenhouse/sensor
           ▼
┌──────────────────────┐
│       HiveMQ         │
│     MQTT Broker      │
└──────────┬───────────┘
           │
           │ MQTT Subscribe
           ▼
┌──────────────────────┐
│       Laravel        │
│                      │
│ • mqtt:subscribe     │
│ • Parse JSON         │
│ • Simpan ke MySQL    │
└──────────┬───────────┘
           │
           │ SQL INSERT
           ▼
┌──────────────────────┐
│        MySQL         │
│                      │
│    sensor_logs       │
└──────────┬───────────┘
           │
           │ HTTP GET
           ▼
┌──────────────────────┐
│      Dashboard       │
│      (Browser)       │
│                      │
│ Polling setiap 1 detik│
└──────────────────────┘
```

Contoh data JSON:

```json
{
  "suhu": 25,
  "kelembapan": 60,
  "tanah": 45,
  "cahaya": 1200,
  "mode": "auto",
  "lampu": false,
  "kipas": false,
  "pompa": false,
  "atap": false
}
```

### 2. Alur Kontrol — Dashboard → Device

```text
┌──────────────────────┐
│        User          │
│      Dashboard       │
│                      │
│ Klik "Lampu ON"      │
└──────────┬───────────┘
           │
           │ POST /api/control
           │
           ▼
┌──────────────────────┐
│       Laravel        │
│        API           │
│                      │
│ • Validate request   │
│ • Publish MQTT       │
└──────────┬───────────┘
           │
           │ MQTT Publish
           │ Topic:
           │ greenhouse/control
           ▼
┌──────────────────────┐
│       HiveMQ         │
│     MQTT Broker      │
└──────────┬───────────┘
           │
           │ MQTT Subscribe
           ▼
┌──────────────────────┐
│        ESP32         │
│        Wokwi         │
│                      │
│ • Callback MQTT      │
│ • Parse JSON         │
│ • Kontrol aktuator   │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│      Aktuator        │
│                      │
│     Lampu ON         │
└──────────────────────┘
```

---

## 🗄️ Database Schema

### Tabel `sensor_logs`

Menyimpan history data sensor.

| Kolom        | Tipe        | Keterangan              |
| ------------ | ----------- | ----------------------- |
| `id`         | BIGINT      | Primary key             |
| `suhu`       | DOUBLE      | Suhu (°C)               |
| `kelembapan` | DOUBLE      | Kelembapan udara (%)    |
| `tanah`      | DOUBLE      | Kelembapan tanah (%)    |
| `cahaya`     | INT         | Intensitas cahaya (ADC) |
| `mode`       | VARCHAR(10) | `auto` / `manual`       |
| `lampu`      | TINYINT(1)  | Status lampu            |
| `kipas`      | TINYINT(1)  | Status kipas            |
| `pompa`      | TINYINT(1)  | Status pompa            |
| `atap`       | TINYINT(1)  | Status atap             |
| `created_at` | TIMESTAMP   | Waktu record            |
| `updated_at` | TIMESTAMP   | Waktu update            |

### Tabel `control_logs`

Menyimpan history perintah kontrol.

| Kolom        | Tipe        | Keterangan                                |
| ------------ | ----------- | ----------------------------------------- |
| `id`         | BIGINT      | Primary key                               |
| `action`     | VARCHAR(50) | `lampu`, `kipas`, `pompa`, `atap`, `mode` |
| `value`      | VARCHAR(50) | `on`, `off`, `auto`, `manual`             |
| `source`     | VARCHAR(20) | `web`, `mqtt`, `button`                   |
| `created_at` | TIMESTAMP   | Waktu record                              |
| `updated_at` | TIMESTAMP   | Waktu update                              |

---

## 🌐 API Endpoints

### Sensor

| Method | Endpoint                       | Fungsi                                     |
| ------ | ------------------------------ | ------------------------------------------ |
| `GET`  | `/api/sensor/latest`           | Mengambil data sensor terbaru              |
| `GET`  | `/api/sensor/history?limit=50` | Mengambil history sensor                   |
| `GET`  | `/api/sensor/stats`            | Mengambil statistik sensor (min, max, avg) |

### Control

| Method | Endpoint                        | Fungsi                    |
| ------ | ------------------------------- | ------------------------- |
| `POST` | `/api/control`                  | Mengirim perintah kontrol |
| `GET`  | `/api/control/history?limit=50` | Mengambil history kontrol |

### Contoh Request

**POST `/api/control`**

```json
{
  "action": "lampu",
  "value": "on"
}
```

### Contoh Response

```json
{
  "success": true,
  "message": "Perintah terkirim",
  "data": {
    "id": 1,
    "action": "lampu",
    "value": "on",
    "source": "web"
  }
}
```

---

## 🚀 Cara Menjalankan

### Prasyarat

Pastikan perangkat dan software berikut telah tersedia:

* PHP 8.2+ & Composer
* MySQL 8.0+
* Node.js & NPM
* Arduino CLI 1.5+
* VS Code + Wokwi Extension
* ESP32 Core terinstall di Arduino CLI

### Setup Awal

#### 1. Clone Project

```bash
cd ~/pemrograman/laravel

git clone <repo-url> greenhouse-iot

cd greenhouse-iot
```

#### 2. Install Dependency

```bash
composer install
npm install
```

#### 3. Setup Environment

```bash
cp .env.example .env

php artisan key:generate
```

#### 4. Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=greenhouse
DB_USERNAME=root
DB_PASSWORD=
```

#### 5. Buat Database

Masuk ke MySQL:

```bash
mysql -u root -p
```

Kemudian jalankan:

```sql
CREATE DATABASE greenhouse;
```

Keluar dari MySQL:

```sql
EXIT;
```

#### 6. Jalankan Migration

```bash
php artisan migrate
```

---

## ▶️ Menjalankan Project

Project membutuhkan beberapa service yang berjalan secara bersamaan.

### Terminal 1 — MySQL

Jika MySQL belum berjalan:

```bash
startlamp
```

Atau:

```bash
sudo systemctl start mysql
```

### Terminal 2 — MQTT Subscriber

```bash
cd ~/pemrograman/laravel/greenhouse-iot

php artisan mqtt:subscribe
```

### Terminal 3 — Laravel Server

```bash
cd ~/pemrograman/laravel/greenhouse-iot

php artisan serve
```

### Terminal 4 — Wokwi

Buka project menggunakan VS Code:

```bash
cd ~/pemrograman/laravel/greenhouse-iot

code .
```

Kemudian:

1. Tekan `F1`.
2. Pilih **Wokwi: Start Simulator**.

### Browser

Buka:

```text
http://localhost:8000
```

---

## 🧪 Testing

### 1. Test API dengan cURL

#### Test Latest Sensor

```bash
curl http://localhost:8000/api/sensor/latest
```

#### Test History

```bash
curl http://localhost:8000/api/sensor/history?limit=10
```

#### Test Statistics

```bash
curl http://localhost:8000/api/sensor/stats
```

#### Test Control

```bash
curl -X POST http://localhost:8000/api/control \
  -H "Content-Type: application/json" \
  -d '{"action":"lampu","value":"on"}'
```

---

### 2. Test MQTT dengan Mosquitto

#### Monitor Control

```bash
mosquitto_sub \
  -h broker.hivemq.com \
  -t greenhouse/control \
  -v
```

#### Publish Data Sensor Manual

```bash
mosquitto_pub \
  -h broker.hivemq.com \
  -t greenhouse/sensor \
  -m '{"suhu":25,"kelembapan":60,"tanah":45,"cahaya":1200,"mode":"auto","lampu":false,"kipas":false,"pompa":false,"atap":false}'
```

---

### 3. Test End-to-End

1. Jalankan seluruh service.
2. Buka dashboard.
3. Klik tombol kontrol pada dashboard.
4. Pastikan Wokwi merespons perintah.
5. Ubah nilai sensor di Wokwi.
6. Pastikan dashboard menerima dan menampilkan data terbaru.

---

## 🔧 Troubleshooting

### Masalah Umum

| Masalah                  | Penyebab          | Solusi                                        |
| ------------------------ | ----------------- | --------------------------------------------- |
| `404 /api/sensor/latest` | Database kosong   | Pastikan `mqtt:subscribe` berjalan            |
| Dashboard kosong         | Server mati       | Jalankan `php artisan serve`                  |
| Data tidak masuk DB      | Command MQTT mati | Restart `php artisan mqtt:subscribe`          |
| Wokwi tidak publish      | WiFi/MQTT gagal   | Cek koneksi dan topic                         |
| Tombol tidak merespons   | CSRF/API error    | Cek console browser dengan `F12`              |
| MySQL error              | Service mati      | Jalankan `sudo systemctl start mysql`         |
| AUTO retained message    | Sisa test lama    | Hapus retained message dengan command berikut |

```bash
mosquitto_pub \
  -h broker.hivemq.com \
  -t greenhouse/control \
  -n \
  -r
```

### Cek Laravel Log

```bash
tail -50 storage/logs/laravel.log
```

### Cek MySQL Log

```bash
sudo tail -50 /var/log/mysql/error.log
```

---

## 🔮 Pengembangan Selanjutnya

### Fitur

* [ ] **Halaman History** — Menampilkan seluruh data sensor dengan filter.
* [ ] **Login/Auth** — Menggunakan Laravel Breeze/Fortify.
* [ ] **Notifikasi** — Email/Telegram ketika suhu berada pada kondisi ekstrem.
* [ ] **Export CSV** — Download data sensor.
* [ ] **Multi-device** — Mendukung beberapa greenhouse.
* [ ] **WebSocket** — Real-time tanpa polling.
* [ ] **Mobile App** — Flutter/React Native.
* [ ] **AI Chatbot** — Integrasi GPT untuk voice command.
* [ ] **Kamera** — ESP32-CAM untuk monitoring visual.
* [ ] **Deployment** — VPS + domain + SSL.

### Upgrade Hardware

* [ ] **Sensor Soil Moisture Asli** — Menggantikan potentiometer.
* [ ] **Sensor Ultrasonic** — Monitoring level air tandon.
* [ ] **RTC DS3231** — Menambahkan real-time clock.
* [ ] **ESP32-CAM** — Monitoring visual.
* [ ] **Sensor pH** — Untuk kebutuhan hidroponik.

---

## 📚 Referensi

* [Laravel Documentation](https://laravel.com/docs)
* [ESP32 Arduino Core](https://github.com/espressif/arduino-esp32)
* [MQTT Protocol](https://mqtt.org/)
* [Wokwi Simulator](https://wokwi.com/)
* [ApexCharts](https://apexcharts.com/)
* [Alpine.js](https://alpinejs.dev/)
* [Tailwind CSS](https://tailwindcss.com/)

---

## 👨‍💻 Author

**Nama:** [Nama Kamu]
**Email:** [Email Kamu]
**GitHub:** [GitHub Kamu]

---

## 📄 License

Project ini menggunakan **MIT License**.

Bebas digunakan untuk pembelajaran dan pengembangan.

---

## 🙏 Ucapan Terima Kasih

* Allah SWT yang telah memberikan kemudahan.
* Keluarga yang selalu mendukung.
* Komunitas Laravel & Arduino Indonesia.
* Semua pihak yang telah membantu.

---

<div align="center">

**Dibuat dengan ❤️ untuk pembelajaran IoT Indonesia**

</div>
