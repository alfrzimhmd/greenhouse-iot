<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'suhu',
        'kelembapan',
        'gas',
        'gas_ppm',
        'cahaya',
        'cahaya_persen',
        'level_air',
        'jarak_air',
        'mode',
        'growlight',
        'exhaust',
        'pompa',
        'atap',
        'alarm',
    ];

    protected $casts = [
        'suhu' => 'float',
        'kelembapan' => 'float',
        'gas' => 'integer',
        'gas_ppm' => 'integer',
        'cahaya' => 'integer',
        'cahaya_persen' => 'integer',
        'level_air' => 'float',
        'jarak_air' => 'float',
        'growlight' => 'boolean',
        'exhaust' => 'boolean',
        'pompa' => 'boolean',
        'atap' => 'boolean',
        'alarm' => 'boolean',
    ];
}