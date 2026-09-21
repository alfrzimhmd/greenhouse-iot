@extends('layouts.app')

@section('title', 'Greenhouse Dashboard V2')

@section('content')
<div class="container mx-auto p-6 max-w-7xl" x-data="dashboard()" x-cloak>
    
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center mb-8 gap-3">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-white text-3xl font-extrabold tracking-tight drop-shadow-lg">
                    Greenhouse V2
                </h1>
                <p class="text-white/80 text-sm mt-0.5 font-medium">Smart Monitoring & Control System</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Alarm Badge -->
            <div x-show="sensor.alarm" 
                 x-transition
                 class="flex items-center gap-2 bg-red-500/90 backdrop-blur-md px-4 py-2 rounded-full border border-red-300/50 shadow-lg animate-pulse">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-white font-bold text-sm">ALARM AKTIF</span>
            </div>
            
            <!-- Connection Status -->
            <div class="flex items-center gap-3 bg-white/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/30">
                <span :class="connected ? 'bg-green-400' : 'bg-red-400'" 
                      class="w-2.5 h-2.5 rounded-full pulse-dot"></span>
                <span class="text-white font-semibold text-sm" x-text="connected ? 'Connected' : 'Disconnected'"></span>
            </div>
        </div>
    </div>

    <!-- Sensor Cards (5 cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        
        <!-- Suhu -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-red-400/20 to-transparent rounded-full -mr-8 -mt-8"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Suhu</span>
                    <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold text-gray-800 stat-value" 
                          x-text="sensor.suhu !== undefined ? parseFloat(sensor.suhu).toFixed(1) : '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">°C</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-red-400 to-red-600 rounded-full transition-all duration-500"
                         :style="`width: ${Math.min((sensor.suhu || 0) / 50 * 100, 100)}%`"></div>
                </div>
            </div>
        </div>

        <!-- Kelembapan -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-400/20 to-transparent rounded-full -mr-8 -mt-8"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Kelembapan</span>
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold text-gray-800 stat-value" 
                          x-text="sensor.kelembapan !== undefined ? parseFloat(sensor.kelembapan).toFixed(0) : '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">%</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-500"
                         :style="`width: ${Math.min(sensor.kelembapan || 0, 100)}%`"></div>
                </div>
            </div>
        </div>

        <!-- Gas / CO2 -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-purple-400/20 to-transparent rounded-full -mr-8 -mt-8"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Gas / CO₂</span>
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold text-gray-800 stat-value" 
                          x-text="sensor.gas_ppm ?? '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">ppm</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="{
                             'bg-gradient-to-r from-green-400 to-green-600': (sensor.gas_ppm || 0) <= 500,
                             'bg-gradient-to-r from-yellow-400 to-yellow-600': (sensor.gas_ppm || 0) > 500 && (sensor.gas_ppm || 0) <= 750,
                             'bg-gradient-to-r from-red-400 to-red-600': (sensor.gas_ppm || 0) > 750
                         }"
                         :style="`width: ${Math.min((sensor.gas_ppm || 0) / 2000 * 100, 100)}%`"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1" x-text="
                    (sensor.gas_ppm || 0) > 750 ? 'BAHAYA' : 
                    (sensor.gas_ppm || 0) > 500 ? 'TINGGI' : 'NORMAL'
                "></p>
            </div>
        </div>

        <!-- Cahaya -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-yellow-400/20 to-transparent rounded-full -mr-8 -mt-8"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Cahaya</span>
                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold text-gray-800 stat-value" 
                          x-text="sensor.cahaya_persen ?? '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">%</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full transition-all duration-500"
                         :style="`width: ${Math.min(sensor.cahaya_persen || 0, 100)}%`"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1" x-text="(sensor.cahaya_persen || 0) < 30 ? 'GELAP' : 'TERANG'"></p>
            </div>
        </div>

        <!-- Level Air -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-cyan-400/20 to-transparent rounded-full -mr-8 -mt-8"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Level Air</span>
                    <div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-extrabold text-gray-800 stat-value" 
                          x-text="sensor.level_air !== undefined ? parseFloat(sensor.level_air).toFixed(0) : '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">%</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="{
                             'bg-gradient-to-r from-green-400 to-green-600': (sensor.level_air || 0) > 50,
                             'bg-gradient-to-r from-yellow-400 to-yellow-600': (sensor.level_air || 0) > 20 && (sensor.level_air || 0) <= 50,
                             'bg-gradient-to-r from-red-400 to-red-600': (sensor.level_air || 0) <= 20
                         }"
                         :style="`width: ${Math.min(sensor.level_air || 0, 100)}%`"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1" x-text="
                    (sensor.level_air || 0) <= 10 ? 'KRITIS' : 
                    (sensor.level_air || 0) <= 20 ? 'RENDAH' : 'NORMAL'
                "></p>
            </div>
        </div>
    </div>

    <!-- Mode + Control Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        
        <!-- Mode Selector -->
        <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl">
            <h2 class="text-gray-800 font-bold text-lg mb-4 flex items-center gap-2">
                <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
                Mode Kontrol
            </h2>
            <div class="space-y-3">
                <button @click="setMode('auto')" 
                        :class="sensor.mode === 'auto' 
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/30' 
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="w-full py-3 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    OTOMATIS
                </button>
                <button @click="setMode('manual')" 
                        :class="sensor.mode === 'manual' 
                            ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/30' 
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="w-full py-3 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0m3 0a1.5 1.5 0 01-3 0m9-6v6.5m0 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m6-6.5a1.5 1.5 0 013 0v6.5m-3 0a1.5 1.5 0 003 0M3 19h18"/>
                    </svg>
                    MANUAL
                </button>
            </div>
        </div>

        <!-- Control Aktuator -->
        <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl lg:col-span-2">
            <h2 class="text-gray-800 font-bold text-lg mb-4 flex items-center gap-2">
                <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
                Kontrol Aktuator
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <!-- Grow Light -->
                <button @click="toggleControl('growlight')" 
                        :class="sensor.growlight 
                            ? 'bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-lg shadow-yellow-500/30' 
                            : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span class="text-xs">Grow Light</span>
                    <span class="text-xs opacity-90" x-text="sensor.growlight ? 'ON' : 'OFF'"></span>
                </button>
                
                <!-- Exhaust -->
                <button @click="toggleControl('exhaust')" 
                        :class="sensor.exhaust 
                            ? 'bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg shadow-blue-500/30' 
                            : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83M12 14a2 2 0 100-4 2 2 0 000 4z"/>
                    </svg>
                    <span class="text-xs">Exhaust</span>
                    <span class="text-xs opacity-90" x-text="sensor.exhaust ? 'ON' : 'OFF'"></span>
                </button>
                
                <!-- Pompa -->
                <button @click="toggleControl('pompa')" 
                        :class="sensor.pompa 
                            ? 'bg-gradient-to-br from-cyan-400 to-cyan-600 shadow-lg shadow-cyan-500/30' 
                            : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="text-xs">Pompa</span>
                    <span class="text-xs opacity-90" x-text="sensor.pompa ? 'ON' : 'OFF'"></span>
                </button>
                
                <!-- Atap -->
                <button @click="toggleControl('atap')" 
                        :class="sensor.atap 
                            ? 'bg-gradient-to-br from-purple-400 to-purple-600 shadow-lg shadow-purple-500/30' 
                            : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs">Atap</span>
                    <span class="text-xs opacity-90" x-text="sensor.atap ? 'BUKA' : 'TUTUP'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Grafik History -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-gray-800 font-bold text-lg flex items-center gap-2">
                <span class="w-1 h-6 bg-purple-500 rounded-full"></span>
                History Sensor
            </h2>
            <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Auto-refresh 2s
            </span>
        </div>
        <div id="historyChart"></div>
    </div>

    <!-- Footer -->
    <div class="text-center text-white/70 text-xs mb-4 flex items-center justify-center gap-2">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Last update: <span x-text="lastUpdate" class="font-semibold text-white"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        sensor: {},
        connected: false,
        lastUpdate: '-',
        chart: null,
        pollingInterval: null,

        async init() {
            this.initChart();
            await this.loadLatest();
            await this.loadHistory();
            this.startPolling();
        },

        async loadLatest() {
            try {
                const res = await fetch('/api/sensor/latest');
                const json = await res.json();
                if (json.success) {
                    this.sensor = json.data;
                    this.connected = true;
                    this.lastUpdate = new Date().toLocaleTimeString('id-ID');
                }
            } catch (e) {
                this.connected = false;
                console.error('Latest error:', e);
            }
        },

        async loadHistory() {
            try {
                const res = await fetch('/api/sensor/history?limit=20');
                const json = await res.json();
                if (json.success && json.data && json.data.length > 0) {
                    this.updateChart(json.data);
                }
            } catch (e) {
                console.error('History error:', e);
            }
        },

        initChart() {
            const options = {
                chart: {
                    type: 'area',
                    height: 350,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        dynamicAnimation: { enabled: true, speed: 350 }
                    },
                    background: 'transparent'
                },
                series: [
                    { name: 'Suhu (°C)', data: [] },
                    { name: 'Kelembapan (%)', data: [] },
                    { name: 'Gas (ppm)', data: [] },
                    { name: 'Level Air (%)', data: [] }
                ],
                colors: ['#ef4444', '#3b82f6', '#a855f7', '#06b6d4'],
                dataLabels: { enabled: false },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: [],
                    labels: {
                        style: { fontSize: '11px', fontWeight: 500, colors: '#9ca3af' }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '11px', fontWeight: 500, colors: '#9ca3af' },
                        formatter: (val) => val.toFixed(0)
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    fontWeight: 600,
                    markers: { radius: 6 },
                    itemMargin: { horizontal: 10 }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: 'light',
                    style: { fontSize: '12px' }
                },
                grid: {
                    borderColor: '#e5e7eb',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    padding: { top: 0, right: 0, bottom: 0, left: 0 }
                }
            };

            this.chart = new ApexCharts(document.querySelector('#historyChart'), options);
            this.chart.render();
        },

        updateChart(data) {
            if (!this.chart) return;

            const labels = data.map(d => 
                new Date(d.created_at).toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                })
            );

            this.chart.updateOptions({
                xaxis: { categories: labels }
            });

            this.chart.updateSeries([
                { name: 'Suhu (°C)', data: data.map(d => parseFloat(d.suhu || 0)) },
                { name: 'Kelembapan (%)', data: data.map(d => parseFloat(d.kelembapan || 0)) },
                { name: 'Gas (ppm)', data: data.map(d => parseFloat(d.gas_ppm || 0)) },
                { name: 'Level Air (%)', data: data.map(d => parseFloat(d.level_air || 0)) }
            ]);
        },

        async setMode(mode) {
            this.sensor.mode = mode;
            await this.sendControl('mode', mode);
        },

        async toggleControl(action) {
            const newValue = this.sensor[action] ? 'off' : 'on';
            this.sensor[action] = (newValue === 'on');
            await this.sendControl(action, newValue);
        },

        async sendControl(action, value) {
            try {
                const res = await fetch('/api/control', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ action, value })
                });
                const json = await res.json();
                if (json.success) {
                    console.log('Control sent:', action, value);
                    setTimeout(() => this.loadLatest(), 500);
                    setTimeout(() => this.loadLatest(), 1500);
                    setTimeout(() => this.loadLatest(), 2500);
                }
            } catch (e) {
                console.error('Control error:', e);
            }
        },

        startPolling() {
            this.pollingInterval = setInterval(() => {
                this.loadLatest();
                this.loadHistory();
            }, 2000);
        }
    }
}
</script>
@endpush