<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche d'entretien - {{ $evaluation->athlete->full_name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; margin: 20px; }
        .header { border-bottom: 2px solid #dc2626; padding-bottom: 12px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .subtitle { font-size: 11px; color: #64748b; margin-top: 3px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .grid { width: 100%; margin-bottom: 15px; }
        .grid td { vertical-align: top; padding: 4px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
        .table th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .table td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        .score-bad { color: #dc2626; font-weight: bold; }
        .score-good { color: #16a34a; font-weight: bold; }
        .box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .signatures td { width: 33%; border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 10px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">Club des Athlètes de Sion (CA Sion)</div>
                    <div class="subtitle">Fiche individuelle d'entretien et d'évaluation • Statuts Art. 3, 10 & 27</div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 14px; font-weight: bold; color: #dc2626;">{{ $evaluation->group->name }}</div>
                    <div style="font-size: 10px; color: #64748b;">Date : {{ date('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Identité de l'athlète -->
    <div class="box">
        <table class="grid">
            <tr>
                <td><strong>Athlète :</strong> {{ $evaluation->athlete->full_name }}</td>
                <td><strong>Année de naissance :</strong> {{ $evaluation->athlete->birth_year }}</td>
                <td><strong>Licence :</strong> {{ $evaluation->athlete->license_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Période d'évaluation :</strong> {{ $evaluation->start_date->format('d/m/Y') }} au {{ $evaluation->end_date->format('d/m/Y') }} ({{ $evaluation->weeks_count }} sem.)</td>
                <td><strong>Contexte :</strong> {{ $evaluation->context->getLabel() }}</td>
                <td>
                    <strong>Décision actuelle :</strong>
                    @if($evaluation->decision === \App\Enums\EvaluationDecision::Retained)
                        <span class="badge badge-success">Retenu</span>
                    @elseif($evaluation->decision === \App\Enums\EvaluationDecision::ProbationNeeded)
                        <span class="badge badge-warning">Sursis probatoire (2 sem.)</span>
                    @elseif($evaluation->decision === \App\Enums\EvaluationDecision::NotRetained)
                        <span class="badge badge-danger">Non retenu</span>
                    @else
                        <span class="badge">En attente</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Bilan des critères -->
    <table class="table">
        <thead>
            <tr>
                <th>Critère</th>
                <th style="width: 15%;">Source</th>
                <th style="width: 12%; text-align: center;">Poids</th>
                <th style="width: 15%; text-align: center;">Note obtenue</th>
                <th style="width: 25%;">Observations</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>C1 : Assiduité</strong></td>
                <td>NDS Jeunesse+Sport</td>
                <td style="text-align: center;">20%</td>
                <td style="text-align: center;">
                    @if($evaluation->is_injured)
                        <span style="color: #d97706; font-style: italic;">Neutralisé (Blessure)</span>
                    @elseif($evaluation->c1_score !== null)
                        <span class="{{ $evaluation->c1_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c1_score, 2) }} / 10</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $evaluation->real_attendances ?? 0 }} présences sur {{ $evaluation->sessions_per_week * $evaluation->weeks_count }} prévues</td>
            </tr>
            <tr>
                <td><strong>C2 : Ponctualité</strong></td>
                <td>Pointage entraîneur</td>
                <td style="text-align: center;">5%</td>
                <td style="text-align: center;" class="{{ $evaluation->c2_score < 6 ? 'score-bad' : 'score-good' }}">
                    {{ number_format($evaluation->c2_score, 2) }} / 10
                </td>
                <td>{{ $evaluation->lateness_count }} retards (-1.5 pt/retard)</td>
            </tr>
            <tr>
                <td><strong>C3 : Compétitions</strong></td>
                <td>Tiiva Club</td>
                <td style="text-align: center;">15%</td>
                <td style="text-align: center;">
                    @if($evaluation->is_injured)
                        <span style="color: #d97706; font-style: italic;">Neutralisé (Blessure)</span>
                    @elseif($evaluation->c3_score !== null)
                        <span class="{{ $evaluation->c3_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c3_score, 2) }} / 10</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $evaluation->competitions_done }} effectuées / {{ $evaluation->competitions_planned }} prévues</td>
            </tr>
            <tr>
                <td><strong>C4 : Implication</strong></td>
                <td>Entraîneur</td>
                <td style="text-align: center;">15%</td>
                <td style="text-align: center;" class="{{ $evaluation->c4_commitment !== null && $evaluation->c4_commitment < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c4_commitment !== null ? number_format($evaluation->c4_commitment, 1) . ' / 10' : 'Non noté' }}
                </td>
                <td>Investissement et dynamisme aux entraînements</td>
            </tr>
            <tr>
                <td><strong>C5 : Comportement</strong></td>
                <td>Entraîneur</td>
                <td style="text-align: center;">15%</td>
                <td style="text-align: center;" class="{{ $evaluation->c5_behavior !== null && $evaluation->c5_behavior < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c5_behavior !== null ? number_format($evaluation->c5_behavior, 1) . ' / 10' : 'Non noté' }}
                </td>
                <td>Esprit sportif, respect des pairs et encadrement</td>
            </tr>
            <tr>
                <td><strong>C6 : Niveau athlétique</strong></td>
                <td>Performances</td>
                <td style="text-align: center;">10%</td>
                <td style="text-align: center;" class="score-good">
                    {{ $evaluation->c6_score !== null ? number_format($evaluation->c6_score, 2) . ' / 10' : 'Non défini' }}
                </td>
                <td>Palier : {{ $evaluation->c6_level?->getLabel() ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>C7 : Progression</strong></td>
                <td>Entraîneur</td>
                <td style="text-align: center;">10%</td>
                <td style="text-align: center;" class="{{ $evaluation->c7_progress !== null && $evaluation->c7_progress < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c7_progress !== null ? number_format($evaluation->c7_progress, 1) . ' / 10' : 'Non noté' }}
                </td>
                <td>Évolution technique et chronométrique</td>
            </tr>
            <tr>
                <td><strong>C8 : Hygiène de vie</strong></td>
                <td>Entraîneur</td>
                <td style="text-align: center;">5%</td>
                <td style="text-align: center;" class="{{ $evaluation->c8_sports_hygiene !== null && $evaluation->c8_sports_hygiene < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c8_sports_hygiene !== null ? number_format($evaluation->c8_sports_hygiene, 1) . ' / 10' : 'Non noté' }}
                </td>
                <td>Sommeil, nutrition, équipement adapté</td>
            </tr>
            <tr>
                <td><strong>C9 : Bénévolat parents</strong></td>
                <td>Administration</td>
                <td style="text-align: center;">5%</td>
                <td style="text-align: center;">
                    @if($evaluation->c9_score !== null)
                        <span class="{{ $evaluation->c9_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c9_score, 2) }} / 10</span>
                    @else
                        <span style="color: #64748b; font-style: italic;">Neutralisé (&gt; {{ $evaluation->group->max_volunteering_age }} ans)</span>
                    @endif
                </td>
                <td>{{ $evaluation->parent_volunteering_count }} participations bénévoles</td>
            </tr>
        </tbody>
    </table>

    <!-- Synthèse et note finale -->
    <div class="box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div><strong>Moyenne pondérée de base :</strong> {{ $evaluation->base_average !== null ? number_format($evaluation->base_average, 2) . ' / 10' : '-' }}</div>
                    <div><strong>Bonification engagement club :</strong> {{ $evaluation->has_club_engagement ? '+0.75 pt' : '0.00 pt' }}</div>
                    @if($evaluation->rank !== null)
                        <div><strong>Rang dans le groupe :</strong> {{ $evaluation->rank }} / {{ $evaluation->group->athletes()->count() }}</div>
                    @endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <div style="font-size: 12px; font-weight: bold; color: #64748b;">NOTE GLOBALE FINALE</div>
                    <div style="font-size: 26px; font-weight: 900; color: {{ ($evaluation->final_score ?? 0) >= 6.5 ? '#16a34a' : '#dc2626' }};">
                        {{ $evaluation->final_score !== null ? number_format($evaluation->final_score, 2) : '-' }} <span style="font-size: 14px; font-weight: normal; color: #64748b;">/ 10</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($evaluation->coach_notes)
        <div class="box">
            <strong>Remarques de l'entraîneur :</strong>
            <p style="margin-top: 4px; font-style: italic;">« {{ $evaluation->coach_notes }} »</p>
        </div>
    @endif

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>Signature de l'athlète</td>
            <td>Signature des parents / représentants</td>
            <td>Signature du Responsable technique</td>
        </tr>
    </table>
</body>
</html>
