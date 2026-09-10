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
                    <h1 class="text-base font-bold tracking-tight text-white leading-tight">CA Sion</h1>
                    <p class="text-xs text-slate-400">Outil d'évaluation</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="#baremes"
                    onclick="const el = document.getElementById('baremes'); if (el) { el.scrollIntoView({ behavior: 'smooth' }); window.dispatchEvent(new CustomEvent('open-baremes')); }"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white border border-slate-700/80 transition-all duration-150 shadow-sm"
                >
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Aide</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </a>
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
