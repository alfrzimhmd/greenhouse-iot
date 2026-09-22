<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ControlLog;
use Illuminate\Http\Request;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class ControlController extends Controller
{
    private $broker = 'broker.hivemq.com';
    private $port = 1883;
    private $topicControl = 'greenhouse/control';

    public function send(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:growlight,exhaust,pompa,atap,mode',
            'value' => 'required|string',
        ]);

        try {
            $this->publishMqtt($validated['action'], $validated['value']);

            $log = ControlLog::create([
                'action' => $validated['action'],
                'value' => $validated['value'],
                'source' => 'web',
            ]);

            // ===== LOG ACTIVITY =====
            $this->logActivity($validated['action'], $validated['value']);

            return response()->json([
                'success' => true,
                'message' => 'Perintah terkirim',
                'data' => $log,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal kirim perintah: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $limit = min($limit, 500);
        $limit = max($limit, 1);

        $logs = ControlLog::orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $logs->count(),
            'data' => $logs,
        ]);
    }

    /**
     * Publish ke MQTT
     */
    private function publishMqtt(string $action, string $value)
    {
        $clientId = 'laravel-publisher-' . rand(1000, 9999);
        $mqtt = new MqttClient($this->broker, $this->port, $clientId);

        $settings = (new ConnectionSettings)
            ->setKeepAliveInterval(60)
            ->setConnectTimeout(5);

        $mqtt->connect($settings, true);

        $payload = json_encode([
            'action' => $action,
            'value' => $value,
        ]);

        $mqtt->publish($this->topicControl, $payload, 0);
        $mqtt->disconnect();
    }

    /**
     * Log activity ke DB
     */
    private function logActivity(string $action, string $value)
    {
        $actionLabels = [
            'growlight' => 'Grow Light',
            'exhaust'   => 'Exhaust Fan',
            'pompa'     => 'Pompa Air',
            'atap'      => 'Atap',
            'mode'      => 'Mode Sistem',
        ];

        $label = $actionLabels[$action] ?? $action;

        // Tentukan title, description, severity, icon
        if ($action === 'mode') {
            $isAuto = ($value === 'auto');
            ActivityLog::log(
                'control',
                "Mode diubah ke " . ($isAuto ? 'OTOMATIS' : 'MANUAL'),
                'Dikontrol dari dashboard',
                'info',
                'settings'
            );
            return;
        }

        // Untuk aktuator on/off
        $isOn = ($value === 'on');

        $title = "{$label} " . ($isOn ? 'dinyalakan' : 'dimatikan');
        $description = $isOn
            ? "Dikontrol manual dari dashboard"
            : "Dimatikan dari dashboard";

        $severity = $isOn ? 'success' : 'info';

        ActivityLog::log(
            'control',
            $title,
            $description,
            $severity,
            $action
        );
    }
}