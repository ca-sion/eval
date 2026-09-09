<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $group->name }} - Évaluation CA Sion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* Google Material Design 3 Linear Progress Indicator */
        .m3-progress-track {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background-color: rgba(239, 68, 68, 0.2);
            overflow: hidden;
            z-index: 50;
        }
        .m3-progress-primary,
        .m3-progress-secondary {
            position: absolute;
            top: 0;
            bottom: 0;
            will-change: left, right;
            background-color: #ef4444;
        }
        .m3-progress-primary {
            animation: m3-indeterminate-primary 2s cubic-bezier(0.65, 0.815, 0.735, 0.395) infinite;
        }
        .m3-progress-secondary {
            animation: m3-indeterminate-secondary 2s cubic-bezier(0.165, 0.84, 0.44, 1) 1.15s infinite;
        }
        @keyframes m3-indeterminate-primary {
            0% { left: -35%; right: 100%; }
            60% { left: 100%; right: -90%; }
            100% { left: 100%; right: -90%; }
        }
        @keyframes m3-indeterminate-secondary {
            0% { left: -200%; right: 100%; }
            60% { left: 107%; right: -8%; }
            100% { left: 107%; right: -8%; }
        }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-100">
    <header class="sticky top-0 z-30 bg-slate-900 text-white shadow-md border-b border-slate-800 relative">
        <!-- Google Material Design 3 Indeterminate Progress Indicator -->
        <div
            wire:loading.delay.shortest
            role="progressbar"
            aria-label="Enregistrement en cours"
            aria-live="polite"
            class="m3-progress-track"
        >
            <div class="m3-progress-primary"></div>
            <div class="m3-progress-secondary"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center font-bold text-white shadow-sm ring-2 ring-red-500/30">
                    CA
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-white leading-tight">{{ $group->name }}</h1>
                    <p class="text-xs text-slate-400">CA Sion • Entraîneur</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span wire:loading.remove.delay.shortest class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                    Live
                </span>
                <span wire:loading.delay.shortest class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-950/70 text-red-300 border border-red-800/80 shadow-sm">
                    <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enregistrement...
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-5">
        {{ $slot }}
    </main>

    <footer class="mt-auto py-4 text-center text-xs text-slate-400 border-t border-slate-200">
        CA Sion - Outil d'évaluation
    </footer>

    @livewireScripts
</body>
</html>
