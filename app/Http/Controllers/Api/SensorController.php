<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorLog;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function latest()
    {
        $sensor = SensorLog::orderBy('id', 'desc')->first();

        if (!$sensor) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data sensor',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sensor,
        ]);
    }

    public function history(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $limit = min($limit, 500);
        $limit = max($limit, 1);

        $sensors = SensorLog::orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $sensors->count(),
            'data' => $sensors->reverse()->values(),
        ]);
    }

    public function stats()
    {
        $stats = SensorLog::selectRaw('
            COUNT(*) as total,
            AVG(suhu) as avg_suhu,
            MIN(suhu) as min_suhu,
            MAX(suhu) as max_suhu,
            AVG(kelembapan) as avg_kelembapan,
            AVG(gas) as avg_gas,
            AVG(gas_ppm) as avg_gas_ppm,
            AVG(cahaya_persen) as avg_cahaya_persen,
            AVG(level_air) as avg_level_air,
            SUM(alarm) as total_alarm
        ')->first();

        if ($stats) {
            $stats->avg_suhu = $stats->avg_suhu ? (float) $stats->avg_suhu : 0;
            $stats->min_suhu = $stats->min_suhu ? (float) $stats->min_suhu : 0;
            $stats->max_suhu = $stats->max_suhu ? (float) $stats->max_suhu : 0;
            $stats->avg_kelembapan = $stats->avg_kelembapan ? (float) $stats->avg_kelembapan : 0;
            $stats->avg_gas = $stats->avg_gas ? (float) $stats->avg_gas : 0;
            $stats->avg_gas_ppm = $stats->avg_gas_ppm ? (float) $stats->avg_gas_ppm : 0;
            $stats->avg_cahaya_persen = $stats->avg_cahaya_persen ? (float) $stats->avg_cahaya_persen : 0;
            $stats->avg_level_air = $stats->avg_level_air ? (float) $stats->avg_level_air : 0;
            $stats->total_alarm = (int) $stats->total_alarm;
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}