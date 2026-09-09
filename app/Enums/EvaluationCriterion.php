<?php

namespace App\Enums;

use App\Models\Evaluation;
use Carbon\Carbon;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum EvaluationCriterion: string implements HasColor, HasDescription, HasLabel
{
    case C1_Attendance = 'c1';
    case C2_Punctuality = 'c2';
    case C3_Competitions = 'c3';
    case C4_Commitment = 'c4';
    case C5_Behavior = 'c5';
    case C6_Performance = 'c6';
    case C7_Progress = 'c7';
    case C8_SportsHygiene = 'c8';
    case C9_Volunteering = 'c9';

    /**
     * Code abrégé du critère (ex. C1, C2).
     */
    public function code(): string
    {
        return match ($this) {
            self::C1_Attendance => 'C1',
            self::C2_Punctuality => 'C2',
            self::C3_Competitions => 'C3',
            self::C4_Commitment => 'C4',
            self::C5_Behavior => 'C5',
            self::C6_Performance => 'C6',
            self::C7_Progress => 'C7',
            self::C8_SportsHygiene => 'C8',
            self::C9_Volunteering => 'C9',
        };
    }

    /**
     * Intitulé officiel complet du critère (conforme aux statuts et directives du CA Sion).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::C1_Attendance => 'Assiduité aux entraînements',
            self::C2_Punctuality => 'Ponctualité (retards)',
            self::C3_Competitions => 'Nombre compétitions',
            self::C4_Commitment => 'Implication et rigueur',
            self::C5_Behavior => 'Comportement et esprit d’équipe',
            self::C6_Performance => 'Niveau et potentiel',
            self::C7_Progress => 'Progression',
            self::C8_SportsHygiene => 'Hygiène et environnement',
            self::C9_Volunteering => 'Engagement bénévole familial',
        };
    }

    /**
     * Libellé court et condensé pour les en-têtes de tableaux et badges.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::C1_Attendance => 'Assiduité',
            self::C2_Punctuality => 'Ponctualité',
            self::C3_Competitions => 'Compétitions',
            self::C4_Commitment => 'Implication',
            self::C5_Behavior => 'Comportement',
            self::C6_Performance => 'Niveau',
            self::C7_Progress => 'Progression',
            self::C8_SportsHygiene => 'Hygiène',
            self::C9_Volunteering => 'Bénévolat',
        };
    }

    /**
     * Directive opérationnelle et description détaillée (affichée en infobulle et fiche PDF).
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::C1_Attendance => 'Présences réelles NDS J+S rapportées au volume attendu (séances prévues sur la période). Neutralisé sur blessure.',
            self::C2_Punctuality => 'Note de base 10.0 réduite par déduction forfaitaire pour chaque retard non excusé consigné.',
            self::C3_Competitions => 'Taux de participation aux compétitions rapporté aux objectifs fixés. Neutralisé sur blessure.',
            self::C4_Commitment => 'Qualité d’écoute des consignes, rigueur, concentration et intensité déployée lors des entraînements.',
            self::C5_Behavior => 'Respect des camarades, des entraîneurs, des règles de vie et soin apporté aux installations et au matériel.',
            self::C6_Performance => 'Niveau athlétique validé sur barème par paliers (Régional, Cantonal, Romand, National). Pas de malus.',
            self::C7_Progress => 'Évolution technique, maîtrise gestuelle et progression athlétique constatées sur la période.',
            self::C8_SportsHygiene => 'Place du sport dans la vie privée, hygiène de vie et qualité de l\'environnement familial.',
            self::C9_Volunteering => 'Participation active des parents aux manifestations organisées par le club.',
        };
    }

    /**
     * Nom de la colonne dans le modèle Evaluation où le score (0-10) est stocké.
     */
    public function scoreColumn(): string
    {
        return match ($this) {
            self::C1_Attendance => 'c1_score',
            self::C2_Punctuality => 'c2_score',
            self::C3_Competitions => 'c3_score',
            self::C4_Commitment => 'c4_commitment',
            self::C5_Behavior => 'c5_behavior',
            self::C6_Performance => 'c6_score',
            self::C7_Progress => 'c7_progress',
            self::C8_SportsHygiene => 'c8_sports_hygiene',
            self::C9_Volunteering => 'c9_score',
        };
    }

    /**
     * Nom de la colonne de saisie brute ou source dans le modèle Evaluation (si applicable).
     */
    public function rawInputColumn(): ?string
    {
        return match ($this) {
            self::C1_Attendance => 'real_attendances',
            self::C2_Punctuality => 'lateness_count',
            self::C3_Competitions => 'competitions_done',
            self::C6_Performance => 'c6_level',
            self::C9_Volunteering => 'parent_volunteering_count',
            default => null,
        };
    }

    /**
     * Pondération par défaut configurée (ex. 0.20 pour 20%).
     */
    public function defaultWeight(): float
    {
        return (float) config("evaluation.weights.{$this->value}", match ($this) {
            self::C1_Attendance => 0.20,
            self::C2_Punctuality => 0.05,
            self::C3_Competitions => 0.15,
            self::C4_Commitment => 0.15,
            self::C5_Behavior => 0.15,
            self::C6_Performance => 0.10,
            self::C7_Progress => 0.10,
            self::C8_SportsHygiene => 0.05,
            self::C9_Volunteering => 0.05,
        });
    }

    /**
     * Indique si le critère est une appréciation qualitative saisie par l'entraîneur (0 à 10).
     */
    public function isQualitative(): bool
    {
        return in_array($this, self::qualitativeCases(), true);
    }

    /**
     * Indique si le critère est calculé automatiquement à partir de données objectives.
     */
    public function isCalculatedAutomatically(): bool
    {
        return ! $this->isQualitative();
    }

    /**
     * Indique si le critère doit être neutralisé en cas de blessure de l'athlète.
     */
    public function isNeutralizedOnInjury(): bool
    {
        return in_array($this, [self::C1_Attendance, self::C3_Competitions], true);
    }

    /**
     * Âge maximum au-delà duquel ce critère n'est plus applicable.
     */
    public function maxApplicableAge(): ?int
    {
        return match ($this) {
            self::C9_Volunteering => (int) config('evaluation.defaults.max_volunteering_age', 17),
            default => null,
        };
    }

    /**
     * Échelle de notation (bornes, pas et valeur par défaut).
     */
    public function scale(): array
    {
        return match ($this) {
            self::C4_Commitment, self::C5_Behavior, self::C7_Progress, self::C8_SportsHygiene => [
                'min' => 0.0,
                'max' => 10.0,
                'step' => 0.5,
                'default' => 5.0,
            ],
            default => [
                'min' => 0.0,
                'max' => 10.0,
                'step' => 0.1,
                'default' => 0.0,
            ],
        };
    }

    /**
     * Couleur de badge pour Filament.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::C1_Attendance, self::C3_Competitions => 'info',
            self::C2_Punctuality => 'warning',
            self::C4_Commitment, self::C5_Behavior, self::C7_Progress, self::C8_SportsHygiene => 'primary',
            self::C6_Performance => 'success',
            self::C9_Volunteering => 'gray',
        };
    }

    /**
     * Liste des critères qualitatifs que l'entraîneur note directement.
     *
     * @return array<self>
     */
    public static function qualitativeCases(): array
    {
        return [
            self::C4_Commitment,
            self::C5_Behavior,
            self::C7_Progress,
            self::C8_SportsHygiene,
        ];
    }

    /**
     * Liste des noms de champs qualitatifs modifiables par l'entraîneur.
     *
     * @return array<string>
     */
    public static function qualitativeFields(): array
    {
        return array_map(fn (self $c) => $c->scoreColumn(), self::qualitativeCases());
    }

    /**
     * Calcule la note individuelle (sur 10.0) de ce critère pour l'évaluation donnée.
     */
    public function calculateScore(Evaluation $evaluation): ?float
    {
        return match ($this) {
            self::C1_Attendance => (function () use ($evaluation): ?float {
                if ($evaluation->is_injured || $evaluation->real_attendances === null) {
                    return null;
                }
                $expected = (int) $evaluation->sessions_per_week * (int) $evaluation->weeks_count;
                if ($expected <= 0) {
                    return null;
                }

                return round(min(10.0, max(0.0, ($evaluation->real_attendances / $expected) * 10.0)), 2);
            })(),

            self::C2_Punctuality => (function () use ($evaluation): float {
                $retardDeduction = (float) config('evaluation.penalties.retard_deduction', 1.5);

                return round(max(0.0, 10.0 - ($evaluation->lateness_count * $retardDeduction)), 2);
            })(),

            self::C3_Competitions => (function () use ($evaluation): ?float {
                if ($evaluation->is_injured) {
                    return null;
                }
                if ($evaluation->competitions_planned <= 0) {
                    return null;
                }

                return round(min(10.0, max(0.0, ($evaluation->competitions_done / $evaluation->competitions_planned) * 10.0)), 2);
            })(),

            self::C4_Commitment => $evaluation->c4_commitment !== null ? (float) $evaluation->c4_commitment : null,

            self::C5_Behavior => $evaluation->c5_behavior !== null ? (float) $evaluation->c5_behavior : null,

            self::C6_Performance => (function () use ($evaluation): ?float {
                if ($evaluation->c6_level === null) {
                    return null;
                }
                $level = $evaluation->c6_level;
                $levelEnum = $level instanceof AthleticLevel
                    ? $level
                    : (is_string($level) ? AthleticLevel::tryFrom($level) : null);

                return $levelEnum?->score();
            })(),

            self::C7_Progress => $evaluation->c7_progress !== null ? (float) $evaluation->c7_progress : null,

            self::C8_SportsHygiene => $evaluation->c8_sports_hygiene !== null ? (float) $evaluation->c8_sports_hygiene : null,

            self::C9_Volunteering => (function () use ($evaluation): ?float {
                $evaluation->loadMissing(['athlete', 'group']);
                $athlete = $evaluation->athlete;
                $group = $evaluation->group;

                $startYear = $evaluation->start_date ? (int) Carbon::parse($evaluation->start_date)->year : (int) date('Y');
                $maxVolunteeringAge = $group?->max_volunteering_age ?? (int) config('evaluation.defaults.max_volunteering_age', 17);
                $athleteAge = $athlete ? ($startYear - (int) $athlete->birth_year) : 0;

                if ($athlete && $athleteAge <= $maxVolunteeringAge) {
                    $required = $group?->required_volunteering_count ?? (int) config('evaluation.defaults.required_volunteering_count', 2);
                    if ($required > 0) {
                        $count = (int) $evaluation->parent_volunteering_count;
                        if ($count >= $required) {
                            $score = min(10.0, 7.5 + ($count - $required) * 1.25);
                        } else {
                            $score = max(0.0, ($count / $required) * 5.0);
                        }

                        return round($score, 2);
                    }
                }

                return null;
            })(),
        };
    }
}
