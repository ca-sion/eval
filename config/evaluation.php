<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pondérations des critères d'évaluation
    |--------------------------------------------------------------------------
    |
    | Définition des poids relatifs de chaque critère d'évaluation (C1 à C9).
    | En cas de neutralisation d'un critère (blessure, condition d'âge),
    | les coefficients actifs sont automatiquement redistribués au prorata.
    |
    */
    'weights' => [
        'c1' => 0.20, // Assiduité
        'c2' => 0.05, // Ponctualité (Retards)
        'c3' => 0.15, // Compétitions
        'c4' => 0.15, // Implication
        'c5' => 0.15, // Comportement
        'c6' => 0.10, // Niveau
        'c7' => 0.10, // Progression
        'c8' => 0.05, // Hygiène et environnement
        'c9' => 0.05, // Bénévolat des parents
    ],

    /*
    |--------------------------------------------------------------------------
    | Pénalités
    |--------------------------------------------------------------------------
    |
    | Déduction appliquée par retard enregistré.
    |
    */
    'penalties' => [
        'retard_base_score' => 6.0,
        'retard_deduction' => 0.3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bonifications
    |--------------------------------------------------------------------------
    |
    | Bonus accordé pour l'engagement exceptionnel envers le club.
    |
    */
    'bonuses' => [
        'club_engagement' => 0.75,
    ],

    /*
    |--------------------------------------------------------------------------
    | Valeurs par défaut des groupes
    |--------------------------------------------------------------------------
    |
    | Paramètres appliqués par défaut lors de la création d'un groupe d'entraînement.
    |
    */
    'defaults' => [
        'sessions_per_week' => 2,
        'competitions_planned' => 3,
        'min_score' => 5.00,
        'quota_places' => 20,
        'max_volunteering_age' => 17,
        'required_volunteering_count' => 2,
    ],
];
