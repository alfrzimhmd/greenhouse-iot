<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    // ===== ROTATING LOG CONFIG =====
    const MAX_LOGS = 1000;      // Batas maksimum
    const KEEP_LOGS = 900;      // Sisakan 900 setelah cleanup

    protected $fillable = [
        'type',
        'event',
        'title',
        'description',
        'icon',
        'severity',
    ];

    /**
     * Log dengan rotating mechanism (FIFO)
     */
    public static function logWithRotation(
        string $type,
        string $title,
        ?string $desc = null,
        string $severity = 'info',
        ?string $icon = null,
        ?string $event = null
    ): self {
        // Simpan log baru
        $log = self::create([
            'type' => $type,
            'event' => $event,
            'title' => $title,
            'description' => $desc,
            'severity' => $severity,
            'icon' => $icon,
        ]);

        // Cek total & cleanup kalau perlu
        $total = self::count();
        
        if ($total > self::MAX_LOGS) {
            $toDelete = $total - self::KEEP_LOGS;
            
            $oldestIds = self::orderBy('id', 'asc')
                ->limit($toDelete)
                ->pluck('id');
            
            self::whereIn('id', $oldestIds)->delete();
            
            \Log::info("Rotating log: hapus {$toDelete} log lama, sisa " . self::count());
        }

        return $log;
    }

    /**
     * Helper backward-compatible
     */
    public static function log(
        string $type,
        string $title,
        ?string $desc = null,
        string $severity = 'info',
        ?string $icon = null
    ): self {
        return self::logWithRotation($type, $title, $desc, $severity, $icon);
    }
}