<?php

namespace App\Console\Commands;

use App\Models\SensorLog;
use App\Models\Schedule;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe ke MQTT broker dan simpan data sensor & events';

    private $broker = 'broker.hivemq.com';
    private $port = 1883;

    public function handle()
    {
        $this->info('Memulai MQTT Subscriber...');

        $clientId = 'laravel-greenhouse-' . rand(1000, 9999);

        $mqtt = new MqttClient($this->broker, $this->port, $clientId);
        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('greenhouse/lastwill')
            ->setLastWillMessage('client disconnected')
            ->setLastWillQualityOfService(1);

        try {
            $mqtt->connect($settings, true);
            $this->info("Terhubung ke broker: {$this->broker}:{$this->port}");
            
            $mqtt->subscribe('greenhouse/sensor', function ($topic, $message) {
                $this->handleSensor($topic, $message);
            }, 0);
            
            $mqtt->subscribe('greenhouse/events', function ($topic, $message) {
                $this->handleEvent($topic, $message);
            }, 0);
            
            $this->info("Subscribe: greenhouse/sensor, greenhouse/events");
            $this->info("Menunggu data... (Ctrl+C untuk stop)");
            $this->newLine();

            $mqtt->loop(true);
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle data sensor dari ESP32
     */
    private function handleSensor(string $topic, string $message)
    {
        try {
            $data = json_decode($message, true);

            if (!$data) {
                $this->warn('Data bukan JSON valid, skip.');
                return;
            }

            // Handle "ready" dari ESP32
            if (isset($data['action']) && $data['action'] === 'ready') {
                $this->info('ESP32 minta jadwal - mengirim ulang...');
                $this->publishSchedules();
                return;
            }

            // Simpan sensor
            $log = SensorLog::create([
                'suhu'          => $data['suhu'] ?? 0,
                'kelembapan'    => $data['kelembapan'] ?? 0,
                'gas'           => $data['gas'] ?? 0,
                'gas_ppm'       => $data['gas_ppm'] ?? 0,
                'cahaya'        => $data['cahaya'] ?? 0,
                'cahaya_persen' => $data['cahaya_persen'] ?? 0,
                'level_air'     => $data['level_air'] ?? 100,
                'jarak_air'     => $data['jarak_air'] ?? 5,
                'mode'          => $data['mode'] ?? 'auto',
                'growlight'     => $data['growlight'] ?? false,
                'exhaust'       => $data['exhaust'] ?? false,
                'pompa'         => $data['pompa'] ?? false,
                'atap'          => $data['atap'] ?? false,
                'alarm'         => $data['alarm'] ?? false,
            ]);

            $this->info("ID: {$log->id} | Suhu: {$log->suhu}C | Air: {$log->level_air}% | Gas: {$log->gas_ppm}ppm");
        } catch (\Exception $e) {
            $this->error('Gagal simpan sensor: ' . $e->getMessage());
        }
    }

    /**
     * Handle event dari ESP32 — rotating log
     */
    private function handleEvent(string $topic, string $message)
    {
        try {
            $data = json_decode($message, true);

            if (!$data) {
                $this->warn('Event bukan JSON valid, skip.');
                return;
            }

            if (!isset($data['type']) || !isset($data['title'])) {
                $this->warn('Event tidak lengkap, skip.');
                return;
            }

            // Simpan dengan rotating log
            $log = ActivityLog::logWithRotation(
                $data['type'],
                $data['title'],
                $data['description'] ?? null,
                $data['severity'] ?? 'info',
                $data['icon'] ?? null,
                $data['event'] ?? null
            );

            $this->info("[EVENT] {$log->type}/{$log->severity}: {$log->title}");
        } catch (\Exception $e) {
            $this->error('Gagal simpan event: ' . $e->getMessage());
        }
    }

    /**
     * Publish jadwal dari DB ke MQTT
     */
    private function publishSchedules()
    {
        $schedules = Schedule::where('enabled', true)
            ->orderBy('time', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'hour' => (int) substr($s->time, 0, 2),
                    'minute' => (int) substr($s->time, 3, 2),
                    'duration' => $s->duration,
                ];
            })
            ->values();

        $payload = json_encode([
            'action' => 'set_schedules',
            'schedules' => $schedules,
        ]);

        try {
            $clientId = 'laravel-schedule-' . rand(1000, 9999);
            $mqtt = new MqttClient($this->broker, $this->port, $clientId);

            $settings = (new ConnectionSettings)
                ->setKeepAliveInterval(60)
                ->setConnectTimeout(5);

            $mqtt->connect($settings, true);
            $mqtt->publish('greenhouse/schedule', $payload, 0);
            $mqtt->disconnect();

            $this->info("Jadwal dipublish: {$schedules->count()} jadwal");
        } catch (\Exception $e) {
            $this->error('Gagal publish jadwal: ' . $e->getMessage());
        }
    }
}