<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Token CSRF untuk kebutuhan permintaan AJAX dari dashboard. --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Greenhouse IoT')</title>
    
    <!-- Tailwind CSS -->
    {{-- Tailwind dimuat melalui CDN untuk keperluan pengembangan. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- ApexCharts -->
    {{-- Pustaka grafik yang digunakan pada kartu riwayat sensor. --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
    
    <!-- Google Fonts (Inter) -->
    {{-- Font Inter dipakai sebagai tipografi utama dashboard. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Menyembunyikan elemen Alpine.js sebelum proses inisialisasi selesai. */
        [x-cloak] { display: none !important; }
        
        /* Tipografi dasar halaman. */
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Penyesuaian tampilan scrollbar agar selaras dengan tema. */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.3); 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover { 
            background: rgba(255,255,255,0.5); 
        }
        
        /* Efek transisi halus saat kartu didekati kursor. */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        /* Latar belakang kartu dengan efek kaca. */
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Animasi pergerakan latar belakang halaman. */
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .animated-bg {
            background: linear-gradient(-45deg, #10b981, #059669, #0891b2, #0d9488);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        /* Animasi titik indikator status koneksi. */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
        
        /* Angka status ditampilkan dengan lebar digit yang konsisten. */
        .stat-value {
            font-variant-numeric: tabular-nums;
        }
        
        /* Animasi bilah pemuatan. */
        @keyframes loading {
            from { width: 0%; }
            to { width: 100%; }
        }

        /*
            Penataan grafik riwayat sensor.
            Sumbu Y ditempatkan di luar area gulir sehingga tetap terlihat
            ketika pengguna menggulir data ke arah horizontal.
        */
        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 260px;
        }

        /* Area sumbu Y yang tidak ikut bergulir bersama data. */
        .chart-yaxis {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 42px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            padding: 22px 4px 32px 0;   /* Padding atas mengikuti grid, padding bawah mengikuti tinggi sumbu X. */
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            background: linear-gradient(to right, rgba(249,250,251,1) 80%, rgba(249,250,251,0));
            z-index: 10;
            pointer-events: none;
            font-variant-numeric: tabular-nums;
        }
        .chart-yaxis span {
            line-height: 1;
        }

        /* Area gulir horizontal yang memuat batang grafik. */
        .chart-scroll {
            position: absolute;
            left: 42px;              /* Memberikan ruang untuk sumbu Y. */
            right: 0;
            top: 0;
            bottom: 0;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
        }
        .chart-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .chart-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .chart-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        /* Garis pembatas vertikal antara sumbu Y dan area grafik. */
        .chart-yaxis-border {
            position: absolute;
            left: 42px;
            top: 22px;
            bottom: 32px;
            width: 1px;
            background: #e5e7eb;
            z-index: 9;
            pointer-events: none;
        }
    </style>
    
    {{-- Slot untuk gaya tambahan dari halaman turunan. --}}
    @stack('styles')
</head>
<body class="animated-bg min-h-screen">
    {{-- Konten utama halaman turunan. --}}
    @yield('content')
    
    {{-- Slot untuk skrip tambahan dari halaman turunan. --}}
    @stack('scripts')
    
    {{-- Alpine.js dimuat dengan atribut defer agar tidak menghambat render halaman. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</body>
</html>