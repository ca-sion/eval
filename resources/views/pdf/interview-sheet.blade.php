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
                    <div class="title">CA Sion</div>
                    <div class="subtitle">Fiche individuelle d'entretien et d'évaluation • Statuts Art. 3, 10 et 27</div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 14px; font-weight: bold; color: #dc2626;">{{ $evaluation->group->name }}</div>
                    <div style="font-size: 10px; color: #64748b;">{{ date('d.m.Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Identité de l'athlète -->
    <div class="box">
        <table class="grid">
            <tr>
                <td><strong>Athlète :</strong> {{ $evaluation->athlete->full_name }}</td>
                <td><strong>Année :</strong> {{ $evaluation->athlete->birth_year }}</td>
                <td> </td>
            </tr>
            <tr>
                <td><strong>Période d'évaluation :</strong><br>{{ $evaluation->start_date->format('d.m.Y') }} au {{ $evaluation->end_date->format('d.m.Y') }} ({{ $evaluation->weeks_count }} sem.)</td>
                <td><strong>Contexte :</strong><br>{{ $evaluation->context->getLabel() }}</td>
                <td>
                    <strong>Décision actuelle :</strong><br>
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
                <th style="width: 20%;">Critère</th>
                <th style="width: 15%;">Source</th>
                <th style="width: 10%; text-align: center;">Poids</th>
                <th style="width: 10%; text-align: center;">Note</th>
                <th style="width: 45%;">Observations</th>
            </tr>
        </thead>
        <tbody>
            @php
                $c1 = \App\Enums\EvaluationCriterion::C1_Attendance;
                $c2 = \App\Enums\EvaluationCriterion::C2_Punctuality;
                $c3 = \App\Enums\EvaluationCriterion::C3_Competitions;
                $c4 = \App\Enums\EvaluationCriterion::C4_Commitment;
                $c5 = \App\Enums\EvaluationCriterion::C5_Behavior;
                $c6 = \App\Enums\EvaluationCriterion::C6_Performance;
                $c7 = \App\Enums\EvaluationCriterion::C7_Progress;
                $c8 = \App\Enums\EvaluationCriterion::C8_Environment;
                $c9 = \App\Enums\EvaluationCriterion::C9_Volunteering;
            @endphp
            <tr>
                <td><strong>{{ $c1->code() }} : {{ $c1->shortLabel() }}</strong></td>
                <td>NDS J+S</td>
                <td style="text-align: center;">{{ number_format($c1->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;">
                    @if($evaluation->is_injured)
                        <span style="color: #d97706; font-style: italic;">Blessure</span>
                    @elseif($evaluation->c1_score !== null)
                        <span class="{{ $evaluation->c1_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c1_score, 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $evaluation->real_attendances ?? 0 }} / {{ $evaluation->sessions_per_week * $evaluation->weeks_count }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c2->code() }} : {{ $c2->shortLabel() }}</strong></td>
                <td>Entraîneur</td>
                <td style="text-align: center;">{{ number_format($c2->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="{{ $evaluation->c2_score < 6 ? 'score-bad' : 'score-good' }}">
                    {{ number_format($evaluation->c2_score, 2) }}
                </td>
                <td>{{ $evaluation->lateness_count }} {{ str('retards')->plural($evaluation->lateness_count) }} (-{{ number_format(config('evaluation.penalties.retard_deduction', 0.3), 1) }}/retard)</td>
            </tr>
            <tr>
                <td><strong>{{ $c3->code() }} : {{ $c3->shortLabel() }}</strong></td>
                <td>Tiiva</td>
                <td style="text-align: center;">{{ number_format($c3->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;">
                    @if($evaluation->is_injured)
                        <span style="color: #d97706; font-style: italic;">Blessure</span>
                    @elseif($evaluation->c3_score !== null)
                        <span class="{{ $evaluation->c3_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c3_score, 1) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $evaluation->competitions_done }} / {{ $evaluation->competitions_planned }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c4->code() }} : {{ $c4->shortLabel() }}</strong></td>
                <td>Observation</td>
                <td style="text-align: center;">{{ number_format($c4->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="{{ $evaluation->c4_commitment !== null && $evaluation->c4_commitment < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c4_commitment !== null ? number_format($evaluation->c4_commitment, 1) . '' : 'Non noté' }}
                </td>
                <td>{{ $c4->getDescription() }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c5->code() }} : {{ $c5->shortLabel() }}</strong></td>
                <td>Observation</td>
                <td style="text-align: center;">{{ number_format($c5->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="{{ $evaluation->c5_behavior !== null && $evaluation->c5_behavior < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c5_behavior !== null ? number_format($evaluation->c5_behavior, 1) . '' : 'Non noté' }}
                </td>
                <td>{{ $c5->getDescription() }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c6->code() }} : {{ $c6->shortLabel() }}</strong></td>
                <td>Performances</td>
                <td style="text-align: center;">{{ number_format($c6->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="score-good">
                    {{ $evaluation->c6_score !== null ? number_format($evaluation->c6_score, 1) . '' : 'Non défini' }}
                </td>
                <td>{{ $evaluation->c6_level?->getLabel() ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c7->code() }} : {{ $c7->shortLabel() }}</strong></td>
                <td>Observation</td>
                <td style="text-align: center;">{{ number_format($c7->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="{{ $evaluation->c7_progress !== null && $evaluation->c7_progress < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c7_progress !== null ? number_format($evaluation->c7_progress, 1) . '' : 'Non noté' }}
                </td>
                <td>{{ $c7->getDescription() }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c8->code() }} : {{ $c8->shortLabel() }}</strong></td>
                <td>Observation</td>
                <td style="text-align: center;">{{ number_format($c8->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;" class="{{ $evaluation->c8_environment !== null && $evaluation->c8_environment < 6 ? 'score-bad' : 'score-good' }}">
                    {{ $evaluation->c8_environment !== null ? number_format($evaluation->c8_environment, 1) . '' : 'Non noté' }}
                </td>
                <td>{{ $c8->getDescription() }}</td>
            </tr>
            <tr>
                <td><strong>{{ $c9->code() }} : {{ $c9->shortLabel() }}</strong></td>
                <td>Tiiva</td>
                <td style="text-align: center;">{{ number_format($c9->defaultWeight() * 100, 0) }}%</td>
                <td style="text-align: center;">
                    @if($evaluation->c9_score !== null)
                        <span class="{{ $evaluation->c9_score < 6 ? 'score-bad' : 'score-good' }}">{{ number_format($evaluation->c9_score, 1) }}</span>
                    @else
                        <span style="color: #64748b; font-style: italic;">Neutralisé</span>
                    @endif
                </td>
                <td>{{ $evaluation->parent_volunteering_count }} participations</td>
            </tr>
        </tbody>
    </table>

    <!-- Synthèse et note finale -->
    <div class="box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div><strong>Moyenne pondérée :</strong> {{ $evaluation->base_average !== null ? number_format($evaluation->base_average, 2) . ' / 10' : '-' }}</div>
                    <div><strong>Bonus engagement club :</strong> {{ $evaluation->has_club_engagement ? '+0.75 pt' : '0.00 pt' }}</div>
                    @if($evaluation->rank !== null)
                        <div><strong>Rang dans le groupe :</strong> {{ $evaluation->rank }} / {{ $evaluation->group->athletes()->count() }}</div>
                    @endif
                </td>
                <td style="width: 50%; text-align: right;">
                    <div style="font-size: 12px; font-weight: bold; color: #64748b;">Note</div>
                    <div style="font-size: 26px; font-weight: bold; color: {{ ($evaluation->final_score ?? 0) >= 5.00 ? '#16a34a' : '#dc2626' }};">
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
     <br>
     <br>
    <table class="signatures">
        <tr>
            <td>Signature de l'athlète</td>
            <td>Signature du représentant légal</td>
            <td>Signature du Chef technique</td>
        </tr>
    </table>
</body>
</html>
