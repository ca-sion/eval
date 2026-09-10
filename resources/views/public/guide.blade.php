<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50 text-slate-900 antialiased scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guide des évaluations • CA Sion</title>
    <meta name="description" content="Découvrez le fonctionnement, les critères et le barème des évaluations au CA Sion. Un cadre transparent, bienveillant et structuré pour accompagner chaque athlète.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-100 text-slate-800" x-data="{ activeTab: 'c1', simulatorOpen: false }">

    <!-- En-tête de navigation -->
    <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md text-white border-b border-slate-800 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="/" class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center font-extrabold text-white text-base shadow-sm ring-2 ring-red-500/30 hover:bg-red-700 transition">
                    CA
                </a>
                <div>
                    <span class="text-base font-bold tracking-tight text-white leading-tight block">CA Sion</span>
                    <span class="text-xs text-slate-400 block">Guide des évaluations</span>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-xs font-semibold text-slate-300">
                <a href="#demarche" class="hover:text-white transition">La démarche</a>
                <a href="#criteres" class="hover:text-white transition">Les 9 critères</a>
                <a href="#bareme" class="hover:text-white transition">Barème et échelle</a>
                <a href="#periodes" class="hover:text-white transition">Périodes et contextes</a>
                <a href="#decisions" class="hover:text-white transition">Décisions et suite</a>
                <a href="#simulateur" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600/90 hover:bg-red-600 text-white font-bold transition shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18a2.25 2.25 0 0 1-2.25 2.25h-6A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75h6A2.25 2.25 0 0 1 15.75 6v2.25" />
                    </svg>
                    <span>Simulateur</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1">

        <!-- Bannière Hero -->
        <section class="bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white pt-14 pb-16 px-4 sm:px-6 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
            
            <div class="max-w-5xl mx-auto text-center relative z-10 space-y-5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-300 border border-red-500/30">
                    <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
                    Règlement • Art. 3, 9, 10 et 27
                </div>

                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Comprendre les évaluations au <span class="text-red-500">CA Sion</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-300 max-w-3xl mx-auto leading-relaxed">
                    Un cadre clair, transparent et bienveillant destiné aux athlètes et à leurs familles pour expliquer comment se déroulent les périodes d'observation, les 9 critères d'évaluation et la notation globale.
                </p>

                <!-- Piliers résumés -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 max-w-4xl mx-auto text-left">
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/70 backdrop-blur-xs">
                        <div class="text-2xl font-black text-red-400">9</div>
                        <div class="text-xs font-bold text-white mt-0.5">Critères objectifs</div>
                        <div class="text-[11px] text-slate-400 mt-1">Assiduité, engagement, comportement et progrès.</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/70 backdrop-blur-xs">
                        <div class="text-2xl font-black text-blue-400">10.0</div>
                        <div class="text-xs font-bold text-white mt-0.5">Barème sur 10</div>
                        <div class="text-[11px] text-slate-400 mt-1">5.0 = acquis et conforme aux attentes du groupe.</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/70 backdrop-blur-xs">
                        <div class="text-2xl font-black text-emerald-400">100%</div>
                        <div class="text-xs font-bold text-white mt-0.5">Transparence</div>
                        <div class="text-[11px] text-slate-400 mt-1">Chaque athlète reçoit son bilan individuel d'évaluation.</div>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/70 backdrop-blur-xs">
                        <div class="text-2xl font-black text-amber-400">1</div>
                        <div class="text-xs font-bold text-white mt-0.5">Bilan individuel</div>
                        <div class="text-[11px] text-slate-400 mt-1">Un document officiel complet généré pour chaque athlète.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Démarche & Philosophie -->
        <section id="demarche" class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.516 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Pourquoi cette évaluation ?</h2>
                        <p class="text-xs text-slate-500">Un outil pédagogique d'encouragement et de responsabilisation</p>
                    </div>
                </div>

                <div class="prose prose-slate max-w-none text-sm text-slate-700 space-y-3 leading-relaxed">
                    <p>
                        Au <strong>Club Athlétique de Sion</strong>, l'évaluation n'est pas un examen ni un jugement punitif : c'est un <strong>outil d'échange et d'accompagnement</strong>. Elle permet à chaque entraîneur de faire un point complet et objectif sur la progression sportive de l'athlète, son assiduité, son implication et son respect de la vie de groupe.
                    </p>
                    <p>
                        Elle donne à l'athlète et à ses parents une visibilité totale sur ses points forts, ses axes d'amélioration et la confirmation de son intégration dans son groupe d'entraînement, conformément aux <strong>articles 3, 9, 10 et 27 du Règlement du club</strong>.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-100">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">1</div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">Période d'observation</h3>
                            <p class="text-[12px] text-slate-600 mt-0.5">Sur 5 semaines, les présences NDS J+S et la régularité aux entraînements sont enregistrées.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">2</div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">Synthèse et barème</h3>
                            <p class="text-[12px] text-slate-600 mt-0.5">L'entraîneur renseigne la grille selon les critères précis du barème officiel.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">3</div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900">Bilan individuel</h3>
                            <p class="text-[12px] text-slate-600 mt-0.5">Un bilan est réalisé pour valoriser les points forts et guider la progression de l'athlète.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Les 9 Critères -->
        <section id="criteres" class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-6">
                <div>
                    <span class="text-xs font-bold text-red-600 uppercase tracking-wider">Détail complet</span>
                    <h2 class="text-2xl font-black text-slate-900">Les 9 critères d'évaluation</h2>
                    <p class="text-xs text-slate-500">Chaque critère est pondéré pour composer une note globale équilibrée sur 10.0.</p>
                </div>
                <div class="text-xs font-semibold text-slate-600 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-2xs self-start sm:self-auto">
                    Total pondérations : <span class="text-red-600 font-bold">100%</span>
                </div>
            </div>

            <div class="space-y-4">
                @foreach($criteria as $criterion)
                    @php
                        $weight = $weights[$criterion->value] ?? 10;
                        $isQualitative = $criterion->isQualitative();
                    @endphp
                    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-xs border border-slate-200 transition hover:border-slate-300">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-slate-900 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-xs">
                                    {{ $criterion->code() }}
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">{{ $criterion->getLabel() }}</h3>
                                    <span class="text-xs text-slate-500">Critère {{ $criterion->code() }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                @if($isQualitative)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span>Appréciation</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                        </svg>
                                        <span>Calcul factuel</span>
                                    </span>
                                @endif
                                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold bg-red-50 text-red-700 border border-red-200">
                                    Poids : {{ $weight }}%
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 text-xs sm:text-sm text-slate-700 leading-relaxed">
                            {{ $criterion->getDescription() }}
                        </div>

                        <!-- Précisions spécifiques par critère -->
                        <div class="mt-3 bg-slate-50 rounded-xl p-3 text-xs text-slate-600 border border-slate-200/70">
                            @if($criterion === \App\Enums\EvaluationCriterion::C1_Attendance)
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Fonctionnement :</span>
                                    <span>Calculé selon le rapport entre les présences réelles (NDS J+S) et le nombre d'entraînements prévus sur la période. <strong class="text-slate-800">Neutralisé</strong> si une blessure déclarée empêche la pratique.</span>
                                </div>
                            @elseif($criterion === \App\Enums\EvaluationCriterion::C2_Punctuality)
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Fonctionnement :</span>
                                    <span>Note de départ standard de <strong>6.0 / 10</strong> (la ponctualité étant la norme attendue). Chaque retard non justifié entraîne une déduction de <strong>0.3 point</strong>.</span>
                                </div>
                            @elseif($criterion === \App\Enums\EvaluationCriterion::C3_Competitions)
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Fonctionnement :</span>
                                    <span>Comparaison entre le nombre de compétitions effectuées et l'objectif fixé pour le groupe d'entraînement. <strong class="text-slate-800">Neutralisé</strong> en cas de blessure déclarée.</span>
                                </div>
                            @elseif($criterion === \App\Enums\EvaluationCriterion::C6_Performance)
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Barème des niveaux :</span>
                                    <span>Cantonal (6.0), Régional (7.5), National (9.0), International (10.0). <strong class="text-slate-800">Ce critère est un bonus valorisant</strong> sans pénalité pour l'athlète.</span>
                                </div>
                            @elseif($criterion === \App\Enums\EvaluationCriterion::C9_Volunteering)
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Engagement familial :</span>
                                    <span>Concerne les familles des athlètes jusqu'à <strong>17 ans</strong>. Les participations des parents aux concours et manifestations organisés par le club rapportent des points précieux pour l'athlète.</span>
                                </div>
                            @else
                                <div class="flex items-start gap-2">
                                    <span class="font-bold text-slate-900 shrink-0">Échelle :</span>
                                    <span>Noté de 0 à 10 selon la grille qualitative de l'entraîneur (5.0 correspondant au cadre minimal attendu).</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Section Barème et Paliers Qualitatifs -->
        <section id="bareme" class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
                <div class="mb-6">
                    <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Échelle d'évaluation</span>
                    <h2 class="text-2xl font-black text-slate-900">Les 5 paliers de notation</h2>
                    <p class="text-xs text-slate-500">Pour les critères qualitatifs (implication, comportement, progression, environnement), les notes de 0 à 10 sont structurées selon 5 paliers rigoureux.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    @foreach($qualitativeTiers as $key => $tier)
                        <div class="rounded-2xl p-4 border {{ $tier['border_color'] }} {{ $tier['bg_color'] }} flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-1 mb-2">
                                    <span class="text-xs font-extrabold px-2 py-0.5 rounded-md {{ $tier['badge_color'] }}">
                                        {{ $tier['range'] }}
                                    </span>
                                    @if($tier['highlight'])
                                        <span class="text-[10px] font-bold text-blue-700 bg-blue-100/80 px-1.5 py-0.5 rounded uppercase">Standard</span>
                                    @endif
                                </div>
                                <h4 class="text-sm font-bold {{ $tier['text_color'] }}">{{ $tier['name'] }}</h4>
                                <div class="text-[11px] font-medium text-slate-500 mt-0.5">{{ $tier['subtitle'] }}</div>
                                <p class="text-xs text-slate-700 mt-2.5 leading-snug">
                                    {{ $tier['summary'] }}
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200/50 text-[11px] font-bold text-slate-500">
                                Notes : {{ implode(', ', $tier['scores']) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Grille détaillée par critères qualitatifs -->
                <div class="mt-10 pt-8 border-t border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Descripteurs précis par critère qualitatif</h3>
                    <p class="text-xs text-slate-500 mb-6">Ce que l'entraîneur observe concrètement lors des séances d'entraînement :</p>

                    <div class="space-y-6">
                        <!-- C4 Implication -->
                        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-xs font-extrabold">C4</span>
                                <span>Implication et rigueur (15%)</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-red-50/70 border border-red-200 text-red-900">
                                    <div class="font-bold text-[11px] text-red-700 mb-1">0 - 2 • Non acquis</div>
                                    {{ $qualitativeRubric[1]['c4'] ?? $qualitativeRubric[0]['c4'] }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
                                    <div class="font-bold text-[11px] text-amber-700 mb-1">3 - 4 • En cours</div>
                                    {{ $qualitativeRubric[3]['c4'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900">
                                    <div class="font-bold text-[11px] text-blue-700 mb-1">5 - 6 • Acquis (Standard)</div>
                                    {{ $qualitativeRubric[5]['c4'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-emerald-900">
                                    <div class="font-bold text-[11px] text-emerald-700 mb-1">7 - 8 • Maîtrisé</div>
                                    {{ $qualitativeRubric[7]['c4'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900">
                                    <div class="font-bold text-[11px] text-purple-700 mb-1">9 - 10 • Exceptionnel</div>
                                    {{ $qualitativeRubric[10]['c4'] ?? '' }}
                                </div>
                            </div>
                        </div>

                        <!-- C5 Comportement -->
                        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-xs font-extrabold">C5</span>
                                <span>Comportement et esprit d'équipe (15%) • Règlement Art. 9</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-red-50/70 border border-red-200 text-red-900">
                                    <div class="font-bold text-[11px] text-red-700 mb-1">0 - 2 • Non acquis</div>
                                    {{ $qualitativeRubric[1]['c5'] ?? $qualitativeRubric[0]['c5'] }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
                                    <div class="font-bold text-[11px] text-amber-700 mb-1">3 - 4 • En cours</div>
                                    {{ $qualitativeRubric[3]['c5'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900">
                                    <div class="font-bold text-[11px] text-blue-700 mb-1">5 - 6 • Acquis (Standard)</div>
                                    {{ $qualitativeRubric[5]['c5'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-emerald-900">
                                    <div class="font-bold text-[11px] text-emerald-700 mb-1">7 - 8 • Maîtrisé</div>
                                    {{ $qualitativeRubric[7]['c5'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900">
                                    <div class="font-bold text-[11px] text-purple-700 mb-1">9 - 10 • Exceptionnel</div>
                                    {{ $qualitativeRubric[10]['c5'] ?? '' }}
                                </div>
                            </div>
                        </div>

                        <!-- C7 Progression -->
                        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-xs font-extrabold">C7</span>
                                <span>Progression technique et motrice (10%)</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-red-50/70 border border-red-200 text-red-900">
                                    <div class="font-bold text-[11px] text-red-700 mb-1">0 - 2 • Non acquis</div>
                                    {{ $qualitativeRubric[1]['c7'] ?? $qualitativeRubric[0]['c7'] }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
                                    <div class="font-bold text-[11px] text-amber-700 mb-1">3 - 4 • En cours</div>
                                    {{ $qualitativeRubric[3]['c7'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900">
                                    <div class="font-bold text-[11px] text-blue-700 mb-1">5 - 6 • Acquis (Standard)</div>
                                    {{ $qualitativeRubric[5]['c7'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-emerald-900">
                                    <div class="font-bold text-[11px] text-emerald-700 mb-1">7 - 8 • Maîtrisé</div>
                                    {{ $qualitativeRubric[7]['c7'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900">
                                    <div class="font-bold text-[11px] text-purple-700 mb-1">9 - 10 • Exceptionnel</div>
                                    {{ $qualitativeRubric[10]['c7'] ?? '' }}
                                </div>
                            </div>
                        </div>

                        <!-- C8 Hygiène et Environnement -->
                        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200">
                            <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-xs font-extrabold">C8</span>
                                <span>Hygiène de vie et environnement familial (5%)</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs">
                                <div class="p-2.5 rounded-xl bg-red-50/70 border border-red-200 text-red-900">
                                    <div class="font-bold text-[11px] text-red-700 mb-1">0 - 2 • Non acquis</div>
                                    {{ $qualitativeRubric[1]['c8'] ?? $qualitativeRubric[0]['c8'] }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
                                    <div class="font-bold text-[11px] text-amber-700 mb-1">3 - 4 • En cours</div>
                                    {{ $qualitativeRubric[3]['c8'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900">
                                    <div class="font-bold text-[11px] text-blue-700 mb-1">5 - 6 • Acquis (Standard)</div>
                                    {{ $qualitativeRubric[5]['c8'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-emerald-900">
                                    <div class="font-bold text-[11px] text-emerald-700 mb-1">7 - 8 • Maîtrisé</div>
                                    {{ $qualitativeRubric[7]['c8'] ?? '' }}
                                </div>
                                <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 text-purple-900">
                                    <div class="font-bold text-[11px] text-purple-700 mb-1">9 - 10 • Exceptionnel</div>
                                    {{ $qualitativeRubric[10]['c8'] ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Périodes et Contextes -->
        <section id="periodes" class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
            <div class="mb-6">
                <span class="text-xs font-bold text-red-600 uppercase tracking-wider">Cadre réglementaire</span>
                <h2 class="text-2xl font-black text-slate-900">Périodes et contextes d'évaluation</h2>
                <p class="text-xs text-slate-500">Chaque type d'évaluation répond à un cadre défini par le règlement du CA Sion.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($contexts as $context)
                    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-xs border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    Durée standard : {{ $context->defaultWeeks() }} semaines
                                </span>
                                <span class="text-xs font-semibold text-slate-400">
                                    {{ $context->shortLabel() }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900">{{ $context->getLabel() }}</h3>

                            <p class="text-xs text-slate-600 mt-2.5 leading-relaxed">
                                @if($context === \App\Enums\EvaluationContext::Collective)
                                    Session semestrielle officielle menée auprès de l'ensemble des athlètes actifs du club dans leurs groupes d'entraînement respectifs.
                                @elseif($context === \App\Enums\EvaluationContext::Adaptation)
                                    Période probatoire de 5 semaines pour tout nouvel athlète intégrant le club afin de valider son adéquation avec le groupe, les exigences et l'esprit du club.
                                @elseif($context === \App\Enums\EvaluationContext::EvaluationProbation)
                                    Période complémentaire de 2 semaines accordée à un athlète en difficulté pour lui permettre de corriger des fragilités (assiduité, ponctualité, implication) avec le soutien de son entraîneur.
                                @elseif($context === \App\Enums\EvaluationContext::DisciplinaryProbation)
                                    Période ciblée de 2 semaines consécutive à un rappel au règlement (Art. 27.1) pour observer le rétablissement d'une attitude irréprochable.
                                @endif
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Statut d'athlète associé</span>
                            <span class="font-semibold text-slate-700">
                                @if($context === \App\Enums\EvaluationContext::Adaptation)
                                    Adaptation
                                @elseif($context === \App\Enums\EvaluationContext::Collective)
                                    Membre actif
                                @else
                                    Sursis probatoire
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Section Décisions et Suite -->
        <section id="decisions" class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
                <div class="mb-6">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Conséquences et suites</span>
                    <h2 class="text-2xl font-black text-slate-900">Les décisions possibles</h2>
                    <p class="text-xs text-slate-500">À l'issue de l'évaluation, l'entraîneur et le Comité statuent selon 3 issues officielles prévues par le règlement.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold mb-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-emerald-900">Maintien ou admission</h3>
                        <div class="text-[11px] font-semibold text-emerald-700 mt-0.5">Règlement Art. 10.4</div>
                        <p class="text-xs text-emerald-800 mt-2 leading-relaxed">
                            L'athlète satisfait aux exigences de son groupe. Son statut de membre actif est confirmé et il poursuit sereinement ses entraînements et compétitions.
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200">
                        <div class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center font-bold mb-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-amber-900">Sursis probatoire</h3>
                        <div class="text-[11px] font-semibold text-amber-700 mt-0.5">Règlement Art. 10.5 (2 semaines)</div>
                        <p class="text-xs text-amber-800 mt-2 leading-relaxed">
                            Certains critères demandent une attention immédiate (ex. assiduité, implication). Une période de 2 semaines permet à l'athlète de redresser la barre avec des objectifs précis.
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-red-50/70 border border-red-200">
                        <div class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold mb-3">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-red-900">Non-admission ou réorientation</h3>
                        <div class="text-[11px] font-semibold text-red-700 mt-0.5">Règlement Art. 10.5 et 27</div>
                        <p class="text-xs text-red-800 mt-2 leading-relaxed">
                            Si les critères ne sont pas atteints après le sursis, une réorientation vers un autre groupe plus adapté ou une décision collégiale du Comité est prononcée.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Simulateur Interactif Alpine.js -->
        <section id="simulateur" class="max-w-5xl mx-auto px-4 sm:px-6 py-12" x-data="{
            c1: 10,
            c2_retards: 0,
            c3: 10,
            c4: 6,
            c5: 6,
            c6_level: 6.0,
            c7: 6,
            c8: 6,
            c9_volunteering: 2,
            get c2Score() {
                return Math.max(0, 6.0 - (Number(this.c2_retards) * 0.3));
            },
            get c9Score() {
                const req = 2;
                const count = Number(this.c9_volunteering);
                return Math.min(10.0, Math.max(0.0, 2.0 + (count * (4.0 / req))));
            },
            get totalScore() {
                const total = (Number(this.c1) * 0.20) +
                              (Number(this.c2Score) * 0.05) +
                              (Number(this.c3) * 0.15) +
                              (Number(this.c4) * 0.15) +
                              (Number(this.c5) * 0.15) +
                              (Number(this.c6_level) * 0.10) +
                              (Number(this.c7) * 0.10) +
                              (Number(this.c8) * 0.05) +
                              (Number(this.c9Score) * 0.05);
                return Math.round(total * 10) / 10;
            },
            get decisionText() {
                if (this.totalScore >= 5.0) return 'Maintien ou admission confirmé(e) (Art. 10.4)';
                if (this.totalScore >= 4.0) return 'Sursis probatoire recommandé (Art. 10.5)';
                return 'Non-admission ou arbitrage Comité (Art. 10.5)';
            },
            get decisionBadge() {
                if (this.totalScore >= 5.0) return 'bg-emerald-100 text-emerald-800 border-emerald-200';
                if (this.totalScore >= 4.0) return 'bg-amber-100 text-amber-800 border-amber-200';
                return 'bg-red-100 text-red-800 border-red-200';
            }
        }">
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-700">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-700">
                    <div>
                        <span class="text-xs font-bold text-red-400 uppercase tracking-wider">Outil interactif</span>
                        <h2 class="text-2xl font-black text-white">Simulateur de calcul de note</h2>
                        <p class="text-xs text-slate-400">Ajustez les curseurs pour observer comment les critères et les pondérations composent la moyenne globale.</p>
                    </div>

                    <div class="bg-slate-800/90 rounded-2xl p-4 border border-slate-700 flex items-center gap-4 shrink-0 shadow-sm">
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 block uppercase">Moyenne simulée</span>
                            <div class="text-3xl font-black text-white tracking-tight" x-text="totalScore.toFixed(1) + ' / 10'"></div>
                        </div>
                        <div class="border-l border-slate-700 pl-4">
                            <span class="text-[10px] font-bold text-slate-400 block uppercase">Projection</span>
                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border mt-0.5" :class="decisionBadge" x-text="decisionText"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6">
                    <!-- Colonne 1 : Critères factuels -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Critères factuels et présences</h3>

                        <!-- C1 Assiduité -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C1 • Assiduité aux séances (20%)</span>
                                <span class="text-red-400 font-bold" x-text="c1 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c1" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>0% présences</span>
                                <span>50%</span>
                                <span>100% présences</span>
                            </div>
                        </div>

                        <!-- C2 Ponctualité -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C2 • Retards constatés (5%)</span>
                                <span class="text-red-400 font-bold" x-text="c2Score.toFixed(1) + ' / 10 (' + c2_retards + ' retard' + (c2_retards > 1 ? 's' : '') + ')'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c2_retards" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>0 retard (6.0)</span>
                                <span>-0.3 pt par retard</span>
                                <span>10 retards (3.0)</span>
                            </div>
                        </div>

                        <!-- C3 Compétitions -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C3 • Compétitions réalisées (15%)</span>
                                <span class="text-red-400 font-bold" x-text="c3 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c3" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Aucune</span>
                                <span>50% des objectifs</span>
                                <span>Toutes</span>
                            </div>
                        </div>

                        <!-- C6 Niveau -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C6 • Niveau athlétique (10% bonus)</span>
                                <span class="text-red-400 font-bold" x-text="c6_level + ' / 10'"></span>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 mt-2">
                                <button type="button" @click="c6_level = 5.5" :class="c6_level === 5.5 ? 'bg-red-600 text-white font-bold' : 'bg-slate-700 text-slate-300'" class="py-1 rounded-lg text-xs transition">Cantonal (5.5)</button>
                                <button type="button" @click="c6_level = 6.0" :class="c6_level === 6.0 ? 'bg-red-600 text-white font-bold' : 'bg-slate-700 text-slate-300'" class="py-1 rounded-lg text-xs transition">Régional (6.0)</button>
                                <button type="button" @click="c6_level = 7.0" :class="c6_level === 7.0 ? 'bg-red-600 text-white font-bold' : 'bg-slate-700 text-slate-300'" class="py-1 rounded-lg text-xs transition">National (7.0)</button>
                                <button type="button" @click="c6_level = 9.0" :class="c6_level === 9.0 ? 'bg-red-600 text-white font-bold' : 'bg-slate-700 text-slate-300'" class="py-1 rounded-lg text-xs transition">Inter. (9.0)</button>
                            </div>
                        </div>

                        <!-- C9 Bénévolat -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C9 • Bénévolat familial (5%)</span>
                                <span class="text-red-400 font-bold" x-text="c9Score.toFixed(1) + ' / 10 (' + c9_volunteering + ' aide' + (c9_volunteering > 1 ? 's' : '') + ')'"></span>
                            </div>
                            <input type="range" min="0" max="4" step="1" x-model.number="c9_volunteering" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>0 (note 2.0)</span>
                                <span>2 (requis, note 6.0)</span>
                                <span>4 (note 10.0)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne 2 : Critères qualitatifs -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Critères qualitatifs de l'entraîneur</h3>

                        <!-- C4 Implication -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C4 • Implication et rigueur (15%)</span>
                                <span class="text-red-400 font-bold" x-text="c4 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c4" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Non acquis (0)</span>
                                <span>Acquis standard (5-6)</span>
                                <span>Exceptionnel (10)</span>
                            </div>
                        </div>

                        <!-- C5 Comportement -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C5 • Comportement et esprit d'équipe (15%)</span>
                                <span class="text-red-400 font-bold" x-text="c5 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c5" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Non acquis (0)</span>
                                <span>Acquis standard (5-6)</span>
                                <span>Exceptionnel (10)</span>
                            </div>
                        </div>

                        <!-- C7 Progression -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C7 • Progression motrice (10%)</span>
                                <span class="text-red-400 font-bold" x-text="c7 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c7" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Non acquis (0)</span>
                                <span>Acquis standard (5-6)</span>
                                <span>Exceptionnel (10)</span>
                            </div>
                        </div>

                        <!-- C8 Environnement -->
                        <div class="bg-slate-800/60 rounded-xl p-3.5 border border-slate-700/80">
                            <div class="flex justify-between text-xs mb-1.5 font-semibold">
                                <span>C8 • Hygiène de vie et environnement (5%)</span>
                                <span class="text-red-400 font-bold" x-text="c8 + ' / 10'"></span>
                            </div>
                            <input type="range" min="0" max="10" step="1" x-model.number="c8" class="w-full accent-red-500 cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                <span>Non acquis (0)</span>
                                <span>Acquis standard (5-6)</span>
                                <span>Exceptionnel (10)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Foire aux questions (FAQ) -->
        <section class="max-w-5xl mx-auto px-4 sm:px-6 py-12">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200">
                <div class="mb-6">
                    <span class="text-xs font-bold text-red-600 uppercase tracking-wider">Foire aux questions</span>
                    <h2 class="text-2xl font-black text-slate-900">Questions fréquentes des parents et athlètes</h2>
                </div>

                <div class="space-y-4" x-data="{ openFaq: null }">
                    <div class="border border-slate-200 rounded-2xl p-4 transition" :class="openFaq === 1 ? 'bg-slate-50/70 border-slate-300' : ''">
                        <button type="button" @click="openFaq = (openFaq === 1 ? null : 1)" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900">
                            <span>Que se passe-t-il si l'athlète est blessé durant la période d'évaluation ?</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform" :class="openFaq === 1 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse class="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-200/60 leading-relaxed">
                            Lorsqu'une blessure est signalée et constatée par l'entraîneur, les critères d'assiduité (C1) et de compétitions (C3) sont automatiquement <strong>neutralisés</strong>. La note moyenne globale est recalculée sur la base des autres critères sans pénaliser l'athlète.
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-4 transition" :class="openFaq === 2 ? 'bg-slate-50/70 border-slate-300' : ''">
                        <button type="button" @click="openFaq = (openFaq === 2 ? null : 2)" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900">
                            <span>Comment les parents ont-ils accès aux résultats de l'évaluation ?</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform" :class="openFaq === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse class="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-200/60 leading-relaxed">
                            À l'issue de la session, un <strong>Bilan individuel d'évaluation (document PDF officiel)</strong> est généré pour chaque athlète. En cas de sursis probatoire ou sur demande des parents et de l'athlète, un échange avec l'entraîneur est organisé pour faire le point et fixer des objectifs de progression.
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-4 transition" :class="openFaq === 3 ? 'bg-slate-50/70 border-slate-300' : ''">
                        <button type="button" @click="openFaq = (openFaq === 3 ? null : 3)" class="w-full flex items-center justify-between text-left font-bold text-sm text-slate-900">
                            <span>Pourquoi le bénévolat des parents est-il pris en compte ?</span>
                            <svg class="w-4 h-4 text-slate-500 transition-transform" :class="openFaq === 3 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse class="text-xs text-slate-600 mt-3 pt-3 border-t border-slate-200/60 leading-relaxed">
                            Le CA Sion est une association sportive animée par des bénévoles passionnés. L'organisation des concours d'athlétisme repose sur l'aide des familles. Pour les athlètes jusqu'à 17 ans, l'implication des parents (2 aides par an demandées) est un critère valorisé à hauteur de 5% dans l'évaluation.
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Pied de page -->
    <footer class="bg-slate-900 text-white py-8 border-t border-slate-800 text-xs text-center text-slate-400 space-y-2">
        <div class="max-w-7xl mx-auto px-4">
            <p class="font-bold text-slate-300">CA Sion</p>
            <p>Outil d'évaluation • Règlement art. 3, 9, 10 et 27</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
