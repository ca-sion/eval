<div x-data="{
    view: localStorage.getItem('coach_view') || 'table',
    setView(v) {
        this.view = v;
        localStorage.setItem('coach_view', v);
    }
}" class="space-y-6">

    <!-- En-tête avec statistiques et bascule de vue -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="lg:text-xl font-black text-slate-900 tracking-tight">{{ $group->name }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    {{ $evaluations->count() }} {{ Str::plural('athlète', $evaluations->count()) }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">
                @if($hasCollectiveSession)
                    Période d'évaluation en cours
                @else
                    Suivi des cycles d'adaptation et de sursis
                @endif
            </p>
        </div>

        <!-- Boutons de bascule Cartes / Tableau (Pleine largeur sur mobile / compact sur grand écran) -->
        <div class="w-full sm:w-auto grid grid-cols-2 sm:inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200">
            <button
                type="button"
                @click="setView('cards')"
                :class="view === 'cards' ? 'bg-white text-slate-900 shadow-sm font-semibold' : 'text-slate-500 hover:text-slate-800'"
                class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg text-xs transition-all duration-150"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Vue Cartes
            </button>
            <button
                type="button"
                @click="setView('table')"
                :class="view === 'table' ? 'bg-white text-slate-900 shadow-sm font-semibold' : 'text-slate-500 hover:text-slate-800'"
                class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg text-xs transition-all duration-150"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
                Vue Tableau
            </button>
        </div>
    </div>

    @if($evaluations->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Aucun suivi individuel en cours</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                Aucune session générale ni période d'adaptation ou sursis n'est actuellement ouverte pour ce groupe.
            </p>
        </div>
    @else

        <!-- VUE CARTES (MOBILE FIRST) -->
        <div x-show="view === 'cards'" class="space-y-5">
            @foreach($evaluations as $eval)
                @php
                    $canToggle = $eval->canToggleStatus();
                    $isEditable = $eval->isEditable();
                    $isSubmitted = $eval->status === \App\Enums\EvaluationStatus::Submitted;
                @endphp
                <div
                    wire:key="eval-card-{{ $eval->id }}"
                    x-data="{
                        injured: {{ $eval->is_injured ? 'true' : 'false' }},
                        lateness: {{ $eval->lateness_count }},
                        c4: {{ $eval->c4_commitment !== null ? (float)$eval->c4_commitment : 'null' }},
                        c5: {{ $eval->c5_behavior !== null ? (float)$eval->c5_behavior : 'null' }},
                        c6: '{{ $eval->c6_level?->value ?? '' }}',
                        c7: {{ $eval->c7_progress !== null ? (float)$eval->c7_progress : 'null' }},
                        c8: {{ $eval->c8_environment !== null ? (float)$eval->c8_environment : 'null' }},
                    }"
                    class="bg-white rounded-2xl shadow-sm border {{ $eval->is_injured ? 'border-amber-300 ring-1 ring-amber-200' : ($isSubmitted ? 'border-slate-300 bg-slate-50/20' : 'border-slate-200') }} overflow-hidden transition-all duration-150"
                >
                    <!-- En-tête de carte (Responsive & compact mobile) -->
                    <div class="p-3.5 sm:p-5 bg-gradient-to-r {{ $eval->is_injured ? 'from-amber-50/70 to-orange-50/40' : 'from-slate-50 to-white' }} border-b border-slate-100 flex items-start sm:items-center justify-between gap-3">
                        <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl {{ $eval->is_injured ? 'bg-amber-500 text-white' : ($isSubmitted ? 'bg-slate-700 text-white' : 'bg-slate-900 text-white') }} flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                {{ strtoupper(substr($eval->athlete->first_name, 0, 1)) }}{{ strtoupper(substr($eval->athlete->last_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-slate-900 leading-snug truncate">
                                    {{ $eval->athlete->first_name }} {{ $eval->athlete->last_name }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs">
                                    <span class="text-slate-500 font-medium whitespace-nowrap">Né(e) en {{ $eval->athlete->birth_year }}</span>
                                    <span class="text-slate-300 hidden sm:inline">•</span>
                                    <!-- Badge de contexte -->
                                    @if($eval->context === \App\Enums\EvaluationContext::Collective)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800 whitespace-nowrap">
                                            {{ $eval->session?->title ?? 'Période d\'évaluation' }}
                                        </span>
                                    @elseif($eval->context === \App\Enums\EvaluationContext::Adaptation)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 whitespace-nowrap">
                                            {{ $eval->context->getLabel() }} (S{{ $eval->currentWeekNumber() }}/{{ $eval->weeks_count }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-800 whitespace-nowrap">
                                            {{ $eval->context->getLabel() }} (S{{ $eval->currentWeekNumber() }}/{{ $eval->weeks_count }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Bouton d'état (Brouillon / Transmis) et action de déverrouillage -->
                        <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-auto pt-0.5 sm:pt-0">
                            @if(! $canToggle)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Clôturé
                                </span>
                            @elseif($isSubmitted)
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $eval->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-xs font-bold transition-all duration-150 bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 shadow-xs whitespace-nowrap"
                                    title="Cliquer pour déverrouiller et modifier les critères"
                                >
                                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    <span>Verrouillé</span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $eval->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-xl text-xs font-bold transition-all duration-150 bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 shadow-xs whitespace-nowrap"
                                    title="Cliquer pour verrouiller / transmettre la fiche"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Brouillon</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Corps de la carte -->
                    <div class="p-4 sm:p-5 space-y-5">

                        @if($isSubmitted)
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 flex items-center justify-between text-xs text-amber-800">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    <span>Fiche transmise et verrouillée. Cliquez sur « Déverrouiller » ci-dessus pour modifier les notes ou retards.</span>
                                </div>
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $eval->id }})"
                                    class="font-bold underline text-amber-900 hover:text-amber-700 ml-2"
                                >
                                    Déverrouiller
                                </button>
                            </div>
                        @endif

                        <!-- Rangée 1 : Blessure & Compteur de Retards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                            <!-- Compteur de retards -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Retards
                                    </span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">À l'entraînement</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="if (lateness > 0) { lateness--; $wire.decrementLateness({{ $eval->id }}); }"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        :disabled="! {{ $isEditable ? 'true' : 'false' }} || lateness <= 0"
                                        class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 flex items-center justify-center text-slate-700 font-bold active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all"
                                    >
                                        -
                                    </button>
                                    <span
                                        class="w-6 text-center font-black text-base"
                                        :class="lateness > 0 ? 'text-red-600' : 'text-slate-800'"
                                        x-text="lateness"
                                    >
                                        {{ $eval->lateness_count }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="lateness++; $wire.incrementLateness({{ $eval->id }})"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="w-8 h-8 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 flex items-center justify-center text-slate-700 font-bold active:scale-95 disabled:opacity-40 disabled:pointer-events-none transition-all"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Toggle Blessure majeure -->
                            <div class="rounded-xl border {{ $eval->is_injured ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50/50' }} p-3.5 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 {{ $eval->is_injured ? 'text-amber-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        Blessure majeure ?
                                    </span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Neutralise l'assiduité & compétitions</p>
                                </div>
                                <button
                                    type="button"
                                    @click="injured = !injured; $wire.toggleInjury({{ $eval->id }})"
                                    {{ ! $isEditable ? 'disabled' : '' }}
                                    :class="injured ? 'bg-amber-500' : 'bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ ! $isEditable ? 'opacity-50 cursor-not-allowed' : '' }}"
                                >
                                    <span
                                        :class="injured ? 'translate-x-5' : 'translate-x-0'"
                                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <!-- Critères qualitatifs (C4, C5, C7, C8) avec pastilles interactives 0 à 10 -->
                        <div class="space-y-4 pt-2 border-t border-slate-100">
                            <!-- C4 : Implication -->
                            @php
                                $c4 = \App\Enums\EvaluationCriterion::C4_Commitment;
                                $c5 = \App\Enums\EvaluationCriterion::C5_Behavior;
                                $c6 = \App\Enums\EvaluationCriterion::C6_Performance;
                                $c7 = \App\Enums\EvaluationCriterion::C7_Progress;
                                $c8 = \App\Enums\EvaluationCriterion::C8_Environment;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1.5 gap-2" title="{{ $c4->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1 min-w-0 pr-1">
                                        <span class="truncate">{{ $c4->code() }} : {{ $c4->getLabel() }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600 flex-shrink-0 whitespace-nowrap" x-text="c4 !== null ? (Number(c4).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c4_commitment !== null ? number_format($eval->c4_commitment, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="grid grid-cols-11 gap-0.5 sm:gap-1 w-full">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c4 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c4_commitment', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c4 !== null && Number(c4) === {{ $i }} ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-600' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="h-8 sm:h-8.5 rounded-lg text-xs font-bold transition-all disabled:opacity-50 p-0 flex items-center justify-center active:scale-95"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C5 : Comportement -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5 gap-2" title="{{ $c5->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1 min-w-0 pr-1">
                                        <span class="truncate">{{ $c5->code() }} : {{ $c5->getLabel() }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600 flex-shrink-0 whitespace-nowrap" x-text="c5 !== null ? (Number(c5).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c5_behavior !== null ? number_format($eval->c5_behavior, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="grid grid-cols-11 gap-0.5 sm:gap-1 w-full">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c5 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c5_behavior', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c5 !== null && Number(c5) === {{ $i }} ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-600' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="h-8 sm:h-8.5 rounded-lg text-xs font-bold transition-all disabled:opacity-50 p-0 flex items-center justify-center active:scale-95"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C6 : Niveau athlétique (Paliers) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5 gap-2" title="{{ $c6->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1 min-w-0 pr-1">
                                        <span class="truncate">{{ $c6->code() }} : {{ $c6->getLabel() }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600 flex-shrink-0 whitespace-nowrap" x-text="c6 ? ('Niveau ' + c6) : '{{ $eval->c6_level?->getLabel() ?? 'Non défini' }}'">{{ $eval->c6_level?->getLabel() ?? 'Non défini' }}</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach(\App\Enums\AthleticLevel::cases() as $level)
                                        <button
                                            type="button"
                                            @click="c6 = '{{ $level->value }}'; $wire.setLevel({{ $eval->id }}, '{{ $level->value }}')"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c6 === '{{ $level->value }}' ? 'bg-slate-900 border-slate-900 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                                            class="py-2 px-2.5 rounded-xl text-xs font-bold text-center border transition-all disabled:opacity-50"
                                        >
                                            <div>{{ $level->getLabel() }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- C7 : Progression -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5 gap-2" title="{{ $c7->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1 min-w-0 pr-1">
                                        <span class="truncate">{{ $c7->code() }} : {{ $c7->getLabel() }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600 flex-shrink-0 whitespace-nowrap" x-text="c7 !== null ? (Number(c7).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c7_progress !== null ? number_format($eval->c7_progress, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="grid grid-cols-11 gap-0.5 sm:gap-1 w-full">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c7 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c7_progress', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c7 !== null && Number(c7) === {{ $i }} ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-600' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="h-8 sm:h-8.5 rounded-lg text-xs font-bold transition-all disabled:opacity-50 p-0 flex items-center justify-center active:scale-95"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C8 : Hygiène et environnement -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5 gap-2" title="{{ $c8->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1 min-w-0 pr-1">
                                        <span class="truncate">{{ $c8->code() }} : {{ $c8->getLabel() }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600 flex-shrink-0 whitespace-nowrap" x-text="c8 !== null ? (Number(c8).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c8_environment !== null ? number_format($eval->c8_environment, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="grid grid-cols-11 gap-0.5 sm:gap-1 w-full">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c8 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c8_environment', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c8 !== null && Number(c8) === {{ $i }} ? 'bg-red-600 text-white shadow-sm ring-1 ring-red-600' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="h-8 sm:h-8.5 rounded-lg text-xs font-bold transition-all disabled:opacity-50 p-0 flex items-center justify-center active:scale-95"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <!-- Remarques de l'entraîneur -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Remarques de l'entraîneur (disciplinaires ou sportives)
                            </label>
                            <textarea
                                x-data="{ note: @js($eval->coach_notes ?? '') }"
                                x-model="note"
                                @change="$wire.updateNotes({{ $eval->id }}, note)"
                                {{ ! $isEditable ? 'disabled' : '' }}
                                rows="2"
                                placeholder="Observations spécifiques pour le responsable technique..."
                                class="w-full text-xs rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 disabled:text-slate-500 p-2.5"
                            ></textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- VUE TABLEAU (FORMAT COMPACT TYPE FEUILLE DE CALCUL AVEC COLONNE FIGÉE) -->
        <div x-show="view === 'table'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3 sm:px-4 sticky left-0 z-20 bg-slate-50 border-r border-slate-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] min-w-[130px] sm:min-w-[160px]">Athlète</th>
                            <th class="py-2.5 px-2 text-center w-20 sm:w-24 whitespace-nowrap">Statut</th>
                            <th class="py-2.5 px-1.5 text-center w-16 sm:w-20 whitespace-nowrap">Retards</th>
                            <th class="py-2.5 px-1.5 text-center w-14 sm:w-16 whitespace-nowrap">Blessure</th>
                            <th class="py-2 px-1 text-center w-12 sm:w-14 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C4_Commitment->getDescription() }}">
                                <div class="font-black text-slate-900 text-xs">C4</div>
                                <div class="text-[9px] font-semibold text-slate-400 normal-case block truncate max-w-[48px] mx-auto leading-tight" title="{{ \App\Enums\EvaluationCriterion::C4_Commitment->shortLabel() }}">{{ \App\Enums\EvaluationCriterion::C4_Commitment->shortLabel() }}</div>
                            </th>
                            <th class="py-2 px-1 text-center w-12 sm:w-14 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C5_Behavior->getDescription() }}">
                                <div class="font-black text-slate-900 text-xs">C5</div>
                                <div class="text-[9px] font-semibold text-slate-400 normal-case block truncate max-w-[48px] mx-auto leading-tight" title="{{ \App\Enums\EvaluationCriterion::C5_Behavior->shortLabel() }}">{{ \App\Enums\EvaluationCriterion::C5_Behavior->shortLabel() }}</div>
                            </th>
                            <th class="py-2 px-1.5 text-center w-28 sm:w-32 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C6_Performance->getDescription() }}">
                                <div class="font-black text-slate-900 text-xs">C6</div>
                                <div class="text-[9px] font-semibold text-slate-400 normal-case block truncate max-w-[70px] mx-auto leading-tight" title="{{ \App\Enums\EvaluationCriterion::C6_Performance->shortLabel() }}">{{ \App\Enums\EvaluationCriterion::C6_Performance->shortLabel() }}</div>
                            </th>
                            <th class="py-2 px-1 text-center w-12 sm:w-14 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C7_Progress->getDescription() }}">
                                <div class="font-black text-slate-900 text-xs">C7</div>
                                <div class="text-[9px] font-semibold text-slate-400 normal-case block truncate max-w-[48px] mx-auto leading-tight" title="{{ \App\Enums\EvaluationCriterion::C7_Progress->shortLabel() }}">{{ \App\Enums\EvaluationCriterion::C7_Progress->shortLabel() }}</div>
                            </th>
                            <th class="py-2 px-1 text-center w-12 sm:w-14 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C8_Environment->getDescription() }}">
                                <div class="font-black text-slate-900 text-xs">C8</div>
                                <div class="text-[9px] font-semibold text-slate-400 normal-case block truncate max-w-[48px] mx-auto leading-tight" title="{{ \App\Enums\EvaluationCriterion::C8_Environment->shortLabel() }}">{{ \App\Enums\EvaluationCriterion::C8_Environment->shortLabel() }}</div>
                            </th>
                            <th class="py-2.5 px-3 text-left w-32 sm:w-44 whitespace-nowrap">Notes</th>
                            <th class="py-2.5 px-2 text-center w-24 sm:w-28 whitespace-nowrap">État</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800">
                        @foreach($evaluations as $eval)
                            @php
                                $isEditable = $eval->isEditable();
                            @endphp
                            <tr
                                wire:key="eval-row-{{ $eval->id }}"
                                x-data="{
                                    injured: {{ $eval->is_injured ? 'true' : 'false' }},
                                    lateness: {{ $eval->lateness_count }},
                                    note: @js($eval->coach_notes ?? ''),
                                }"
                                class="{{ $eval->is_injured ? 'bg-amber-50/40' : 'hover:bg-slate-50/60' }} transition-colors group"
                            >
                                <!-- Athlète (Colonne figée sticky) -->
                                <td
                                    class="py-2.5 px-3 sm:px-4 whitespace-nowrap sticky left-0 z-10 border-r border-slate-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)] transition-colors"
                                    :class="injured ? 'bg-amber-50' : 'bg-white group-hover:bg-slate-50'"
                                >
                                    <div class="font-bold text-slate-900 leading-tight">{{ $eval->athlete->first_name }} {{ $eval->athlete->last_name }}</div>
                                    <div class="text-[10px] text-slate-400">Né(e) {{ $eval->athlete->birth_year }}</div>
                                </td>

                                <!-- Contexte -->
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $eval->context === \App\Enums\EvaluationContext::Collective ? 'bg-blue-100 text-blue-800' : ($eval->context === \App\Enums\EvaluationContext::Adaptation ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $eval->context->shortLabel() }} (S{{ $eval->currentWeekNumber() }}/{{ $eval->weeks_count }})
                                    </span>
                                </td>

                                <!-- Retards -->
                                <td class="py-2.5 px-1.5 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="if (lateness > 0) { lateness--; $wire.decrementLateness({{ $eval->id }}); }"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :disabled="! {{ $isEditable ? 'true' : 'false' }} || lateness <= 0"
                                            class="w-5 h-5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs disabled:opacity-40 flex items-center justify-center active:scale-95"
                                        >-</button>
                                        <span
                                            class="w-4 text-center font-bold text-xs"
                                            :class="lateness > 0 ? 'text-red-600' : 'text-slate-700'"
                                            x-text="lateness"
                                        >{{ $eval->lateness_count }}</span>
                                        <button
                                            type="button"
                                            @click="lateness++; $wire.incrementLateness({{ $eval->id }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            class="w-5 h-5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs disabled:opacity-40 flex items-center justify-center active:scale-95"
                                        >+</button>
                                    </div>
                                </td>

                                <!-- Blessure -->
                                <td class="py-2.5 px-1.5 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="injured = !injured; $wire.toggleInjury({{ $eval->id }})"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        :class="injured ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold transition-colors disabled:opacity-50"
                                        x-text="injured ? 'OUI' : 'NON'"
                                    >
                                        {{ $eval->is_injured ? 'OUI' : 'NON' }}
                                    </button>
                                </td>

                                <!-- C4 -->
                                <td class="py-2 px-1 text-center whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c4_commitment', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-1 w-11 sm:w-12 text-center font-bold rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 mx-auto block"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c4_commitment !== null && (float)$eval->c4_commitment === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C5 -->
                                <td class="py-2 px-1 text-center whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c5_behavior', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-1 w-11 sm:w-12 text-center font-bold rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 mx-auto block"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c5_behavior !== null && (float)$eval->c5_behavior === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C6 -->
                                <td class="py-2 px-1.5 whitespace-nowrap text-center">
                                    <select
                                        wire:change="setLevel({{ $eval->id }}, $event.target.value === '' ? null : $event.target.value)"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 w-28 sm:w-32 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 font-medium mx-auto block"
                                    >
                                        <option value="">-</option>
                                        @foreach(\App\Enums\AthleticLevel::cases() as $level)
                                            <option value="{{ $level->value }}" @selected($eval->c6_level === $level)>{{ $level->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- C7 -->
                                <td class="py-2 px-1 text-center whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c7_progress', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-1 w-11 sm:w-12 text-center font-bold rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 mx-auto block"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c7_progress !== null && (float)$eval->c7_progress === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C8 -->
                                <td class="py-2 px-1 text-center whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c8_environment', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-1 w-11 sm:w-12 text-center font-bold rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100 mx-auto block"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c8_environment !== null && (float)$eval->c8_environment === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- Notes -->
                                <td class="py-2.5 px-3">
                                    <input
                                        type="text"
                                        x-model="note"
                                        @change="$wire.updateNotes({{ $eval->id }}, note)"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        placeholder="Remarque..."
                                        class="text-xs py-1 px-2 w-28 sm:w-40 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    />
                                </td>

                                <!-- État -->
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    @php
                                        $rowCanToggle = $eval->canToggleStatus();
                                        $rowIsSubmitted = $eval->status === \App\Enums\EvaluationStatus::Submitted;
                                    @endphp
                                    @if(! $rowCanToggle)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500">Clôturé</span>
                                    @elseif($rowIsSubmitted)
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $eval->id }})"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold transition-colors bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 shadow-xs"
                                            title="Cliquer pour déverrouiller et modifier"
                                        >
                                            <svg class="w-3 h-3 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Verrouillé
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $eval->id }})"
                                            class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold transition-colors bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 shadow-xs"
                                            title="Cliquer pour verrouiller"
                                        >
                                            Brouillon
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    <!-- GUIDE OFFICIEL DE NOTATION & ÉCHELLE DE VALEURS (UI / UX EXPLICATIF) -->
    <div
        x-data="{
            openGuide: true,
            activeScore: 5,
            scores: @js(\App\Enums\EvaluationCriterion::qualitativeRubric()),
            tiers: @js(\App\Enums\EvaluationCriterion::qualitativeTiers()),
        }"
        id="baremes"
        x-on:open-baremes.window="openGuide = true"
        class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all mt-8 scroll-mt-20"
    >
        <!-- En-tête du guide -->
        <div
            @click="openGuide = !openGuide"
            class="p-4 sm:p-5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between cursor-pointer select-none"
        >
            <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0 mr-2">
                <div class="w-10 h-10 rounded-xl bg-red-600/90 text-white flex items-center justify-center font-black text-sm shadow-inner flex-shrink-0">
                    0-10
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                        <h3 class="text-sm sm:text-base font-extrabold tracking-tight text-white leading-snug">
                            Barème des notes
                        </h3>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5 leading-snug">
                        Guide officiel pour garantir une échelle de notation équitable entre tous les entraîneurs
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-300 flex-shrink-0">
                <span class="hidden sm:inline" x-text="openGuide ? 'Masquer' : 'Afficher le barème'"></span>
                <svg
                    class="w-5 h-5 transform transition-transform duration-200 text-slate-400"
                    :class="openGuide ? 'rotate-180' : 'rotate-0'"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Corps du guide -->
        <div x-show="openGuide" x-collapse class="p-5 sm:p-6 space-y-6">

            <!-- Message d'étalonnage fondamental (Le repère 5/10) -->
            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 sm:p-5 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="space-y-1.5 flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-900 text-white font-black text-xs flex-shrink-0">5</span>
                        <h4 class="text-sm font-bold text-slate-900">
                            est le standard attendu
                        </h4>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed max-w-2xl">
                        <strong>5 n'est pas une mauvaise note</strong> : c'est le standard attendu par le club. L'athlète répond fidèlement, sérieusement et avec constance aux exigences du critère évalué. La note <strong>10</strong> est réservée à des réalisations véritablement exceptionnelles et rares.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-stretch lg:self-auto justify-start lg:justify-end flex-shrink-0 text-xs">
                    <span class="px-2.5 py-1 rounded-lg font-medium bg-white text-slate-700 border border-slate-200 whitespace-nowrap">
                        0 = Non acquis
                    </span>
                    <span class="px-2.5 py-1 rounded-lg font-bold bg-slate-900 text-white whitespace-nowrap shadow-xs">
                        5 = Acquis
                    </span>
                    <span class="px-2.5 py-1 rounded-lg font-medium bg-white text-slate-700 border border-slate-200 whitespace-nowrap">
                        10 = Exceptionnel
                    </span>
                </div>
            </div>

            <!-- Grille synthétique des 5 grands paliers -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
                    Les 5 paliers d'évaluation
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    @foreach(\App\Enums\EvaluationCriterion::qualitativeTiers() as $key => $tier)
                        <div
                            class="rounded-xl border {{ $tier['highlight'] ? 'border-slate-400/80 bg-slate-50/70 ring-1 ring-slate-300/60' : 'border-slate-200 bg-white' }} p-3.5 flex flex-col justify-between transition-all hover:border-slate-300 hover:shadow-xs"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $tier['highlight'] ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $tier['range'] }}
                                    </span>
                                    @if($tier['highlight'])
                                        <span class="text-[10px] font-extrabold text-slate-900 uppercase tracking-tight">Standard</span>
                                    @endif
                                </div>
                                <h5 class="text-xs font-bold text-slate-900 leading-snug">
                                    {{ $tier['name'] }}
                                </h5>
                                <div class="text-[11px] text-slate-500 mb-2 font-medium">
                                    {{ $tier['subtitle'] }}
                                </div>
                                <p class="text-[11px] text-slate-600 leading-snug">
                                    {{ $tier['summary'] }}
                                </p>
                            </div>
                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
                                <span>Scores :</span>
                                <div class="flex gap-1">
                                    @foreach($tier['scores'] as $sc)
                                        <button
                                            type="button"
                                            @click="activeScore = {{ $sc }}"
                                            :class="activeScore === {{ $sc }} ? 'bg-slate-900 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                                            class="w-5 h-5 rounded flex items-center justify-center transition-colors text-[10px]"
                                        >
                                            {{ $sc }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Sélecteur interactif détail note par note (0 à 10) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                        Détail par note (cliquez pour inspecter)
                    </h4>
                    <span class="text-xs text-slate-500">
                        Note sélectionnée : <strong class="text-slate-900" x-text="activeScore + ' / 10'"></strong>
                    </span>
                </div>

                <!-- Boutons de sélection 0 à 10 -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-2">
                    @for($s = 0; $s <= 10; $s++)
                        <button
                            type="button"
                            @click="activeScore = {{ $s }}"
                            :class="activeScore === {{ $s }} ? 'bg-slate-900 text-white shadow-sm font-black' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80'"
                            class="flex-1 min-w-[34px] h-9 rounded-xl font-bold text-xs transition-all flex items-center justify-center flex-shrink-0"
                        >
                            {{ $s }}
                        </button>
                    @endfor
                </div>

                <!-- Panneau de détail dynamique de la note sélectionnée -->
                <div class="mt-3 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-900 text-white font-black text-sm"
                                x-text="activeScore"
                            ></span>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white text-slate-800 border border-slate-300"
                                x-text="scores[activeScore].label"
                            ></span>
                        </div>
                        <span class="text-xs text-slate-500 font-medium" x-text="scores[activeScore].tier_label"></span>
                    </div>

                    <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-medium" x-text="scores[activeScore].description"></p>

                    <!-- Exemples appliqués aux critères C4, C5, C7, C8 -->
                    <div class="pt-3 border-t border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-900">C4 • Implication</span>
                                </div>
                                <p class="text-slate-600 leading-snug" x-text="scores[activeScore].c4"></p>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-900">C5 • Comportement</span>
                                </div>
                                <p class="text-slate-600 leading-snug" x-text="scores[activeScore].c5"></p>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-900">C7 • Progression</span>
                                </div>
                                <p class="text-slate-600 leading-snug" x-text="scores[activeScore].c7"></p>
                            </div>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-slate-900">C8 • Environnement</span>
                                </div>
                                <p class="text-slate-600 leading-snug" x-text="scores[activeScore].c8"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
