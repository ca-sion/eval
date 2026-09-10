<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvaluationDecision: string implements HasColor, HasLabel
{
    case Retained = 'retained';
    case ProbationNeeded = 'probation_needed';
    case NotRetained = 'not_retained';
    case Pending = 'pending';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Retained => 'Maintien ou admission',
            self::ProbationNeeded => 'Sursis probatoire',
            self::NotRetained => 'Non-admission ou exclusion',
            self::Pending => 'En attente d\'arbitrage',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Retained => 'success',
            self::ProbationNeeded => 'warning',
            self::NotRetained => 'danger',
            self::Pending => 'gray',
        };
    }

    /**
     * Référence de l'article dans le Règlement du club.
     */
    public function regulationArticle(): ?string
    {
        return match ($this) {
            self::Retained => 'Règlement Art. 10.4',
            self::ProbationNeeded => 'Règlement Art. 10.5',
            self::NotRetained => 'Règlement Art. 10.5 et 27',
            self::Pending => null,
        };
    }

    /**
     * Description détaillée des conséquences de la décision.
     */
    public function description(): string
    {
        return match ($this) {
            self::Retained => 'L\'athlète satisfait aux exigences de son groupe. Son statut de membre actif est confirmé et il poursuit sereinement ses entraînements et compétitions.',
            self::ProbationNeeded => 'Certains critères demandent une attention immédiate. Une période de 2 semaines permet à l\'athlète de redresser la barre avec des objectifs précis.',
            self::NotRetained => 'Si les critères ne sont pas atteints après le sursis, une réorientation ou une exclusion est prononcée.',
            self::Pending => 'L\'évaluation est en cours de consolidation et d\'arbitrage par le club.',
        };
    }

    /**
     * Configuration visuelle des cartes et badges pour l'affichage public et coach.
     *
     * @return array{card_bg: string, card_border: string, text_title: string, text_article: string, text_desc: string, icon_bg: string, icon_color: string, icon: string}
     */
    public function styleConfig(): array
    {
        return match ($this) {
            self::Retained => [
                'card_bg' => 'bg-emerald-50/70',
                'card_border' => 'border-emerald-200',
                'text_title' => 'text-emerald-900',
                'text_article' => 'text-emerald-700',
                'text_desc' => 'text-emerald-800',
                'icon_bg' => 'bg-emerald-600',
                'icon_color' => 'text-white',
                'icon' => 'check',
            ],
            self::ProbationNeeded => [
                'card_bg' => 'bg-amber-50/70',
                'card_border' => 'border-amber-200',
                'text_title' => 'text-amber-900',
                'text_article' => 'text-amber-700',
                'text_desc' => 'text-amber-800',
                'icon_bg' => 'bg-amber-600',
                'icon_color' => 'text-white',
                'icon' => 'exclamation',
            ],
            self::NotRetained => [
                'card_bg' => 'bg-red-50/70',
                'card_border' => 'border-red-200',
                'text_title' => 'text-red-900',
                'text_article' => 'text-red-700',
                'text_desc' => 'text-red-800',
                'icon_bg' => 'bg-red-600',
                'icon_color' => 'text-white',
                'icon' => 'x',
            ],
            self::Pending => [
                'card_bg' => 'bg-slate-50/70',
                'card_border' => 'border-slate-200',
                'text_title' => 'text-slate-900',
                'text_article' => 'text-slate-700',
                'text_desc' => 'text-slate-800',
                'icon_bg' => 'bg-slate-600',
                'icon_color' => 'text-white',
                'icon' => 'clock',
            ],
        };
    }
}
