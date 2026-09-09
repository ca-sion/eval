<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Procès-Verbal Officiel des Sélections - {{ $session->title }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; margin: 25px; }
        .page-break { page-break-before: always; }
        .header { border-bottom: 2px solid #dc2626; padding-bottom: 15px; margin-bottom: 25px; }
        .title { font-size: 20px; font-weight: 800; color: #0f172a; text-transform: uppercase; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 4px; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
        .table th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .table td { border: 1px solid #cbd5e1; padding: 7px 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        .signatures { width: 100%; margin-top: 50px; border-collapse: collapse; }
        .signatures td { width: 50%; border-top: 1px solid #94a3b8; padding-top: 10px; font-size: 11px; color: #475569; text-align: center; }
        .defective { color: #dc2626; font-size: 10px; margin-top: 3px; }
    </style>
</head>
<body>

    <!-- PAGE DE GARDE / SYNTHÈSE GÉNÉRALE -->
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">Procès-Verbal Officiel de Sélection</div>
                    <div class="subtitle">Club des Athlètes de Sion (CA Sion) • Séances du Comité • Statuts Art. 3, 10 & 27</div>
                </td>
                <td class="text-right">
                    <div style="font-size: 14px; font-weight: bold; color: #dc2626;">SESSION {{ $session->title }}</div>
                    <div style="font-size: 10px; color: #64748b;">Édité le {{ date('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <strong>Période d'observation officielle :</strong> du {{ $session->start_date->format('d/m/Y') }} au {{ $session->end_date->format('d/m/Y') }} ({{ $session->weeks_count }} semaines).<br>
        <strong>Objet :</strong> Ratification des admissions, attribution des places par quota/seuil, confirmation des sursis probatoires de 2 semaines (Art. 10.5) et motifs d'exclusion/non-admission (Art. 27).
    </div>

    <h3 style="text-transform: uppercase; font-size: 13px; color: #0f172a; margin-top: 25px;">Tableau récapitulatif par groupe d'entraînement</h3>

    <table class="table">
        <thead>
            <tr>
                <th>Groupe</th>
                <th class="text-center" style="width: 15%;">Mode d'arbitrage</th>
                <th class="text-center" style="width: 12%;">Effectif total</th>
                <th class="text-center" style="width: 15%; background-color: #dcfce7;">Retenus</th>
                <th class="text-center" style="width: 18%; background-color: #fef3c7;">Sursis probatoires</th>
                <th class="text-center" style="width: 15%; background-color: #fee2e2;">Non retenus</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAthletes = 0;
                $totalRetained = 0;
                $totalProbation = 0;
                $totalNotRetained = 0;
            @endphp
            @foreach($groupsData as $data)
                @php
                    $totalAthletes += $data['total'];
                    $totalRetained += $data['retained'];
                    $totalProbation += $data['probation'];
                    $totalNotRetained += $data['not_retained'];
                @endphp
                <tr>
                    <td><strong>{{ $data['group']->name }}</strong></td>
                    <td class="text-center">{{ $data['group']->arbitration_mode->getLabel() }}</td>
                    <td class="text-center"><strong>{{ $data['total'] }}</strong></td>
                    <td class="text-center" style="color: #15803d; font-weight: bold;">{{ $data['retained'] }}</td>
                    <td class="text-center" style="color: #b45309; font-weight: bold;">{{ $data['probation'] }}</td>
                    <td class="text-center" style="color: #b91c1c; font-weight: bold;">{{ $data['not_retained'] }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td>TOTAL GÉNÉRAL DU CLUB</td>
                <td class="text-center">-</td>
                <td class="text-center">{{ $totalAthletes }}</td>
                <td class="text-center" style="color: #15803d;">{{ $totalRetained }}</td>
                <td class="text-center" style="color: #b45309;">{{ $totalProbation }}</td>
                <td class="text-center" style="color: #b91c1c;">{{ $totalNotRetained }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <strong>Le Responsable Technique</strong><br>
                <span style="font-size: 9px; color: #94a3b8;">Visa et propositions transmises au Comité</span><br><br><br>
                _______________________________________
            </td>
            <td>
                <strong>Pour le Comité du CA Sion (Président)</strong><br>
                <span style="font-size: 9px; color: #94a3b8;">Ratification officielle des décisions</span><br><br><br>
                _______________________________________
            </td>
        </tr>
    </table>

    <!-- PAGES DÉTAILLÉES PAR GROUPE -->
    @foreach($groupsData as $data)
        <div class="page-break"></div>

        <div class="header">
            <table style="width: 100%;">
                <tr>
                    <td>
                        <div class="title">{{ $data['group']->name }}</div>
                        <div class="subtitle">Procès-verbal de sélection • Session {{ $session->title }}</div>
                    </td>
                    <td class="text-right">
                        <span class="badge badge-success">{{ $data['retained'] }} Retenus</span>
                        <span class="badge badge-warning">{{ $data['probation'] }} Sursis</span>
                        <span class="badge badge-danger">{{ $data['not_retained'] }} Non retenus</span>
                    </td>
                </tr>
            </table>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 8%; text-align: center;">Rang</th>
                    <th style="width: 30%;">Nom & Prénom</th>
                    <th style="width: 12%; text-align: center;">Année</th>
                    <th style="width: 15%; text-align: center;">Note (/10)</th>
                    <th style="width: 15%; text-align: center;">Décision</th>
                    <th style="width: 20%;">Détails & Motifs</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['evaluations'] as $eval)
                    <tr>
                        <td class="text-center font-bold">{{ $eval->rank ?? '-' }}</td>
                        <td>
                            <strong>{{ $eval->athlete->last_name }}</strong> {{ $eval->athlete->first_name }}
                            @if($eval->is_injured)
                                <div style="font-size: 9px; color: #d97706;">[Blessé durant la session]</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $eval->athlete->birth_year }}</td>
                        <td class="text-center font-bold">
                            {{ $eval->final_score !== null ? number_format($eval->final_score, 2) : '-' }}
                        </td>
                        <td class="text-center">
                            @if($eval->decision === \App\Enums\EvaluationDecision::Retained)
                                <span class="badge badge-success">Retenu</span>
                            @elseif($eval->decision === \App\Enums\EvaluationDecision::ProbationNeeded)
                                <span class="badge badge-warning">Sursis (2 sem.)</span>
                            @elseif($eval->decision === \App\Enums\EvaluationDecision::NotRetained)
                                <span class="badge badge-danger">Non retenu</span>
                            @else
                                <span class="badge">En attente</span>
                            @endif
                        </td>
                        <td>
                            @if($eval->decision === \App\Enums\EvaluationDecision::ProbationNeeded)
                                <div class="defective">
                                    <strong>Critères sous le seuil :</strong><br>
                                    @if($eval->c1_score !== null && $eval->c1_score < 6)
                                        • Assiduité : {{ number_format($eval->c1_score, 1) }}/10<br>
                                    @endif
                                    @if($eval->c2_score !== null && $eval->c2_score < 6)
                                        • Ponctualité : {{ number_format($eval->c2_score, 1) }}/10<br>
                                    @endif
                                    @if($eval->c3_score !== null && $eval->c3_score < 6)
                                        • Compétitions : {{ number_format($eval->c3_score, 1) }}/10<br>
                                    @endif
                                    @if($eval->c4_commitment !== null && $eval->c4_commitment < 6)
                                        • Implication : {{ number_format($eval->c4_commitment, 1) }}/10<br>
                                    @endif
                                    @if($eval->c5_behavior !== null && $eval->c5_behavior < 6)
                                        • Comportement : {{ number_format($eval->c5_behavior, 1) }}/10<br>
                                    @endif
                                    @if($eval->c7_progress !== null && $eval->c7_progress < 6)
                                        • Progression : {{ number_format($eval->c7_progress, 1) }}/10<br>
                                    @endif
                                    <em>Sursis probatoire 2 sem. (Art. 10.5)</em>
                                </div>
                            @elseif($eval->decision === \App\Enums\EvaluationDecision::NotRetained)
                                <div style="font-size: 10px; color: #b91c1c;">
                                    Motif : Note finale insuffisante ({{ number_format($eval->final_score, 2) }}/10) sous le seuil d'éligibilité. Exclusion formelle Art. 27.
                                </div>
                            @else
                                <span style="color: #64748b; font-size: 10px;">Critères validés</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>
</html>
