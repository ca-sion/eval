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

    /*
    |--------------------------------------------------------------------------
    | Durées des cycles et contextes d'évaluation (en semaines)
    |--------------------------------------------------------------------------
    |
    | Définition du nombre de semaines pour chaque contexte d'évaluation.
    |
    */
    'durations' => [
        'collective_session_weeks' => 5,       // Session générale ordinaire du club
        'adaptation_weeks' => 5,               // Période d'adaptation des nouveaux membres
        'evaluation_probation_weeks' => 2,     // Sursis probatoire de sélection (art. 10.5)
        'disciplinary_probation_weeks' => 2,   // Sursis disciplinaire (art. 27.1)
    ],

    /*
    |--------------------------------------------------------------------------
    | Période d'adaptation des nouveaux membres
    |--------------------------------------------------------------------------
    |
    | Paramètres appliqués pour l'intégration et l'évaluation initiale
    | des nouveaux athlètes rejoignant le club.
    |
    */
    'adaptation' => [
        'recent_entry_months' => 3,            // Seuil d'ancienneté Tiiva pour l'adaptation
    ],
];
