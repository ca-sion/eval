<x-filament-panels::page>
    @php
        $stats = $this->stats;
        $session = $this->record;
    @endphp

    {{-- Bandeau principal : progression globale et statut de la session --}}
    <x-filament::section>
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="font-size: 1.25rem; font-weight: 700;">{{ $session->title }}</span>
                @if($session->is_closed)
                    <x-filament::badge color="danger" icon="heroicon-o-lock-closed">
                        Session clôturée
                    </x-filament::badge>
                @else
                    <x-filament::badge color="success" icon="heroicon-o-bolt">
                        Session ouverte et active
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        <x-slot name="description">
            Du <strong>{{ $session->start_date ? \Carbon\Carbon::parse($session->start_date)->format('d/m/Y') : '-' }}</strong> au <strong>{{ $session->end_date ? \Carbon\Carbon::parse($session->end_date)->format('d/m/Y') : '-' }}</strong> ({{ $session->weeks_count }} semaines)
        </x-slot>

        <x-slot name="afterHeader">
            <div style="min-width: 220px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">
                    <span style="opacity: 0.8;">Avancement global</span>
                    <span style="font-weight: 700;">{{ $stats['global_progress'] }}%</span>
                </div>
                <div style="width: 100%; height: 8px; background-color: rgba(156, 163, 175, 0.2); border-radius: 9999px; overflow: hidden;">
                    <div style="height: 100%; width: {{ $stats['global_progress'] }}%; background-color: var(--primary-600, #f59e0b); border-radius: 9999px; transition: width 0.5s ease;"></div>
                </div>
            </div>
        </x-slot>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- ÉTAPE 1 : Initialisation et effectifs --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-user-group"
        collapsible
    >
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>Étape 1 : Initialisation et effectifs</span>
                @if($stats['total_evaluations'] > 0)
                    <x-filament::badge color="success">
                        {{ $stats['total_evaluations'] }} fiches actives
                    </x-filament::badge>
                @else
                    <x-filament::badge color="warning">
                        À initialiser
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        <x-slot name="description">
            Générez les fiches d'évaluation pour tous les athlètes actifs du club ou importez la liste depuis Tiiva.
        </x-slot>

        <x-slot name="afterHeader">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                {{ $this->getAction('initialize_evaluations') }}
                {{ $this->getAction('import_tiiva') }}
            </div>
        </x-slot>

        {{-- Métriques Étape 1 --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 8px;">
            <div style="padding: 12px; background: rgba(156, 163, 175, 0.08); border-radius: 8px; border: 1px solid rgba(156, 163, 175, 0.15);">
                <div style="font-size: 0.75rem; opacity: 0.7;">Athlètes actifs du club</div>
                <div style="font-size: 1.25rem; font-weight: 700; margin-top: 2px;">{{ $stats['total_active_athletes'] }}</div>
            </div>
            <div style="padding: 12px; background: rgba(156, 163, 175, 0.08); border-radius: 8px; border: 1px solid rgba(156, 163, 175, 0.15);">
                <div style="font-size: 0.75rem; opacity: 0.7;">Fiches créées pour la session</div>
                <div style="font-size: 1.25rem; font-weight: 700; margin-top: 2px;">{{ $stats['total_evaluations'] }}</div>
            </div>
            <div style="padding: 12px; background: rgba(156, 163, 175, 0.08); border-radius: 8px; border: 1px solid rgba(156, 163, 175, 0.15);">
                <div style="font-size: 0.75rem; opacity: 0.7;">Groupes concernés</div>
                <div style="font-size: 1.25rem; font-weight: 700; margin-top: 2px;">{{ count($stats['groups_stats']) }}</div>
            </div>
            <div style="padding: 12px; background: rgba(156, 163, 175, 0.08); border-radius: 8px; border: 1px solid rgba(156, 163, 175, 0.15);">
                <div style="font-size: 0.75rem; opacity: 0.7;">Taux de couverture</div>
                <div style="font-size: 1.25rem; font-weight: 700; margin-top: 2px;">
                    {{ $stats['total_active_athletes'] > 0 ? round(($stats['total_evaluations'] / $stats['total_active_athletes']) * 100) : 0 }}%
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- ÉTAPE 2 : Saisie mobile des entraîneurs --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-device-phone-mobile"
        collapsible
    >
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>Étape 2 : Saisie mobile des entraîneurs</span>
                <x-filament::badge color="{{ $stats['coach_progress_percent'] >= 100 ? 'success' : 'gray' }}">
                    {{ $stats['coach_progress_percent'] }}% complété ({{ $stats['fully_rated_count'] }}/{{ $stats['total_evaluations'] }} notés)
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Partagez les liens mobiles directs aux entraîneurs de chaque groupe pour la notation terrain (C4, C5, C6, C7, C8).
        </x-slot>

        {{-- Cartes de suivi par groupe --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-top: 8px;">
            @forelse($stats['groups_stats'] as $groupStat)
                @php
                    $group = $groupStat['group'];
                @endphp
                <div style="padding: 14px; background: rgba(156, 163, 175, 0.06); border-radius: 10px; border: 1px solid rgba(156, 163, 175, 0.18); display: flex; flex-direction: column; justify-content: space-between; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; font-size: 0.95rem;">{{ $group->name }}</span>
                            <x-filament::badge color="{{ $groupStat['progress_percent'] >= 100 ? 'success' : 'gray' }}">
                                {{ $groupStat['progress_percent'] }}%
                            </x-filament::badge>
                        </div>

                        <div style="margin-top: 8px; font-size: 0.8rem; opacity: 0.8; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Athlètes notés :</span>
                                <strong>{{ $groupStat['rated_count'] }} / {{ $groupStat['total'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Mode d'arbitrage :</span>
                                <span>{{ $group->arbitration_mode?->getLabel() ?? 'Quota' }} ({{ $group->quota_places ?? 12 }} places)</span>
                            </div>
                        </div>

                        {{-- Mini barre de progression --}}
                        <div style="width: 100%; height: 6px; background-color: rgba(156, 163, 175, 0.2); border-radius: 9999px; overflow: hidden; margin-top: 10px;">
                            <div style="height: 100%; width: {{ $groupStat['progress_percent'] }}%; background-color: var(--primary-600, #3b82f6); border-radius: 9999px;"></div>
                        </div>
                    </div>

                    {{-- Boutons d'action pour le groupe --}}
                    <div style="padding-top: 8px; border-top: 1px solid rgba(156, 163, 175, 0.15); display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <x-filament::button
                            color="gray"
                            size="xs"
                            tag="a"
                            href="{{ $groupStat['whatsapp_url'] }}"
                            target="_blank"
                            icon="heroicon-o-chat-bubble-oval-left-ellipsis"
                        >
                            Relance WhatsApp
                        </x-filament::button>

                        <div x-data="{ copied: false }">
                            <x-filament::button
                                color="gray"
                                size="xs"
                                type="button"
                                @click="navigator.clipboard.writeText('{{ $groupStat['mobile_url'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                icon="heroicon-o-clipboard-document"
                            >
                                <span x-show="!copied">Copier le lien</span>
                                <span x-show="copied" style="display: none; color: #10b981; font-weight: 700;">✓ Copié !</span>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <p style="font-size: 0.85rem; opacity: 0.7;">Aucun groupe actif. Veuillez initialiser la session à l'étape 1.</p>
            @endforelse
        </div>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- ÉTAPE 3 : Données NDS et présences Jeunesse+Sport --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-document-check"
        collapsible
    >
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>Étape 3 : Données NDS et présences Jeunesse+Sport</span>
                <x-filament::badge color="{{ $stats['nds_progress_percent'] >= 100 ? 'success' : 'gray' }}">
                    {{ $stats['nds_synced_count'] }} / {{ $stats['total_evaluations'] }} présences ({{ $stats['nds_progress_percent'] }}%)
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Téléversez le classeur officiel NDS Jeunesse+Sport (.xlsx) pour calculer automatiquement le critère C1 (Assiduité).
        </x-slot>

        <x-slot name="afterHeader">
            <div>
                {{ $this->getAction('import_nds') }}
            </div>
        </x-slot>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- ÉTAPE 4 : Arbitrage et sélection des athlètes --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-scale"
        collapsible
    >
        <x-slot name="heading">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>Étape 4 : Arbitrage et sélection</span>
                <x-filament::badge color="{{ $stats['arbitrated_count'] > 0 && $stats['pending_count'] === 0 ? 'success' : 'gray' }}">
                    {{ $stats['arbitrated_count'] }} / {{ $stats['total_evaluations'] }} arbitrés
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Calcule les moyennes pondérées, applique les bonus club (+0.75) et établit le classement et les décisions par groupe (Quotas / Note minimale).
        </x-slot>

        <x-slot name="afterHeader">
            <div>
                {{ $this->getAction('recalculate_arbitrate') }}
            </div>
        </x-slot>

        {{-- Répartition des décisions basées sur les Enums --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 8px;">
            <div style="padding: 14px; background: rgba(16, 185, 129, 0.08); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.25);">
                <div style="font-size: 0.8rem; font-weight: 600; color: #10b981;">🟢 {{ \App\Enums\EvaluationDecision::Retained->getLabel() }}</div>
                <div style="font-size: 1.5rem; font-weight: 800; margin-top: 4px;">{{ $stats['retained_count'] }}</div>
            </div>
            <div style="padding: 14px; background: rgba(245, 158, 11, 0.08); border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.25);">
                <div style="font-size: 0.8rem; font-weight: 600; color: #f59e0b;">🟡 {{ \App\Enums\EvaluationDecision::ProbationNeeded->getLabel() }}</div>
                <div style="font-size: 1.5rem; font-weight: 800; margin-top: 4px;">{{ $stats['probation_count'] }}</div>
            </div>
            <div style="padding: 14px; background: rgba(239, 68, 68, 0.08); border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.25);">
                <div style="font-size: 0.8rem; font-weight: 600; color: #ef4444;">🔴 {{ \App\Enums\EvaluationDecision::NotRetained->getLabel() }}</div>
                <div style="font-size: 1.5rem; font-weight: 800; margin-top: 4px;">{{ $stats['not_retained_count'] }}</div>
            </div>
            <div style="padding: 14px; background: rgba(156, 163, 175, 0.08); border-radius: 8px; border: 1px solid rgba(156, 163, 175, 0.2);">
                <div style="font-size: 0.8rem; font-weight: 600; opacity: 0.8;">⚪ {{ \App\Enums\EvaluationDecision::Pending->getLabel() }}</div>
                <div style="font-size: 1.5rem; font-weight: 800; margin-top: 4px;">{{ $stats['pending_count'] }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- ÉTAPE 5 : Livrables officiels et clôture --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-flag"
        collapsible
    >
        <x-slot name="heading">
            <span>Étape 5 : Livrables officiels et clôture</span>
        </x-slot>

        <x-slot name="description">
            Générez le procès-verbal officiel du comité, exportez les classeurs de résultats et verrouillez la session.
        </x-slot>

        <x-slot name="afterHeader">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                {{ $this->getAction('export_pdf_minutes') }}
                {{ $this->getAction('export_excel') }}
                {{ $this->getAction('toggle_session_closed') }}
            </div>
        </x-slot>
    </x-filament::section>

    {{-- ======================================================== --}}
    {{-- Grille détaillée des évaluations --}}
    {{-- ======================================================== --}}
    <x-filament::section
        icon="heroicon-o-table-cells"
        heading="Grille d'évaluation détaillée et arbitrage individuel"
        description="Consultez l'ensemble des notes par critère, ajustez directement les valeurs ou déclenchez les actions individuelles."
    >
        @livewire(\App\Filament\Resources\EvaluationSessions\RelationManagers\EvaluationsRelationManager::class, ['ownerRecord' => $session, 'pageClass' => static::class], key('evaluations-rm-' . $session->id))
    </x-filament::section>
</x-filament-panels::page>
