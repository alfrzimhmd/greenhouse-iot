<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50);       // growlight, exhaust, pompa, atap, mode
            $table->string('value', 50);        // on, off, auto, manual
            $table->string('source', 20)->default('web'); // web, mqtt, button
            $table->timestamps();
            
            $table->index('created_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_logs');
    }
};