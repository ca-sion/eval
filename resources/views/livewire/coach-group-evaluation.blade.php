<div x-data="{
    view: localStorage.getItem('coach_view') || 'cards',
    setView(v) {
        this.view = v;
        localStorage.setItem('coach_view', v);
    }
}" class="space-y-6">

    <!-- En-tête avec statistiques et bascule de vue -->
    <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-black text-slate-900 tracking-tight">{{ $group->name }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    {{ $evaluations->count() }} {{ Str::plural('athlète', $evaluations->count()) }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">
                @if($hasCollectiveSession)
                    Session générale d'évaluation en cours
                @else
                    Suivi des cycles d'adaptation et de sursis
                @endif
            </p>
        </div>

        <!-- Boutons de bascule Cartes / Tableau -->
        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 self-start sm:self-auto">
            <button
                type="button"
                @click="setView('cards')"
                :class="view === 'cards' ? 'bg-white text-slate-900 shadow-sm font-semibold' : 'text-slate-500 hover:text-slate-800'"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs transition-all duration-150"
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
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs transition-all duration-150"
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
                        c8: {{ $eval->c8_sports_hygiene !== null ? (float)$eval->c8_sports_hygiene : 'null' }},
                    }"
                    class="bg-white rounded-2xl shadow-sm border {{ $eval->is_injured ? 'border-amber-300 ring-1 ring-amber-200' : ($isSubmitted ? 'border-slate-300 bg-slate-50/20' : 'border-slate-200') }} overflow-hidden transition-all duration-150"
                >
                    <!-- En-tête de carte -->
                    <div class="p-4 sm:p-5 bg-gradient-to-r {{ $eval->is_injured ? 'from-amber-50/70 to-orange-50/40' : 'from-slate-50 to-white' }} border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl {{ $eval->is_injured ? 'bg-amber-500 text-white' : ($isSubmitted ? 'bg-slate-700 text-white' : 'bg-slate-900 text-white') }} flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                {{ strtoupper(substr($eval->athlete->first_name, 0, 1)) }}{{ strtoupper(substr($eval->athlete->last_name, 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 leading-tight">
                                    {{ $eval->athlete->last_name }} {{ $eval->athlete->first_name }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-slate-500 font-medium">Né(e) en {{ $eval->athlete->birth_year }}</span>
                                    <span class="text-slate-300">•</span>
                                    <!-- Badge de contexte -->
                                    @if($eval->context === \App\Enums\EvaluationContext::Collective)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            {{ $eval->session?->title ?? 'Session générale' }}
                                        </span>
                                    @elseif($eval->context === \App\Enums\EvaluationContext::Adaptation)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Adaptation (Semaine {{ $eval->currentWeekNumber() }}/{{ $eval->weeks_count }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                            Sursis probatoire (Semaine {{ $eval->currentWeekNumber() }}/{{ $eval->weeks_count }})
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Bouton d'état (Brouillon / Transmis) et action de déverrouillage -->
                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            @if(! $canToggle)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Clôturé
                                </span>
                            @elseif($isSubmitted)
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $eval->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 shadow-sm"
                                    title="Cliquer pour déverrouiller et modifier les critères"
                                >
                                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Verrouillé (Déverrouiller)
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $eval->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 shadow-sm"
                                    title="Cliquer pour verrouiller / transmettre la fiche"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Brouillon (Verrouiller)
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

                            <!-- Compteur de retards -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3.5 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Retards en séance
                                    </span>
                                    <p class="text-[11px] text-slate-500 mt-0.5">-1.5 pt par retard</p>
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
                        </div>

                        <!-- Critères qualitatifs (C4, C5, C7, C8) avec pastilles interactives 0 à 10 -->
                        <div class="space-y-4 pt-2 border-t border-slate-100">
                            <!-- C4 : Implication -->
                            @php
                                $c4 = \App\Enums\EvaluationCriterion::C4_Commitment;
                                $c5 = \App\Enums\EvaluationCriterion::C5_Behavior;
                                $c6 = \App\Enums\EvaluationCriterion::C6_Performance;
                                $c7 = \App\Enums\EvaluationCriterion::C7_Progress;
                                $c8 = \App\Enums\EvaluationCriterion::C8_SportsHygiene;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1.5" title="{{ $c4->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1">
                                        {{ $c4->code() }} : {{ $c4->getLabel() }}
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600" x-text="c4 !== null ? (Number(c4).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c4_commitment !== null ? number_format($eval->c4_commitment, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c4 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c4_commitment', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c4 !== null && Number(c4) === {{ $i }} ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="flex-1 min-w-[28px] h-8 rounded-lg text-xs font-bold transition-all disabled:opacity-50"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C5 : Comportement -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5" title="{{ $c5->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1">
                                        {{ $c5->code() }} : {{ $c5->getLabel() }}
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600" x-text="c5 !== null ? (Number(c5).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c5_behavior !== null ? number_format($eval->c5_behavior, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c5 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c5_behavior', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c5 !== null && Number(c5) === {{ $i }} ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="flex-1 min-w-[28px] h-8 rounded-lg text-xs font-bold transition-all disabled:opacity-50"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C6 : Niveau athlétique (Paliers) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5" title="{{ $c6->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1">
                                        {{ $c6->code() }} : {{ $c6->getLabel() }}
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600" x-text="c6 ? ('Palier ' + c6) : '{{ $eval->c6_level?->getLabel() ?? 'Non défini' }}'">{{ $eval->c6_level?->getLabel() ?? 'Non défini' }}</span>
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
                                            <div class="text-[10px] font-normal" :class="c6 === '{{ $level->value }}' ? 'text-slate-300' : 'text-slate-400'">Palier {{ $level->score() }} pts</div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- C7 : Progression -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5" title="{{ $c7->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1">
                                        {{ $c7->code() }} : {{ $c7->getLabel() }}
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600" x-text="c7 !== null ? (Number(c7).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c7_progress !== null ? number_format($eval->c7_progress, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c7 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c7_progress', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c7 !== null && Number(c7) === {{ $i }} ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="flex-1 min-w-[28px] h-8 rounded-lg text-xs font-bold transition-all disabled:opacity-50"
                                        >
                                            {{ $i }}
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <!-- C8 : Hygiène et environnement -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5" title="{{ $c8->getDescription() }}">
                                    <span class="text-xs font-bold text-slate-700 cursor-help flex items-center gap-1">
                                        {{ $c8->code() }} : {{ $c8->getLabel() }}
                                        <svg class="w-3.5 h-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                    <span class="text-xs font-extrabold text-red-600" x-text="c8 !== null ? (Number(c8).toFixed(1) + ' / 10') : 'Non noté'">{{ $eval->c8_sports_hygiene !== null ? number_format($eval->c8_sports_hygiene, 1) . ' / 10' : 'Non noté' }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @for($i = 0; $i <= 10; $i++)
                                        <button
                                            type="button"
                                            @click="c8 = {{ $i }}; $wire.setScore({{ $eval->id }}, 'c8_sports_hygiene', {{ $i }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :class="c8 !== null && Number(c8) === {{ $i }} ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                            class="flex-1 min-w-[28px] h-8 rounded-lg text-xs font-bold transition-all disabled:opacity-50"
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

        <!-- VUE TABLEAU (FORMAT COMPACT TYPE FEUILLE DE CALCUL) -->
        <div x-show="view === 'table'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Athlète</th>
                            <th class="py-3 px-3">Statut</th>
                            <th class="py-3 px-3 text-center">Blessure</th>
                            <th class="py-3 px-3 text-center">Retards</th>
                            <th class="py-3 px-3 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C4_Commitment->getDescription() }}">C4 ({{ \App\Enums\EvaluationCriterion::C4_Commitment->shortLabel() }})</th>
                            <th class="py-3 px-3 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C5_Behavior->getDescription() }}">C5 ({{ \App\Enums\EvaluationCriterion::C5_Behavior->shortLabel() }})</th>
                            <th class="py-3 px-3 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C6_Performance->getDescription() }}">C6 ({{ \App\Enums\EvaluationCriterion::C6_Performance->shortLabel() }})</th>
                            <th class="py-3 px-3 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C7_Progress->getDescription() }}">C7 ({{ \App\Enums\EvaluationCriterion::C7_Progress->shortLabel() }})</th>
                            <th class="py-3 px-3 cursor-help" title="{{ \App\Enums\EvaluationCriterion::C8_SportsHygiene->getDescription() }}">C8 ({{ \App\Enums\EvaluationCriterion::C8_SportsHygiene->shortLabel() }})</th>
                            <th class="py-3 px-4">Notes</th>
                            <th class="py-3 px-3 text-center">État</th>
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
                                class="{{ $eval->is_injured ? 'bg-amber-50/40' : 'hover:bg-slate-50/60' }} transition-colors"
                            >
                                <!-- Athlète -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $eval->athlete->last_name }} {{ $eval->athlete->first_name }}</div>
                                    <div class="text-[11px] text-slate-400">Né(e) {{ $eval->athlete->birth_year }}</div>
                                </td>

                                <!-- Contexte -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $eval->context === \App\Enums\EvaluationContext::Collective ? 'bg-blue-100 text-blue-800' : ($eval->context === \App\Enums\EvaluationContext::Adaptation ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $eval->context === \App\Enums\EvaluationContext::Collective ? 'Générale' : ($eval->context === \App\Enums\EvaluationContext::Adaptation ? 'Adapt. (S'.$eval->currentWeekNumber().'/'.$eval->weeks_count.')' : 'Sursis (S'.$eval->currentWeekNumber().'/'.$eval->weeks_count.')') }}
                                    </span>
                                </td>

                                <!-- Blessure -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="injured = !injured; $wire.toggleInjury({{ $eval->id }})"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        :class="injured ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        class="px-2 py-1 rounded text-[11px] font-bold transition-colors disabled:opacity-50"
                                        x-text="injured ? 'OUI' : 'NON'"
                                    >
                                        {{ $eval->is_injured ? 'OUI' : 'NON' }}
                                    </button>
                                </td>

                                <!-- Retards -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="if (lateness > 0) { lateness--; $wire.decrementLateness({{ $eval->id }}); }"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            :disabled="! {{ $isEditable ? 'true' : 'false' }} || lateness <= 0"
                                            class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold disabled:opacity-40"
                                        >-</button>
                                        <span
                                            class="w-5 text-center font-bold"
                                            :class="lateness > 0 ? 'text-red-600' : 'text-slate-700'"
                                            x-text="lateness"
                                        >{{ $eval->lateness_count }}</span>
                                        <button
                                            type="button"
                                            @click="lateness++; $wire.incrementLateness({{ $eval->id }})"
                                            {{ ! $isEditable ? 'disabled' : '' }}
                                            class="w-6 h-6 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold disabled:opacity-40"
                                        >+</button>
                                    </div>
                                </td>

                                <!-- C4 -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c4_commitment', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c4_commitment !== null && (float)$eval->c4_commitment === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C5 -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c5_behavior', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c5_behavior !== null && (float)$eval->c5_behavior === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C6 -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <select
                                        wire:change="setLevel({{ $eval->id }}, $event.target.value === '' ? null : $event.target.value)"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    >
                                        <option value="">-</option>
                                        @foreach(\App\Enums\AthleticLevel::cases() as $level)
                                            <option value="{{ $level->value }}" @selected($eval->c6_level === $level)>{{ $level->getLabel() }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- C7 -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c7_progress', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c7_progress !== null && (float)$eval->c7_progress === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- C8 -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <select
                                        wire:change="setScore({{ $eval->id }}, 'c8_sports_hygiene', $event.target.value === '' ? null : parseFloat($event.target.value))"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        class="text-xs py-1 px-2 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    >
                                        <option value="">-</option>
                                        @for($i = 0; $i <= 10; $i++)
                                            <option value="{{ $i }}" @selected($eval->c8_sports_hygiene !== null && (float)$eval->c8_sports_hygiene === (float)$i)>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </td>

                                <!-- Notes -->
                                <td class="py-3 px-4">
                                    <input
                                        type="text"
                                        x-model="note"
                                        @change="$wire.updateNotes({{ $eval->id }}, note)"
                                        {{ ! $isEditable ? 'disabled' : '' }}
                                        placeholder="Remarque..."
                                        class="text-xs py-1 px-2 w-36 rounded-lg border-slate-300 focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                    />
                                </td>

                                <!-- État -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
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
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold transition-colors bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200"
                                            title="Cliquer pour déverrouiller et modifier"
                                        >
                                            <svg class="w-3 h-3 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Verrouillé (Déverrouiller)
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="toggleStatus({{ $eval->id }})"
                                            class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold transition-colors bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200"
                                            title="Cliquer pour verrouiller"
                                        >
                                            Brouillon (Verrouiller)
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

</div>
