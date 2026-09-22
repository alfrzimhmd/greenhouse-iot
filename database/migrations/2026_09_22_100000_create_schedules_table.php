<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('Penyiraman');   // label jadwal
            $table->time('time');                                 // jam (06:00:00)
            $table->integer('duration')->default(10);             // durasi (detik)
            $table->boolean('enabled')->default(true);
            $table->string('days', 20)->default('daily');         // daily / weekdays / weekend
            $table->timestamps();
            
            $table->index('enabled');
            $table->index('time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};