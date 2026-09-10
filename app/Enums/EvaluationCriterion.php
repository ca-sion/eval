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
    case C8_Environment = 'c8';
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
            self::C8_Environment => 'C8',
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
            self::C8_Environment => 'Hygiène de vie et environnement',
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
            self::C8_Environment => 'Environnement',
            self::C9_Volunteering => 'Bénévolat',
        };
    }

    /**
     * Directive opérationnelle et description détaillée (affichée en infobulle et fiche PDF).
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::C1_Attendance => 'Présences réelles NDS J+S rapportées au volume de séances prévues sur la période. Neutralisé si blessure.',
            self::C2_Punctuality => 'Note de base 6.0 (standard) réduite de 0.3 pt pour chaque retard consigné.',
            self::C3_Competitions => 'Nombre de participation aux compétitions rapporté aux nombre de compétitions fixés. Neutralisé si blessure.',
            self::C4_Commitment => 'Qualité d’écoute des consignes, rigueur, concentration et intensité déployée lors des entraînements.',
            self::C5_Behavior => 'Respect des camarades, des entraîneurs, des règles de vie et soin apporté aux installations et au matériel.',
            self::C6_Performance => 'Niveau en compétition (cantonal, romand, national, international). Il n\'y a pas de malus. Ce critère apporte quoi qu\'il en soit un bonus.',
            self::C7_Progress => 'Évolution technique, maîtrise gestuelle et progression athlétique constatées sur la période.',
            self::C8_Environment => 'Place du sport dans la vie privée, hygiène de vie (sommeil, récupération, gestion des excès/fêtes/alcool chez les adultes, plans hors club respectés, écoute du corps, régulation des courses le week-end) et qualité de l\'environnement familial (soutien des parents sans omniprésence étouffante, suivi médical/physio sérieux si blessé, adhésion à la philosophie du club plutôt que discours contraire au coach).',
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
            self::C8_Environment => 'c8_environment',
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
            self::C8_Environment => 0.05,
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
     * Alias pour compatibilité.
     */
    public function isCoachRated(): bool
    {
        return $this->isQualitative();
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
     * Indique si le critère est applicable et actif pour l'évaluation donnée.
     */
    public function isApplicable(Evaluation $evaluation): bool
    {
        if ($evaluation->is_injured && $this->isNeutralizedOnInjury()) {
            return false;
        }

        if ($this === self::C9_Volunteering) {
            $evaluation->loadMissing(['athlete', 'group']);
            $maxAge = $evaluation->group?->max_volunteering_age ?? (int) config('evaluation.defaults.max_volunteering_age', 17);
            $startYear = $evaluation->start_date ? (int) Carbon::parse($evaluation->start_date)->year : (int) date('Y');
            $athleteAge = $evaluation->athlete ? ($startYear - (int) $evaluation->athlete->birth_year) : 0;

            if ($athleteAge > $maxAge) {
                return false;
            }
        }

        return true;
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
     *
     * @return array{min: float, max: float, step: float, default: float}
     */
    public function scale(): array
    {
        return match ($this) {
            self::C4_Commitment, self::C5_Behavior, self::C7_Progress, self::C8_Environment => [
                'min' => 0.0,
                'max' => 10.0,
                'step' => 1.0,
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
            self::C4_Commitment, self::C5_Behavior, self::C7_Progress, self::C8_Environment => 'primary',
            self::C6_Performance => 'success',
            self::C9_Volunteering => 'gray',
        };
    }

    /**
     * Couleur Tailwind pour l'interface coach.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::C1_Attendance => 'blue',
            self::C2_Punctuality => 'emerald',
            self::C3_Competitions => 'indigo',
            self::C4_Commitment => 'amber',
            self::C5_Behavior => 'teal',
            self::C6_Performance => 'violet',
            self::C7_Progress => 'cyan',
            self::C8_Environment => 'rose',
            self::C9_Volunteering => 'slate',
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
            self::C8_Environment,
        ];
    }

    /**
     * Alias pour compatibilité.
     *
     * @return array<self>
     */
    public static function coachRatedCriteria(): array
    {
        return self::qualitativeCases();
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
     * Grille de référence et étalonnage officiel des notes de 0 à 10 pour les entraîneurs.
     *
     * @return array<int, array{score: int, tier: string, tier_label: string, label: string, color: string, badge_class: string, description: string, c4: string, c5: string, c7: string, c8: string}>
     */
    public static function qualitativeRubric(): array
    {
        return [
            0 => [
                'score' => 0,
                'tier' => 'non_acquis',
                'tier_label' => 'Non acquis / Insuffisant',
                'label' => 'Non acquis • Défaillance complète',
                'color' => 'red',
                'badge_class' => 'bg-red-100 text-red-800 border-red-200',
                'description' => 'Défaillance complète ou absence totale des attendus. Ne répond à aucune exigence minimale du critère évalué.',
                'c4' => 'Refus d\'effort total, abandon au moindre exercice ou passivité complète.',
                'c5' => 'Comportement inacceptable ou perturbateur, insolence ou nuisance au groupe.',
                'c7' => 'Refus d\'apprentissage, aucune tentative d\'assimilation des consignes techniques.',
                'c8' => 'Hygiène de vie destructrice (excès répétés, fêtes/alcool, nuits blanches). Entourage hostile ou absent, contredisant le coach et refusant tout suivi médical ou physio si blessé.',
            ],
            1 => [
                'score' => 1,
                'tier' => 'non_acquis',
                'tier_label' => 'Non acquis / Insuffisant',
                'label' => 'Très insuffisant',
                'color' => 'red',
                'badge_class' => 'bg-red-100 text-red-800 border-red-200',
                'description' => 'Très nettement inférieur aux attentes minimales. Manquement quasi constant, nécessite des interventions répétées.',
                'c4' => 'Abandon très rapide, nécessite d\'être constamment poussé pour participer.',
                'c5' => 'Manque manifeste de respect des règles et des personnes, recadrages permanents.',
                'c7' => 'Régression ou blocage persistant par manque d\'écoute et d\'application.',
                'c8' => 'Sport relégué au dernier plan : sorties inappropriées avant les séances/compétitions, plans hors club ignorés, surcharge anarchique. Parents dévalorisants ou absents, refus de soigner les blessures.',
            ],
            2 => [
                'score' => 2,
                'tier' => 'non_acquis',
                'tier_label' => 'Non acquis / Insuffisant',
                'label' => 'Insuffisant',
                'color' => 'red',
                'badge_class' => 'bg-red-100 text-red-800 border-red-200',
                'description' => 'Niveau insuffisant. Les exigences de base ne sont atteintes que très rarement ou de manière très incomplète.',
                'c4' => 'Efforts très faibles, se contente du minimum absolu avec réticence.',
                'c5' => 'Déconcentration permanente, bavardages nuisibles et attitude nonchalante.',
                'c7' => 'Stagnation nette, répète systématiquement les mêmes erreurs sans chercher à corriger.',
                'c8' => 'Sommeil très négligé, fatigue chronique. Enchaîne les courses le week-end sans concertation ni récupération. Parents intrusifs, critiques ou réticents à consulter un médecin/physio en cas de douleur.',
            ],
            3 => [
                'score' => 3,
                'tier' => 'en_cours',
                'tier_label' => 'En cours d\'acquisition / Fragile',
                'label' => 'Fragile / Irrégulier',
                'color' => 'amber',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
                'description' => 'Niveau fragile et discontinu. Début de conformité encore instable, ne tient pas face à la moindre difficulté.',
                'c4' => 'Fournit l\'effort seulement sous surveillance directe, décroche en autonomie.',
                'c5' => 'Bavardages fréquents, attitude inconstante nécessitant plusieurs rappels à l\'ordre.',
                'c7' => 'Progression très lente, acquis techniques fragiles qui s\'effondrent sous la fatigue.',
                'c8' => 'Hygiène instable : couchers tardifs fréquents, plans d\'entraînement personnels négligés, mauvaise gestion des excès. Entourage peu réceptif aux conseils du club, suivi médical/physio tardif.',
            ],
            4 => [
                'score' => 4,
                'tier' => 'en_cours',
                'tier_label' => 'En cours d\'acquisition / Fragile',
                'label' => 'En voie d\'acquisition',
                'color' => 'amber',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
                'description' => 'En cours d\'acquisition. Répond partiellement aux attentes, mais manque encore de constance, de solidité ou d\'autonomie.',
                'c4' => 'Bonne volonté ponctuelle mais manque d\'endurance mentale pour maintenir l\'effort.',
                'c5' => 'Comportement globalement correct mais dispersion fréquente lors des temps d\'attente.',
                'c7' => 'Début d\'assimilation mais manque de régularité pour ancrer les gestes techniques.',
                'c8' => 'Bonne volonté mais erreurs de gestion : sommeil irrégulier, plans hors club sporadiques, accumulation de courses sans écouter son corps. Parents bienveillants mais trop protecteurs ou envahissants.',
            ],
            5 => [
                'score' => 5,
                'tier' => 'acquis',
                'tier_label' => 'Standard CA Sion / Acquis',
                'label' => 'Acquis • Le Standard du Club',
                'color' => 'blue',
                'badge_class' => 'bg-blue-100 text-blue-800 border-blue-300 ring-2 ring-blue-500/20 font-bold',
                'description' => 'CE QUI EST DEMANDÉ. Correspond exactement au standard de conformité attendu par le club. Répond pleinement, correctement et fidèlement aux exigences habituelles.',
                'c4' => 'Réalise toutes les séries et consignes avec sérieux et application. (standard attendu)',
                'c5' => 'Poli, respectueux du matériel, des camarades et de l\'entraîneur. (standard attendu)',
                'c7' => 'Progression constante et régulière. Intègre les corrections techniques demandées. (standard attendu)',
                'c8' => 'Standard attendu : sommeil régulier, récupération respectée, plans hors club réalisés sans surmenage le week-end. Parents encourageants et bien alignés avec le club : consultation médecin/physio immédiate si blessé.',
            ],
            6 => [
                'score' => 6,
                'tier' => 'acquis',
                'tier_label' => 'Standard CA Sion / Acquis',
                'label' => 'Bien acquis / Régulier',
                'color' => 'blue',
                'badge_class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'description' => 'Bien acquis et fiable. Bonne régularité observée, maîtrise constante et autonome des exigences requises.',
                'c4' => 'Engagement soutenu sur l\'ensemble de la séance, ne rechigne jamais devant l\'effort.',
                'c5' => 'Attitude positive, concentré sur les consignes et attentif aux explications.',
                'c7' => 'Progression solide et visible, gagne en assurance et en fluidité motrice.',
                'c8' => 'Bonne hygiène de vie : équilibre vie privée/sport solide, pas d\'excès préjudiciables, gestion réfléchie des courses. Entourage soutenant, respectueux des choix du coach et assidu aux soins prescrits.',
            ],
            7 => [
                'score' => 7,
                'tier' => 'maitrise',
                'tier_label' => 'Maîtrisé / Très satisfaisant',
                'label' => 'Très satisfaisant / Avancé',
                'color' => 'emerald',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'description' => 'Niveau très satisfaisant. Dépasse le cadre des exigences de base avec aisance, constance et proactivité.',
                'c4' => 'Dépasse la consigne, cherche à se dépasser et encourage ses camarades de séance.',
                'c5' => 'Comportement moteur : dynamise positivement le groupe et facilite la séance.',
                'c7' => 'Nette progression technique et physique, assimile rapidement de nouveaux gestes.',
                'c8' => 'Gestion mature de la vie d\'athlète : hygiène rigoureuse, plans personnels autonomes bien exécutés, écoute active de la fatigue. Parents facilitateurs, en pleine confiance avec le club et très réactifs en cas de soin.',
            ],
            8 => [
                'score' => 8,
                'tier' => 'maitrise',
                'tier_label' => 'Maîtrisé / Très satisfaisant',
                'label' => 'Maîtrisé / Exemplaire au quotidien',
                'color' => 'emerald',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'description' => 'Niveau maîtrisé et remarquable. Grande maturité, régularité exemplaire servant de référence au quotidien.',
                'c4' => 'Volonté sans faille, donne son maximum en toutes circonstances, même difficile.',
                'c5' => 'Exemplarité quotidienne : écoute remarquable, fair-play et esprit d\'entraide.',
                'c7' => 'Progression majeure, maîtrise gestuelle solide et capacité d\'auto-correction.',
                'c8' => 'Hygiène sportive exemplaire : sommeil et récupération optimisés, zéro excès, gestion préventive des pépins physiques. Entourage familial modèle : soutien constant sans ingérence, symbiose avec le staff.',
            ],
            9 => [
                'score' => 9,
                'tier' => 'exceptionnel',
                'tier_label' => 'Exceptionnel / Remarquable',
                'label' => 'Remarquable / Niveau supérieur',
                'color' => 'purple',
                'badge_class' => 'bg-purple-100 text-purple-800 border-purple-200',
                'description' => 'Niveau supérieur remarquable. Constamment au-delà des standards les plus élevés, rigueur exemplaire et inspirante.',
                'c4' => 'Investissement physique et mental hors du commun sur chaque exercice et répétition.',
                'c5' => 'Leader naturel positif, modèle d\'éthique sportive et ambassadeur des valeurs du club.',
                'c7' => 'Évolution fulgurante, franchissement spectaculaire de paliers techniques et athlétiques.',
                'c8' => 'Maturité d\'athlète de haut niveau : discipline de vie remarquable, écoute fine du corps, gestion chirurgicale des efforts hors club. Parents relais exemplaires de la philosophie du club, accompagnement médical parfait.',
            ],
            10 => [
                'score' => 10,
                'tier' => 'exceptionnel',
                'tier_label' => 'Exceptionnel / Remarquable',
                'label' => 'Exceptionnel • Excellence absolue',
                'color' => 'purple',
                'badge_class' => 'bg-purple-100 text-purple-800 border-purple-300 ring-2 ring-purple-500/30 font-bold',
                'description' => 'Note rare et exceptionnelle. Perfection absolue et sans faille, référence ultime rarement attribuée.',
                'c4' => 'Dévouement total et exceptionnel, dépassement de soi permanent et inspirant pour tous.',
                'c5' => 'Attitude irréprochable et exemplaire en tout point, référence absolue pour tout le club.',
                'c7' => 'Excellence motrice et technique rare, progression d\'une rapidité et précision exceptionnelles.',
                'c8' => 'Excellence absolue : hygiène et récupération d\'élite (sommeil, nutrition, prévention). Entourage familial idéal, partenaire exemplaire du club, écoute médicale/physio parfaite au service de l\'épanouissement de l\'athlète.',
            ],
        ];
    }

    /**
     * Paliers regroupés pour l'affichage synthétique de la grille.
     *
     * @return array<string, array{range: string, scores: array<int>, name: string, subtitle: string, badge_color: string, border_color: string, bg_color: string, text_color: string, highlight: bool, summary: string}>
     */
    public static function qualitativeTiers(): array
    {
        return [
            'non_acquis' => [
                'range' => '0 à 2',
                'scores' => [0, 1, 2],
                'name' => 'Non acquis',
                'subtitle' => 'En deçà des attentes du club',
                'badge_color' => 'bg-red-100 text-red-800 border-red-200',
                'border_color' => 'border-red-200',
                'bg_color' => 'bg-red-50/50',
                'text_color' => 'text-red-700',
                'highlight' => false,
                'summary' => 'Défaillance marquée ou absence des attendus minimaux fixés par le club.',
            ],
            'en_cours' => [
                'range' => '3 à 4',
                'scores' => [3, 4],
                'name' => 'En cours d\'acquisition',
                'subtitle' => 'Fragile & irrégulier',
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-200',
                'border_color' => 'border-amber-200',
                'bg_color' => 'bg-amber-50/50',
                'text_color' => 'text-amber-700',
                'highlight' => false,
                'summary' => 'Acquisition fragile ou discontinue, en deçà du niveau de régularité attendu.',
            ],
            'acquis' => [
                'range' => '5 à 6',
                'scores' => [5, 6],
                'name' => 'Acquis',
                'subtitle' => '★ Le repère central attendu',
                'badge_color' => 'bg-blue-100 text-blue-800 border-blue-300 ring-2 ring-blue-400/20',
                'border_color' => 'border-blue-300',
                'bg_color' => 'bg-blue-50/70',
                'text_color' => 'text-blue-800',
                'highlight' => true,
                'summary' => '5 = Ce qui est demandé. Répond précisément et avec constance aux attentes du club.',
            ],
            'maitrise' => [
                'range' => '7 à 8',
                'scores' => [7, 8],
                'name' => 'Maîtrisé / Très bon',
                'subtitle' => 'Dépassement du cadre minimal',
                'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'border_color' => 'border-emerald-200',
                'bg_color' => 'bg-emerald-50/50',
                'text_color' => 'text-emerald-700',
                'highlight' => false,
                'summary' => 'Dépassement solide et régulier des exigences minimales avec proactivité et maturité.',
            ],
            'exceptionnel' => [
                'range' => '9 à 10',
                'scores' => [9, 10],
                'name' => 'Exceptionnel',
                'subtitle' => 'Excellence rare & modèle',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-300 ring-2 ring-purple-400/20',
                'border_color' => 'border-purple-300',
                'bg_color' => 'bg-purple-50/50',
                'text_color' => 'text-purple-700',
                'highlight' => true,
                'summary' => 'Excellence rare et remarquable, référence absolue servant d\'exemple.',
            ],
        ];
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
                $baseScore = (float) config('evaluation.penalties.retard_base_score', 6.0);
                $retardDeduction = (float) config('evaluation.penalties.retard_deduction', 0.3);

                return round(max(0.0, $baseScore - ($evaluation->lateness_count * $retardDeduction)), 2);
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

            self::C8_Environment => $evaluation->c8_environment !== null ? (float) $evaluation->c8_environment : null,

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
