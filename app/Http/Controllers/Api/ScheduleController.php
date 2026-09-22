<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Schedule;
use Illuminate\Http\Request;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class ScheduleController extends Controller
{
    private $broker = 'broker.hivemq.com';
    private $port = 1883;
    private $topicSchedule = 'greenhouse/schedule';

    /**
     * GET /api/schedule
     * Ambil semua jadwal
     */
    public function index()
    {
        $schedules = Schedule::orderBy('time', 'asc')->get();

        return response()->json([
            'success' => true,
            'count' => $schedules->count(),
            'data' => $schedules,
        ]);
    }

    /**
     * POST /api/schedule
     * Tambah jadwal baru + publish ke ESP32
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:5|max:300',
            'enabled' => 'boolean',
        ]);

        // Cek maksimal 5 jadwal
        if (Schedule::count() >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 jadwal',
            ], 422);
        }

        $schedule = Schedule::create([
            'name' => $validated['name'],
            'time' => $validated['time'] . ':00',
            'duration' => $validated['duration'],
            'enabled' => $validated['enabled'] ?? true,
        ]);

        // Log activity
        ActivityLog::log(
            'schedule',
            "Jadwal ditambahkan: {$schedule->name}",
            "Pukul {$validated['time']} selama {$schedule->duration} detik",
            'success',
            'calendar-plus'
        );

        // Publish semua jadwal ke ESP32
        $this->publishSchedules();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal ditambahkan',
            'data' => $schedule,
        ]);
    }

    /**
     * PUT /api/schedule/{id}
     * Update jadwal
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'time' => 'sometimes|date_format:H:i',
            'duration' => 'sometimes|integer|min:5|max:300',
            'enabled' => 'sometimes|boolean',
        ]);

        if (isset($validated['time'])) {
            $validated['time'] .= ':00';
        }

        $schedule->update($validated);

        ActivityLog::log(
            'schedule',
            "Jadwal diupdate: {$schedule->name}",
            "Pukul " . substr($schedule->time, 0, 5) . " selama {$schedule->duration} detik",
            'info',
            'calendar-edit'
        );

        $this->publishSchedules();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal diupdate',
            'data' => $schedule,
        ]);
    }

    /**
     * DELETE /api/schedule/{id}
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $name = $schedule->name;
        $schedule->delete();

        ActivityLog::log(
            'schedule',
            "Jadwal dihapus: {$name}",
            null,
            'warning',
            'calendar-remove'
        );

        $this->publishSchedules();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal dihapus',
        ]);
    }

    /**
     * POST /api/schedule/sync
     * Trigger manual sync jadwal ke ESP32
     */
    public function sync()
    {
        $this->publishSchedules();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal disinkronkan ke ESP32',
        ]);
    }

    /**
     * Publish semua jadwal ke MQTT
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
            $mqtt->publish($this->topicSchedule, $payload, 0);
            $mqtt->disconnect();

            return true;
        } catch (\Exception $e) {
            \Log::error('MQTT publish schedule error: ' . $e->getMessage());
            return false;
        }
    }
}