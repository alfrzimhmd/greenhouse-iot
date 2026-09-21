<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id();
            
            // ===== SENSOR =====
            $table->float('suhu')->default(0);              // °C
            $table->float('kelembapan')->default(0);        // % RH
            $table->integer('gas')->default(0);             // ADC MQ-135
            $table->integer('gas_ppm')->default(0);         // Estimasi ppm
            $table->integer('cahaya')->default(0);          // ADC LDR
            $table->integer('cahaya_persen')->default(0);   // % terang
            $table->float('level_air')->default(100);       // % tandon
            $table->float('jarak_air')->default(5);         // cm (HC-SR04)
            
            // ===== MODE =====
            $table->string('mode', 10)->default('auto');    // auto / manual
            
            // ===== AKTUATOR =====
            $table->boolean('growlight')->default(false);
            $table->boolean('exhaust')->default(false);
            $table->boolean('pompa')->default(false);
            $table->boolean('atap')->default(false);
            
            // ===== STATUS =====
            $table->boolean('alarm')->default(false);
            
            $table->timestamps();
            
            // Index untuk query cepat
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};