<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'time',
        'duration',
        'enabled',
        'days',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'duration' => 'integer',
    ];
}