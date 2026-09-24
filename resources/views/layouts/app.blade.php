<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Greenhouse IoT')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }
        
        body {
            font-family: 'Inter', sans-serif;
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.3); 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover { 
            background: rgba(255,255,255,0.5); 
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
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
        
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.2); }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
        
        .stat-value {
            font-variant-numeric: tabular-nums;
        }
        
        @keyframes loading {
            from { width: 0%; }
            to { width: 100%; }
        }

        /* ============================================================
           CHART STYLING
           Y-axis overlay FIXED di luar scroll area.
           Hanya SVG bar yang di-scroll.
        ============================================================ */
        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 260px;
        }

        /* Area Y-axis (fixed, tidak ikut scroll) */
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
            padding: 22px 4px 32px 0;   /* top = grid padding top, bottom = x-axis height */
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

        /* Area scroll untuk chart (bar saja) */
        .chart-scroll {
            position: absolute;
            left: 42px;              /* geser ke kanan untuk kasih ruang Y-axis */
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

        /* Garis vertikal pembatas Y-axis (visual) */
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
    
    @stack('styles')
</head>
<body class="animated-bg min-h-screen">
    @yield('content')
    
    @stack('scripts')
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</body>
</html>