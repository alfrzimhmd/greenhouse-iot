@extends('layouts.app')

@section('title', 'Greenhouse Dashboard V3')

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
                <h1 class="text-white text-3xl font-extrabold tracking-tight drop-shadow-lg">Greenhouse V3</h1>
                <p class="text-white/80 text-sm mt-0.5 font-medium">Smart Monitoring & Control System</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div x-show="isSending" x-transition
                 class="flex items-center gap-2 bg-blue-500/90 backdrop-blur-md px-4 py-2 rounded-full border border-blue-300/50 shadow-lg">
                <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-white font-bold text-sm">Mengirim...</span>
            </div>
            
            <div x-show="sensor.alarm" x-transition
                 class="flex items-center gap-2 bg-red-500/90 backdrop-blur-md px-4 py-2 rounded-full border border-red-300/50 shadow-lg animate-pulse">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-white font-bold text-sm">ALARM AKTIF</span>
            </div>
            
            <div class="flex items-center gap-3 bg-white/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/30">
                <span :class="connected ? 'bg-green-400' : 'bg-red-400'" class="w-2.5 h-2.5 rounded-full pulse-dot"></span>
                <span class="text-white font-semibold text-sm" x-text="connected ? 'Connected' : 'Disconnected'"></span>
            </div>
        </div>
    </div>

    <!-- Sensor Cards (5 cards) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Suhu -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-red-400/20 to-transparent rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-500"></div>
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
                    <span class="text-3xl font-extrabold text-gray-800" x-text="sensor.suhu !== undefined ? parseFloat(sensor.suhu).toFixed(1) : '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">°C</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-red-400 to-red-600 rounded-full transition-all duration-500"
                         :style="`width: ${Math.min((sensor.suhu || 0) / 50 * 100, 100)}%`"></div>
                </div>
            </div>
        </div>

        <!-- Kelembapan -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-400/20 to-transparent rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-500"></div>
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
                    <span class="text-3xl font-extrabold text-gray-800" x-text="sensor.kelembapan !== undefined ? parseFloat(sensor.kelembapan).toFixed(0) : '--'"></span>
                    <span class="text-sm text-gray-400 font-semibold">%</span>
                </div>
                <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-500"
                         :style="`width: ${Math.min(sensor.kelembapan || 0, 100)}%`"></div>
                </div>
            </div>
        </div>

        <!-- Gas -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-purple-400/20 to-transparent rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-500"></div>
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
                    <span class="text-3xl font-extrabold text-gray-800" x-text="sensor.gas_ppm ?? '--'"></span>
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
                <p class="text-xs text-gray-400 mt-1" x-text="(sensor.gas_ppm || 0) > 750 ? 'BAHAYA' : (sensor.gas_ppm || 0) > 500 ? 'TINGGI' : 'NORMAL'"></p>
            </div>
        </div>

        <!-- Cahaya -->
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-yellow-400/20 to-transparent rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-500"></div>
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
                    <span class="text-3xl font-extrabold text-gray-800" x-text="sensor.cahaya_persen ?? '--'"></span>
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
        <div class="glass rounded-2xl p-5 card-hover border border-white/50 shadow-xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-cyan-400/20 to-transparent rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-500"></div>
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
                    <span class="text-3xl font-extrabold text-gray-800" x-text="sensor.level_air !== undefined ? parseFloat(sensor.level_air).toFixed(0) : '--'"></span>
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
                <p class="text-xs text-gray-400 mt-1" x-text="(sensor.level_air || 0) <= 10 ? 'KRITIS' : (sensor.level_air || 0) <= 20 ? 'RENDAH' : 'NORMAL'"></p>
            </div>
        </div>
    </div>

    <!-- Mode + Control -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl">
            <h2 class="text-gray-800 font-bold text-lg mb-4 flex items-center gap-2">
                <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
                Mode Kontrol
            </h2>
            <div class="space-y-3">
                <button @click="setMode('auto')"
                        :class="sensor.mode === 'auto' ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="w-full py-3 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    OTOMATIS
                </button>
                <button @click="setMode('manual')"
                        :class="sensor.mode === 'manual' ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="w-full py-3 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0m3 0a1.5 1.5 0 01-3 0m9-6v6.5m0 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m6-6.5a1.5 1.5 0 013 0v6.5m-3 0a1.5 1.5 0 003 0M3 19h18"/>
                    </svg>
                    MANUAL
                </button>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl lg:col-span-2">
            <h2 class="text-gray-800 font-bold text-lg mb-4 flex items-center gap-2">
                <span class="w-1 h-6 bg-blue-500 rounded-full"></span>
                Kontrol Aktuator
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button @click="toggleControl('growlight')"
                        :class="sensor.growlight ? 'bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-lg shadow-yellow-500/30' : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span class="text-xs">Grow Light</span>
                    <span class="text-xs opacity-90" x-text="sensor.growlight ? 'ON' : 'OFF'"></span>
                </button>
                
                <button @click="toggleControl('exhaust')"
                        :class="sensor.exhaust ? 'bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg shadow-blue-500/30' : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83M12 14a2 2 0 100-4 2 2 0 000 4z"/>
                    </svg>
                    <span class="text-xs">Exhaust</span>
                    <span class="text-xs opacity-90" x-text="sensor.exhaust ? 'ON' : 'OFF'"></span>
                </button>
                
                <button @click="toggleControl('pompa')"
                        :class="sensor.pompa ? 'bg-gradient-to-br from-cyan-400 to-cyan-600 shadow-lg shadow-cyan-500/30' : 'bg-gradient-to-br from-gray-300 to-gray-400'"
                        class="py-4 rounded-xl text-white font-bold transition-all duration-300 hover:scale-105 flex flex-col items-center gap-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span class="text-xs">Pompa</span>
                    <span class="text-xs opacity-90" x-text="sensor.pompa ? 'ON' : 'OFF'"></span>
                </button>
                
                <button @click="toggleControl('atap')"
                        :class="sensor.atap ? 'bg-gradient-to-br from-purple-400 to-purple-600 shadow-lg shadow-purple-500/30' : 'bg-gradient-to-br from-gray-300 to-gray-400'"
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

    <!-- ============================================= -->
    <!-- CARD GRAFIK HISTORY - MULTI SMALL BAR CHARTS  -->
    <!-- ============================================= -->
    <div class="glass rounded-2xl p-6 border border-white/50 shadow-xl mb-6">
        <div class="flex flex-wrap justify-between items-center mb-5 gap-3">
            <div class="flex items-center gap-2">
                <span class="w-1 h-6 bg-purple-500 rounded-full"></span>
                <h2 class="text-gray-800 font-bold text-lg">History Sensor</h2>
                <span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded-full font-semibold">
                    Bar Chart
                </span>
            </div>
            <div class="flex items-center gap-2">
                <select x-model="chartLimit" @change="loadHistory()"
                        class="text-xs bg-gray-100 border-0 rounded-lg px-3 py-1.5 text-gray-700 font-medium">
                    <option value="20">20 data</option>
                    <option value="40">40 data</option>
                    <option value="60">60 data</option>
                    <option value="100">100 data</option>
                </select>
                <span class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Auto 2s</span>
            </div>
        </div>

        <!-- Info -->
        <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><b>Data terbaru di kiri</b> • Data lama di kanan. Y-axis tetap diam saat di-scroll. Warna bar berubah otomatis sesuai status.</span>
        </div>

        <!-- Grid 4 Mini Bar Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            
            <!-- Chart 1: Suhu -->
            <div class="bg-gray-50/70 rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-white/60">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background: #ef4444;"></span>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Suhu</span>
                        <span class="text-xs text-gray-400">(°C)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Max 50</span>
                        <span class="text-sm font-extrabold text-red-600 tabular-nums"
                              x-text="sensor.suhu !== undefined ? parseFloat(sensor.suhu).toFixed(1) : '--'"></span>
                    </div>
                </div>
                <!-- Chart wrapper dengan Y-axis fixed + scroll area -->
                <div class="chart-wrapper">
                    <!-- Y-axis FIXED (tidak ikut scroll) -->
                    <div class="chart-yaxis">
                        <span>50</span>
                        <span>40</span>
                        <span>30</span>
                        <span>20</span>
                        <span>10</span>
                        <span>0</span>
                    </div>
                    <div class="chart-yaxis-border"></div>
                    <!-- Area scroll: hanya berisi SVG bar -->
                    <div class="chart-scroll">
                        <div id="chartSuhu" style="width: 1100px; height: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Kelembapan -->
            <div class="bg-gray-50/70 rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-white/60">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background: #3b82f6;"></span>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Kelembapan</span>
                        <span class="text-xs text-gray-400">(%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Max 100</span>
                        <span class="text-sm font-extrabold text-blue-600 tabular-nums"
                              x-text="sensor.kelembapan !== undefined ? parseFloat(sensor.kelembapan).toFixed(0) : '--'"></span>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-yaxis">
                        <span>100</span>
                        <span>80</span>
                        <span>60</span>
                        <span>40</span>
                        <span>20</span>
                        <span>0</span>
                    </div>
                    <div class="chart-yaxis-border"></div>
                    <div class="chart-scroll">
                        <div id="chartRH" style="width: 1100px; height: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Chart 3: Gas -->
            <div class="bg-gray-50/70 rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-white/60">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background: #a855f7;"></span>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Gas / CO₂</span>
                        <span class="text-xs text-gray-400">(ppm)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Max 2000</span>
                        <span class="text-sm font-extrabold text-purple-600 tabular-nums"
                              x-text="sensor.gas_ppm ?? '--'"></span>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-yaxis">
                        <span>2k</span>
                        <span>1.6k</span>
                        <span>1.2k</span>
                        <span>800</span>
                        <span>400</span>
                        <span>0</span>
                    </div>
                    <div class="chart-yaxis-border"></div>
                    <div class="chart-scroll">
                        <div id="chartGas" style="width: 1100px; height: 100%;"></div>
                    </div>
                </div>
            </div>

            <!-- Chart 4: Level Air -->
            <div class="bg-gray-50/70 rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-white/60">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background: #06b6d4;"></span>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Level Air</span>
                        <span class="text-xs text-gray-400">(%)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Max 100</span>
                        <span class="text-sm font-extrabold text-cyan-600 tabular-nums"
                              x-text="sensor.level_air !== undefined ? parseFloat(sensor.level_air).toFixed(0) : '--'"></span>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <div class="chart-yaxis">
                        <span>100</span>
                        <span>80</span>
                        <span>60</span>
                        <span>40</span>
                        <span>20</span>
                        <span>0</span>
                    </div>
                    <div class="chart-yaxis-border"></div>
                    <div class="chart-scroll">
                        <div id="chartAir" style="width: 1100px; height: 100%;"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- CARD JADWAL POMPA -->
    <div class="glass rounded-2xl border border-white/50 shadow-xl mb-6 overflow-hidden">
        <div class="flex flex-wrap justify-between items-center p-6 border-b border-gray-100 gap-3">
            <div class="flex items-center gap-2">
                <span class="w-1 h-6 bg-emerald-500 rounded-full"></span>
                <h2 class="text-gray-800 font-bold text-lg">Jadwal Pompa</h2>
                <span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full font-semibold"
                      x-text="schedules.length + ' / 5'"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="syncSchedules()"
                        class="text-sm bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg font-semibold hover:bg-gray-200">
                    Sync
                </button>
                <button @click="openScheduleForm()"
                        :disabled="schedules.length >= 5"
                        :class="schedules.length >= 5 ? 'bg-gray-300 cursor-not-allowed' : 'bg-emerald-500 hover:bg-emerald-600'"
                        class="text-sm text-white px-3 py-1.5 rounded-lg font-semibold">
                    + Tambah Jadwal
                </button>
            </div>
        </div>
        
        <div x-show="showScheduleForm" x-transition class="p-6 bg-emerald-50/50 border-b border-emerald-100">
            <h3 class="text-sm font-bold text-gray-800 mb-3" x-text="scheduleForm.id ? 'Edit Jadwal' : 'Jadwal Baru'"></h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Nama</label>
                    <input type="text" x-model="scheduleForm.name" placeholder="Siram Pagi"
                           class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Jam : Menit</label>
                    <div class="flex gap-1 items-center">
                        <input type="number" x-model="scheduleForm.hour"
                               min="0" max="23" placeholder="06"
                               class="flex-1 text-sm bg-white border border-gray-200 rounded-lg px-2 py-2 text-center">
                        <span class="text-gray-400 font-bold">:</span>
                        <input type="number" x-model="scheduleForm.minute"
                               min="0" max="59" placeholder="00"
                               class="flex-1 text-sm bg-white border border-gray-200 rounded-lg px-2 py-2 text-center">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 block mb-1">Durasi (detik)</label>
                    <input type="number" x-model="scheduleForm.duration" min="5" max="300"
                           class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" x-model="scheduleForm.enabled"
                               class="w-4 h-4 text-emerald-500 rounded">
                        <span class="font-semibold text-gray-700">Aktifkan</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button @click="saveSchedule()"
                        class="text-sm bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold">
                    <span x-text="scheduleForm.id ? 'Update' : 'Simpan'"></span>
                </button>
                <button @click="closeScheduleForm()"
                        class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold">
                    Batal
                </button>
            </div>
        </div>
        
        <div class="p-4">
            <template x-if="schedules.length === 0">
                <div class="text-center py-8">
                    <p class="text-gray-400 text-sm">Belum ada jadwal pompa</p>
                </div>
            </template>
            
            <div class="space-y-2">
                <template x-for="sched in schedules" :key="sched.id">
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border transition-all"
                         :class="sched.enabled ? 'bg-emerald-50/50 border-emerald-100' : 'bg-gray-50 border-gray-100 opacity-60'">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 :class="sched.enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate" x-text="sched.name"></p>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                    <span x-text="sched.time.substring(0, 5)"></span>
                                    <span>•</span>
                                    <span x-text="sched.duration + 's'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="toggleSchedule(sched)"
                                    :class="sched.enabled ? 'text-emerald-500' : 'text-gray-400'"
                                    class="p-2 hover:bg-white rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path x-show="sched.enabled" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    <path x-show="!sched.enabled" stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                            <button @click="editSchedule(sched)" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="deleteSchedule(sched)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- CARD ACTIVITY FEED -->
    <div class="glass rounded-2xl border border-white/50 shadow-xl mb-6 overflow-hidden">
        <div class="flex flex-wrap justify-between items-center p-6 border-b border-gray-100 gap-3">
            <div class="flex items-center gap-2">
                <span class="w-1 h-6 bg-indigo-500 rounded-full"></span>
                <h2 class="text-gray-800 font-bold text-lg">Activity Feed</h2>
                <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-full font-semibold"
                      x-text="activityTotal + ' log'"></span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select x-model="activityFilter" @change="loadActivity()"
                        class="text-sm bg-gray-100 border-0 rounded-lg px-3 py-1.5 text-gray-700 font-medium">
                    <option value="all">Semua Tipe</option>
                    <option value="sensor">Sensor</option>
                    <option value="actuator">Aktuator</option>
                    <option value="alarm">Alarm</option>
                    <option value="control">Kontrol</option>
                    <option value="schedule">Jadwal</option>
                    <option value="safety">Safety</option>
                </select>
                <button @click="activityAutoRefresh = !activityAutoRefresh"
                        :class="activityAutoRefresh ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-500'"
                        class="text-sm px-3 py-1.5 rounded-lg font-semibold">
                    <span x-text="activityAutoRefresh ? 'Live' : 'Paused'"></span>
                </button>
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="activities.length === 0">
                <div class="text-center py-16">
                    <p class="text-gray-400 text-sm">Belum ada aktivitas</p>
                </div>
            </template>

            <template x-for="act in activities" :key="act.id">
                <div class="flex items-start gap-3 p-4 border-b border-gray-50">
                    <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-blue-100 text-blue-600': act.severity === 'info',
                             'bg-green-100 text-green-600': act.severity === 'success',
                             'bg-yellow-100 text-yellow-600': act.severity === 'warning',
                             'bg-red-100 text-red-600': act.severity === 'danger'
                         }">
                        <span class="text-xs font-bold" x-text="act.severity.charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-800" x-text="act.title"></p>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="act.description"></p>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap" x-text="relativeTime(act.created_at)"></span>
                        </div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold uppercase"
                                  :class="{
                                      'bg-blue-50 text-blue-600': act.severity === 'info',
                                      'bg-green-50 text-green-600': act.severity === 'success',
                                      'bg-yellow-50 text-yellow-600': act.severity === 'warning',
                                      'bg-red-50 text-red-600': act.severity === 'danger'
                                  }"
                                  x-text="act.type"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="px-6 py-3 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center">
            <span class="text-xs text-gray-500">Rotating log: max <span x-text="activityMaxLogs"></span> entri</span>
            <button @click="clearActivities()" class="text-xs text-red-500 font-semibold">Clear All</button>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-white/70 text-xs mb-4">
        Last update: <span x-text="lastUpdate" class="font-semibold text-white"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.dashboard = function() {
    return {
        sensor: {},
        connected: false,
        lastUpdate: '-',
        pollingInterval: null,
        chartLimit: 20,
        
        // 4 mini bar charts
        chartSuhu: null,
        chartRH: null,
        chartGas: null,
        chartAir: null,
        
        activities: [],
        activityFilter: 'all',
        activityAutoRefresh: true,
        activityTotal: 0,
        activityMaxLogs: 1000,
        activityInterval: null,
        
        schedules: [],
        showScheduleForm: false,
        scheduleForm: { id: null, name: 'Penyiraman', hour: '06', minute: '00', duration: 10, enabled: true },
        
        // STATE MANAGEMENT
        isSending: false,
        pendingActions: {},
        pendingTimeouts: {},

        async init() {
            console.log('Dashboard init');
            try {
                this.initCharts();
                await this.loadLatest();
                await this.loadHistory();
                await this.loadActivity();
                await this.loadActivityStats();
                await this.loadSchedules();
                this.startPolling();
            } catch (e) {
                console.error('Init error:', e);
            }
        },

        async loadLatest() {
            try {
                const res = await fetch('/api/sensor/latest');
                const json = await res.json();
                if (json.success) {
                    const serverData = json.data;
                    
                    const keys = ['growlight', 'exhaust', 'pompa', 'atap', 'mode'];
                    keys.forEach(k => {
                        if (this.pendingActions[k]) {
                            serverData[k] = this.sensor[k];
                        }
                    });
                    
                    this.sensor = serverData;
                    this.connected = true;
                    this.lastUpdate = new Date().toLocaleTimeString('id-ID');
                }
            } catch (e) { this.connected = false; }
        },

        async loadHistory() {
            try {
                const res = await fetch('/api/sensor/history?limit=' + this.chartLimit);
                const json = await res.json();
                if (json.success && json.data && json.data.length > 0) {
                    this.updateAllCharts(json.data);
                }
            } catch (e) { console.error('loadHistory error', e); }
        },

        async loadActivity() {
            try {
                const url = '/api/activity?limit=30&type=' + this.activityFilter;
                const res = await fetch(url);
                const json = await res.json();
                if (json.success) {
                    this.activities = json.data;
                    this.activityTotal = json.total || json.count;
                }
            } catch (e) {}
        },

        async loadActivityStats() {
            try {
                const res = await fetch('/api/activity/stats');
                const json = await res.json();
                if (json.success) {
                    this.activityTotal = json.data.total;
                    this.activityMaxLogs = json.data.max_logs;
                }
            } catch (e) {}
        },

        async clearActivities() {
            if (!confirm('Hapus semua activity log?')) return;
            try {
                const res = await fetch('/api/activity/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();
                if (json.success) { this.activities = []; this.loadActivityStats(); }
            } catch (e) {}
        },

        relativeTime(dateStr) {
            const date = new Date(dateStr);
            const diff = Math.floor((new Date() - date) / 1000);
            if (diff < 5) return 'baru saja';
            if (diff < 60) return diff + 'd lalu';
            if (diff < 3600) return Math.floor(diff / 60) + 'm lalu';
            if (diff < 86400) return Math.floor(diff / 3600) + 'j lalu';
            return Math.floor(diff / 86400) + 'h lalu';
        },

        async loadSchedules() {
            try {
                const res = await fetch('/api/schedule');
                const json = await res.json();
                if (json.success) this.schedules = json.data;
            } catch (e) {}
        },

        openScheduleForm() {
            this.scheduleForm = { id: null, name: 'Penyiraman', hour: '06', minute: '00', duration: 10, enabled: true };
            this.showScheduleForm = true;
        },

        closeScheduleForm() { this.showScheduleForm = false; },

        editSchedule(sched) {
            this.scheduleForm = {
                id: sched.id,
                name: sched.name,
                hour: sched.time.substring(0, 2),
                minute: sched.time.substring(3, 5),
                duration: sched.duration,
                enabled: sched.enabled,
            };
            this.showScheduleForm = true;
        },

        async saveSchedule() {
            let hour = String(this.scheduleForm.hour || '0').padStart(2, '0');
            let minute = String(this.scheduleForm.minute || '0').padStart(2, '0');
            
            try {
                const url = this.scheduleForm.id ? '/api/schedule/' + this.scheduleForm.id : '/api/schedule';
                const method = this.scheduleForm.id ? 'PUT' : 'POST';
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.scheduleForm.name,
                        time: hour + ':' + minute,
                        duration: parseInt(this.scheduleForm.duration),
                        enabled: this.scheduleForm.enabled,
                    })
                });
                const json = await res.json();
                if (json.success) {
                    this.closeScheduleForm();
                    await this.loadSchedules();
                    await this.loadActivity();
                } else {
                    alert('Gagal: ' + (json.message || 'Unknown error'));
                }
            } catch (e) { alert('Error: ' + e.message); }
        },

        async toggleSchedule(sched) {
            try {
                const res = await fetch('/api/schedule/' + sched.id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ enabled: !sched.enabled })
                });
                const json = await res.json();
                if (json.success) await this.loadSchedules();
            } catch (e) {}
        },

        async deleteSchedule(sched) {
            if (!confirm('Hapus jadwal "' + sched.name + '"?')) return;
            try {
                const res = await fetch('/api/schedule/' + sched.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();
                if (json.success) {
                    await this.loadSchedules();
                    await this.loadActivity();
                }
            } catch (e) {}
        },

        async syncSchedules() {
            try {
                const res = await fetch('/api/schedule/sync', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json();
                if (json.success) alert('Jadwal berhasil disinkronkan ke ESP32');
            } catch (e) { alert('Sync gagal: ' + e.message); }
        },

        // ============================================
        // INIT 4 MINI BAR CHARTS
        // Y-axis DI-HIDE di ApexCharts karena kita pakai HTML overlay manual
        // ============================================
        initCharts() {
            const gasColorFn = ({ value }) => {
                if (value <= 500) return '#22c55e';
                if (value <= 750) return '#eab308';
                return '#ef4444';
            };

            const airColorFn = ({ value }) => {
                if (value > 50) return '#22c55e';
                if (value > 20) return '#eab308';
                return '#ef4444';
            };

            // ⭐ TAMBAH parameter maxVal
            const baseOptions = (color, name, unit, maxVal) => ({
                chart: {
                    type: 'bar',
                    height: '100%',
                    width: '100%',
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { 
                        enabled: true, 
                        easing: 'easeinout', 
                        speed: 300,
                        dynamicAnimation: { enabled: true, speed: 250 }
                    },
                    background: 'transparent',
                    parentHeightOffset: 0,
                    offsetX: 0,
                    offsetY: 0,
                    redrawOnParentResize: false,
                    redrawOnWindowResize: true
                },
                series: [{ name: name, data: [] }],
                colors: [color],
                plotOptions: {
                    bar: {
                        borderRadius: 3,
                        borderRadiusApplication: 'end',
                        columnWidth: '50%',
                        barHeight: '40%',
                        horizontal: false,
                        distributed: false,
                        dataLabels: { position: 'top' }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: 0 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.3,
                        opacityFrom: 1,
                        opacityTo: 0.75,
                        stops: [0, 100]
                    }
                },
                markers: { size: 0 },
                
                // ⭐ FIX DI SINI — paksa max & min
                yaxis: {
                    show: false,
                    max: maxVal,             // ⬅️ PAKSA max sesuai HTML overlay
                    min: 0,                  // ⬅️ PAKSA min
                    forceNiceScale: false,   // ⬅️ jangan auto-round (biar 2000 tetap 2000)
                    tickAmount: 5            // ⬅️ konsisten 5 tick
                },
                
                xaxis: {
                    type: 'category',
                    labels: {
                        style: { fontSize: '9px', colors: '#9ca3af', fontWeight: 500 },
                        rotate: -45,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        trim: false,
                        showDuplicates: false
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    tooltip: { enabled: false }
                },
                legend: { show: false },
                tooltip: {
                    theme: 'light',
                    style: { fontSize: '11px' },
                    custom: ({ series, seriesIndex, dataPointIndex, w }) => {
                        const raw = w.globals.initialSeries[seriesIndex].rawData?.[dataPointIndex];
                        const value = series[seriesIndex][dataPointIndex];
                        const timeLabel = raw?.timeLabel || '-';
                        return `
                            <div class="px-3 py-2 text-xs">
                                <div class="font-bold text-gray-700 mb-1">${timeLabel}</div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" style="background:${color};"></span>
                                    <span class="text-gray-500">${name}:</span>
                                    <span class="font-bold text-gray-800">${parseFloat(value).toFixed(1)} ${unit}</span>
                                </div>
                            </div>
                        `;
                    }
                },
                grid: {
                    borderColor: '#f3f4f6',
                    strokeDashArray: 3,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: { 
                        top: 10, 
                        right: 15, 
                        bottom: 0, 
                        left: 8 
                    }
                }
            });

            // ⭐ Tambah maxVal di pemanggilan
            const gasOptions = baseOptions('#a855f7', 'Gas', 'ppm', 2000);
            gasOptions.plotOptions.bar.distributed = true;
            gasOptions.colors = [gasColorFn];
            gasOptions.fill = { type: 'solid' };

            const airOptions = baseOptions('#06b6d4', 'Level Air', '%', 100);
            airOptions.plotOptions.bar.distributed = true;
            airOptions.colors = [airColorFn];
            airOptions.fill = { type: 'solid' };

            // ⭐ Tambah maxVal di pemanggilan
            this.chartSuhu = new ApexCharts(document.querySelector('#chartSuhu'), baseOptions('#ef4444', 'Suhu', '°C', 50));
            this.chartRH   = new ApexCharts(document.querySelector('#chartRH'),   baseOptions('#3b82f6', 'Kelembapan', '%', 100));
            this.chartGas  = new ApexCharts(document.querySelector('#chartGas'),  gasOptions);
            this.chartAir  = new ApexCharts(document.querySelector('#chartAir'),  airOptions);

            this.chartSuhu.render();
            this.chartRH.render();
            this.chartGas.render();
            this.chartAir.render();
            console.log('4 mini bar charts initialized (Y-axis overlay mode)');
        },

        // ============================================
        // UPDATE 4 MINI BAR CHARTS
        // 🔄 DATA TERBARU DI KIRI, LAMA DI KANAN
        // ============================================
        updateAllCharts(data) {
            const safe = (v) => (v === null || v === undefined || isNaN(parseFloat(v))) ? 0 : parseFloat(v);
            const fmtTime = (dateStr, withDate = false) => {
                const d = new Date(dateStr);
                const time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                if (!withDate) return time;
                const date = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
                return `${date} ${time}`;
            };

            // ⭐ REVERSE: data terbaru → index 0 (paling kiri)
            const reversedData = [...data].reverse();

            const formatData = (key) => reversedData.map(d => ({
                x: fmtTime(d.created_at),
                y: safe(d[key]),
                timeLabel: fmtTime(d.created_at, true),
                rawValue: safe(d[key])
            }));

            const suhuData = formatData('suhu');
            const rhData   = formatData('kelembapan');
            const gasData  = formatData('gas_ppm');
            const airData  = formatData('level_air');

            if (this.chartSuhu) this.chartSuhu.updateSeries([{ name: 'Suhu', data: suhuData, rawData: suhuData }]);
            if (this.chartRH)   this.chartRH.updateSeries([{ name: 'Kelembapan', data: rhData, rawData: rhData }]);
            if (this.chartGas)  this.chartGas.updateSeries([{ name: 'Gas', data: gasData, rawData: gasData }]);
            if (this.chartAir)  this.chartAir.updateSeries([{ name: 'Level Air', data: airData, rawData: airData }]);
        },

        async setMode(mode) {
            const key = 'mode';
            if (this.pendingTimeouts[key]) clearTimeout(this.pendingTimeouts[key]);
            
            this.pendingActions[key] = true;
            this.isSending = true;
            this.sensor.mode = mode;
            
            const success = await this.sendControl('mode', mode);
            
            if (success) {
                this.pendingTimeouts[key] = setTimeout(() => {
                    delete this.pendingActions[key];
                    this.isSending = false;
                    this.loadLatest();
                }, 12000);
            } else {
                delete this.pendingActions[key];
                this.isSending = false;
                this.loadLatest();
            }
        },

        async toggleControl(action) {
            if (this.pendingTimeouts[action]) clearTimeout(this.pendingTimeouts[action]);
            
            this.pendingActions[action] = true;
            this.isSending = true;
            
            const newValue = this.sensor[action] ? 'off' : 'on';
            this.sensor[action] = (newValue === 'on');
            
            const success = await this.sendControl(action, newValue);
            
            if (success) {
                this.pendingTimeouts[action] = setTimeout(() => {
                    delete this.pendingActions[action];
                    this.isSending = false;
                    this.loadLatest();
                }, 12000);
            } else {
                this.sensor[action] = !(newValue === 'on');
                delete this.pendingActions[action];
                this.isSending = false;
                this.loadLatest();
            }
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
                console.log('Control sent:', action, value, json);
                return json.success === true;
            } catch (e) {
                console.error('Control error:', e);
                return false;
            }
        },

        startPolling() {
            this.pollingInterval = setInterval(() => {
                this.loadLatest();
                this.loadHistory();
            }, 2000);
            
            this.activityInterval = setInterval(() => {
                if (this.activityAutoRefresh) {
                    this.loadActivity();
                    this.loadActivityStats();
                }
            }, 5000);
        }
    };
};
</script>
@endpush