<?php

namespace App\Console\Commands;

use App\Models\SensorLog;
use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe ke MQTT broker dan simpan data sensor ke database';

    public function handle()
    {
        $this->info('Memulai MQTT Subscriber...');

        $broker = 'broker.hivemq.com';
        $port = 1883;
        $clientId = 'laravel-greenhouse-' . rand(1000, 9999);
        $topic = 'greenhouse/sensor';

        $mqtt = new MqttClient($broker, $port, $clientId);
        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(60)
            ->setLastWillTopic('greenhouse/lastwill')
            ->setLastWillMessage('client disconnected')
            ->setLastWillQualityOfService(1);

        try {
            $mqtt->connect($settings, true);
            $this->info("Terhubung ke broker: {$broker}:{$port}");
            $this->info("Subscribe ke topic: {$topic}");
            $this->info("Menunggu data... (Ctrl+C untuk stop)");
            $this->newLine();

            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->handleMessage($topic, $message);
            }, 0);

            $mqtt->loop(true);
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function handleMessage(string $topic, string $message)
    {
        try {
            $data = json_decode($message, true);

            if (!$data) {
                $this->warn('Data bukan JSON valid, skip.');
                return;
            }

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
            $this->error('Gagal simpan: ' . $e->getMessage());
        }
    }
}