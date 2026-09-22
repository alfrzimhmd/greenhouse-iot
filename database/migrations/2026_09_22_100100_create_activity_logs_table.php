<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);        // sensor, control, alarm, schedule, system
            $table->string('title', 100);       // "Pompa menyala"
            $table->text('description')->nullable(); // detail
            $table->string('icon', 30)->nullable();  // icon name: pump, light, warning
            $table->string('severity', 20)->default('info'); // info, success, warning, danger
            $table->timestamps();
            
            $table->index('type');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};