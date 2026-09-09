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
    <header class="sticky top-0 z-30 bg-slate-900 text-white shadow-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center font-bold text-white shadow-sm ring-2 ring-red-500/30">
                    CAS
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-white leading-tight">{{ $group->name }}</h1>
                    <p class="text-xs text-slate-400">CA Sion Athlétisme • Espace Entraîneur</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                    Accès Direct
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-5">
        {{ $slot }}
    </main>

    <footer class="mt-auto py-4 text-center text-xs text-slate-400 border-t border-slate-200">
        CA Sion • Conformité Statuts Club (Art. 3, 10 et 27)
    </footer>

    @livewireScripts
</body>
</html>
