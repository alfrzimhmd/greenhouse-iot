<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * GET /api/activity
     * Ambil activity log dengan filter & pagination
     */
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $limit = min($limit, 200);
        $limit = max($limit, 1);

        $type = $request->query('type', null);
        $severity = $request->query('severity', null);

        $query = ActivityLog::orderBy('id', 'desc');

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($severity && $severity !== 'all') {
            $query->where('severity', $severity);
        }

        $logs = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'count' => $logs->count(),
            'total' => ActivityLog::count(),
            'data' => $logs,
        ]);
    }

    /**
     * GET /api/activity/latest
     * Ambil activity terbaru sejak ID tertentu (untuk polling)
     */
    public function latest(Request $request)
    {
        $sinceId = (int) $request->query('since_id', 0);

        $logs = ActivityLog::where('id', '>', $sinceId)
            ->orderBy('id', 'asc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * GET /api/activity/stats
     * Statistik log
     */
    public function stats()
    {
        $total = ActivityLog::count();
        $byType = ActivityLog::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');
        $bySeverity = ActivityLog::selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_type' => $byType,
                'by_severity' => $bySeverity,
                'max_logs' => ActivityLog::MAX_LOGS,
            ],
        ]);
    }

    /**
     * DELETE /api/activity/clear
     * Hapus semua log (untuk testing)
     */
    public function clear()
    {
        $count = ActivityLog::count();
        ActivityLog::truncate();

        return response()->json([
            'success' => true,
            'message' => "{$count} log dihapus",
        ]);
    }
}