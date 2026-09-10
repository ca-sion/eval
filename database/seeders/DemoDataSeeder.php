<?php

namespace Database\Seeders;

use App\Enums\ArbitrageMode;
use App\Enums\AthleteStatus;
use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Models\User;
use App\Services\EvaluationCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Crée le jeu de données de test / démonstration pour le développement local.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            // 1. Entraîneur de démonstration
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Entraîneur sprint',
                    'password' => 'password',
                ]
            );

            // 2. Groupes d'entraînement de démonstration
            $groupU16G = Group::create([
                'name' => 'U16 Garçons (Sprint / Sauts)',
                'slug' => 'u16-garcons',
                'arbitration_mode' => ArbitrageMode::Quota,
                'quota_places' => 12,
                'min_score' => 5,
                'default_sessions_per_week' => 3,
                'default_competitions_planned' => 5,
                'max_volunteering_age' => 17,
                'required_volunteering_count' => 2,
                'tiiva_id' => 'TIIVA-GRP-01',
            ]);

            $groupU16F = Group::create([
                'name' => 'U16 Filles (Demi-Fond / Haies)',
                'slug' => 'u16-filles',
                'arbitration_mode' => ArbitrageMode::Threshold,
                'quota_places' => 15,
                'min_score' => 5,
                'default_sessions_per_week' => 3,
                'default_competitions_planned' => 5,
                'max_volunteering_age' => 17,
                'required_volunteering_count' => 2,
                'tiiva_id' => 'TIIVA-GRP-02',
            ]);

            $groupU18 = Group::create([
                'name' => 'U18 / U20 Élite',
                'slug' => 'u18-u20-elite',
                'arbitration_mode' => ArbitrageMode::Quota,
                'quota_places' => 4,
                'min_score' => 5,
                'default_sessions_per_week' => 4,
                'default_competitions_planned' => 8,
                'max_volunteering_age' => 17,
                'required_volunteering_count' => 2,
                'tiiva_id' => 'TIIVA-GRP-03',
            ]);

            // 3. Sessions d'évaluation
            $today = Carbon::today();

            // Session active courante
            $sessionActive = EvaluationSession::create([
                'title' => 'Session d\'automne S35 - 2026',
                'start_date' => $today->copy()->subDays(14),
                'end_date' => $today->copy()->addDays(21),
                'weeks_count' => 5,
                'is_closed' => false,
            ]);

            // Session historique précédente (archivée)
            $sessionPast = EvaluationSession::create([
                'title' => 'Session de printemps S18 - 2026',
                'start_date' => $today->copy()->subMonths(5),
                'end_date' => $today->copy()->subMonths(4),
                'weeks_count' => 4,
                'is_closed' => true,
            ]);

            // 4. Athlètes U16 Garçons (20 athlètes)
            $athletesDataU16G = [
                // 1. Top performers
                ['fn' => 'Théo', 'ln' => 'Bonvin', 'by' => 2012, 'lic' => 'SA-9012', 'eval' => ['att' => 15, 'lat' => 0, 'comp' => 5, 'vol' => 3, 'c4' => 9.5, 'c5' => 9.5, 'c6' => AthleticLevel::National, 'c7' => 9.0, 'c8' => 9.0, 'club' => true, 'notes' => 'Exemplaire en tout point. Athlète moteur du groupe. Potentiel podium suisse.']],
                ['fn' => 'Maxime', 'ln' => 'Rey', 'by' => 2012, 'lic' => 'SA-9013', 'eval' => ['att' => 15, 'lat' => 0, 'comp' => 5, 'vol' => 2, 'c4' => 9.0, 'c5' => 9.0, 'c6' => AthleticLevel::Regional, 'c7' => 8.5, 'c8' => 8.5, 'club' => false, 'notes' => 'Très assidu et régulier. Progrès techniques nets sur les haies.']],
                ['fn' => 'Noah', 'ln' => 'Sierro', 'by' => 2011, 'lic' => 'SA-9014', 'eval' => ['att' => 14, 'lat' => 1, 'comp' => 4, 'vol' => 0, 'c4' => 8.5, 'c5' => 8.5, 'c6' => AthleticLevel::Regional, 'c7' => 8.5, 'c8' => 8.0, 'club' => true, 'notes' => '15 ans révolus (bénévolat neutralisé). Très bonne dynamique et investissement club.']],
                ['fn' => 'Adrien', 'ln' => 'Fournier', 'by' => 2012, 'lic' => 'SA-9015', 'eval' => ['att' => 14, 'lat' => 0, 'comp' => 5, 'vol' => 2, 'c4' => 8.5, 'c5' => 8.0, 'c6' => AthleticLevel::Regional, 'c7' => 8.0, 'c8' => 8.0, 'club' => false, 'notes' => 'Bonne régularité aux entraînements. Bon état d\'esprit.']],
                ['fn' => 'Lucas', 'ln' => 'Clavien', 'by' => 2012, 'lic' => 'SA-9016', 'eval' => ['att' => 13, 'lat' => 0, 'comp' => 4, 'vol' => 2, 'c4' => 8.0, 'c5' => 8.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 8.0, 'c8' => 7.5, 'club' => false, 'notes' => 'Sérieux et appliqué. Bonne progression en vitesse pure.']],

                // 2. Blessé avec très bon comportement
                ['fn' => 'Romain', 'ln' => 'Antille', 'by' => 2012, 'lic' => 'SA-9017', 'eval' => ['inj' => true, 'att' => 6, 'lat' => 0, 'comp' => 1, 'vol' => 2, 'c4' => 8.5, 'c5' => 9.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.5, 'c8' => 8.0, 'club' => false, 'notes' => 'Blessure à la cheville en milieu de période. Suivi physio très sérieux, critères C1/C3 neutralisés.']],

                // 3. Athlètes réguliers retenus
                ['fn' => 'Julien', 'ln' => 'Dubuis', 'by' => 2011, 'lic' => 'SA-9018', 'eval' => ['att' => 13, 'lat' => 1, 'comp' => 4, 'vol' => 0, 'c4' => 7.5, 'c5' => 8.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.5, 'c8' => 7.5, 'club' => false, 'notes' => 'Régulier, bonne participation globale.']],
                ['fn' => 'Nathan', 'ln' => 'Emery', 'by' => 2012, 'lic' => 'SA-9019', 'eval' => ['att' => 13, 'lat' => 1, 'comp' => 4, 'vol' => 2, 'c4' => 7.5, 'c5' => 7.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.5, 'c8' => 7.0, 'club' => false, 'notes' => 'Bonne implication générale, efforts constants.']],
                ['fn' => 'Bastien', 'ln' => 'Roh', 'by' => 2013, 'lic' => 'SA-9020', 'eval' => ['att' => 14, 'lat' => 0, 'comp' => 3, 'vol' => 2, 'c4' => 8.0, 'c5' => 8.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 8.0, 'c8' => 7.0, 'club' => false, 'notes' => 'U14 première année surclassé, adaptation très positive.']],
                ['fn' => 'Dylan', 'ln' => 'Morard', 'by' => 2012, 'lic' => 'SA-9021', 'eval' => ['att' => 12, 'lat' => 2, 'comp' => 3, 'vol' => 2, 'c4' => 7.0, 'c5' => 7.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.0, 'c8' => 7.0, 'club' => false, 'notes' => 'Quelques retards à surveiller, mais travail sérieux.']],
                ['fn' => 'Robin', 'ln' => 'Zufferey', 'by' => 2011, 'lic' => 'SA-9022', 'eval' => ['att' => 12, 'lat' => 2, 'comp' => 3, 'vol' => 0, 'c4' => 7.0, 'c5' => 7.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.0, 'c8' => 6.5, 'club' => false, 'notes' => 'À la limite du quota mais niveau satisfaisant.']],
                ['fn' => 'Mathis', 'ln' => 'Favre', 'by' => 2012, 'lic' => 'SA-9023', 'eval' => ['att' => 11, 'lat' => 2, 'comp' => 3, 'vol' => 1, 'c4' => 6.5, 'c5' => 7.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 6.5, 'c8' => 6.5, 'club' => false, 'notes' => '12ème place du quota. Doit intensifier sa présence.']],

                // 4. Sursis probatoires (13 à 16)
                ['fn' => 'Alexis', 'ln' => 'Vouillamoz', 'by' => 2012, 'lic' => 'SA-9024', 'eval' => ['att' => 10, 'lat' => 4, 'comp' => 2, 'vol' => 2, 'c4' => 6.5, 'c5' => 6.0, 'c6' => null, 'c7' => 6.0, 'c8' => 5.5, 'club' => false, 'notes' => 'Hors quota et C8 sous le seuil (récupération/sommeil). Sursis probatoire de 2 semaines requis.']],
                ['fn' => 'Simon', 'ln' => 'Germanier', 'by' => 2011, 'lic' => 'SA-9025', 'eval' => ['att' => 10, 'lat' => 3, 'comp' => 2, 'vol' => 0, 'c4' => 6.0, 'c5' => 6.5, 'c6' => null, 'c7' => 6.0, 'c8' => 5.5, 'club' => false, 'notes' => 'Manque de constance dans les efforts. Période probatoire fixée.']],
                ['fn' => 'Loïc', 'ln' => 'Salamin', 'by' => 2012, 'lic' => 'SA-9026', 'eval' => ['att' => 9, 'lat' => 5, 'comp' => 2, 'vol' => 1, 'c4' => 6.0, 'c5' => 5.5, 'c6' => null, 'c7' => 6.0, 'c8' => 6.0, 'club' => false, 'notes' => 'Comportement C5 sous le seuil et 5 retards. Nécessite un recadrage formel.']],
                ['fn' => 'Eliot', 'ln' => 'Vianin', 'by' => 2013, 'lic' => 'SA-9027', 'eval' => ['inj' => true, 'att' => 4, 'lat' => 1, 'comp' => 0, 'vol' => 1, 'c4' => 6.0, 'c5' => 6.5, 'c6' => null, 'c7' => 5.5, 'c8' => 6.0, 'club' => false, 'notes' => 'Blessure et progression technique en retrait. Entretien spécifique avec les parents.']],

                // 5. Non retenus (17 à 20)
                ['fn' => 'Gabriel', 'ln' => 'Darbellay', 'by' => 2012, 'lic' => 'SA-9028', 'eval' => ['att' => 8, 'lat' => 6, 'comp' => 1, 'vol' => 0, 'c4' => 5.5, 'c5' => 5.0, 'c6' => null, 'c7' => 5.0, 'c8' => 5.0, 'club' => false, 'notes' => 'Absences répétées, 6 retards, note finale sous 5.50. Non retenu pour le cycle compétition.']],
                ['fn' => 'Yoann', 'ln' => 'Masserey', 'by' => 2011, 'lic' => 'SA-9029', 'eval' => ['att' => 7, 'lat' => 7, 'comp' => 1, 'vol' => 0, 'c4' => 5.0, 'c5' => 5.0, 'c6' => null, 'c7' => 5.0, 'c8' => 4.5, 'club' => false, 'notes' => 'Investissement insuffisant, désengagement manifeste. Réorientation vers groupe loisir.']],
                ['fn' => 'Samuel', 'ln' => 'Gillioz', 'by' => 2012, 'lic' => 'SA-9030', 'eval' => ['att' => 5, 'lat' => 8, 'comp' => 0, 'vol' => 0, 'c4' => 4.5, 'c5' => 4.5, 'c6' => null, 'c7' => 4.5, 'c8' => 4.0, 'club' => false, 'notes' => 'Non respect des règles de présence et aucune compétition effectuée.']],
                ['fn' => 'David', 'ln' => 'Délèze', 'by' => 2012, 'lic' => 'SA-9031', 'eval' => ['att' => 4, 'lat' => 9, 'comp' => 0, 'vol' => 0, 'c4' => 4.0, 'c5' => 4.0, 'c6' => null, 'c7' => 4.0, 'c8' => 3.5, 'club' => false, 'notes' => 'Présence anecdotique, non retenu selon Art. 27 des Statuts.']],
            ];

            $this->seedAthletesAndEvaluations($groupU16G, $sessionActive, $athletesDataU16G);

            // 5. Athlètes U16 Filles (12 athlètes)
            $athletesDataU16F = [
                ['fn' => 'Chloé', 'ln' => 'Bagnoud', 'by' => 2012, 'lic' => 'SA-9040', 'eval' => ['att' => 15, 'lat' => 0, 'comp' => 5, 'vol' => 3, 'c4' => 9.5, 'c5' => 9.5, 'c6' => AthleticLevel::National, 'c7' => 9.0, 'c8' => 9.0, 'club' => true, 'notes' => 'Excellente meneuse de groupe, grande rigueur.']],
                ['fn' => 'Lola', 'ln' => 'Constantin', 'by' => 2011, 'lic' => 'SA-9041', 'eval' => ['att' => 14, 'lat' => 0, 'comp' => 5, 'vol' => 0, 'c4' => 9.0, 'c5' => 9.0, 'c6' => AthleticLevel::Regional, 'c7' => 8.5, 'c8' => 8.5, 'club' => false, 'notes' => 'Très bonne dynamique de progression en demi-fond.']],
                ['fn' => 'Julie', 'ln' => 'Ritz', 'by' => 2012, 'lic' => 'SA-9042', 'eval' => ['att' => 14, 'lat' => 1, 'comp' => 4, 'vol' => 2, 'c4' => 8.5, 'c5' => 8.5, 'c6' => AthleticLevel::Regional, 'c7' => 8.0, 'c8' => 8.0, 'club' => false, 'notes' => 'Bon niveau technique et assiduité remarquable.']],
                ['fn' => 'Emma', 'ln' => 'Coquoz', 'by' => 2012, 'lic' => 'SA-9043', 'eval' => ['att' => 13, 'lat' => 0, 'comp' => 4, 'vol' => 2, 'c4' => 8.0, 'c5' => 8.5, 'c6' => AthleticLevel::Regional, 'c7' => 8.0, 'c8' => 8.0, 'club' => false, 'notes' => 'Très régulière aux entraînements.']],
                ['fn' => 'Sarah', 'ln' => 'Gay', 'by' => 2011, 'lic' => 'SA-9044', 'eval' => ['att' => 13, 'lat' => 1, 'comp' => 4, 'vol' => 0, 'c4' => 8.0, 'c5' => 8.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.5, 'c8' => 7.5, 'club' => false, 'notes' => 'Bonne participation aux séances spécifiques haies.']],
                ['fn' => 'Léa', 'ln' => 'Bétrisey', 'by' => 2012, 'lic' => 'SA-9045', 'eval' => ['att' => 12, 'lat' => 0, 'comp' => 3, 'vol' => 2, 'c4' => 7.5, 'c5' => 8.0, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.5, 'c8' => 7.5, 'club' => false, 'notes' => 'Bon potentiel, à encourager en compétition.']],
                ['fn' => 'Marion', 'ln' => 'Mayor', 'by' => 2012, 'lic' => 'SA-9046', 'eval' => ['att' => 12, 'lat' => 1, 'comp' => 3, 'vol' => 2, 'c4' => 7.0, 'c5' => 7.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.0, 'c8' => 7.0, 'club' => false, 'notes' => 'Niveau régulier et comportement irréprochable.']],
                ['fn' => 'Camille', 'ln' => 'Moix', 'by' => 2013, 'lic' => 'SA-9047', 'eval' => ['att' => 12, 'lat' => 1, 'comp' => 3, 'vol' => 2, 'c4' => 7.0, 'c5' => 7.0, 'c6' => null, 'c7' => 7.0, 'c8' => 7.0, 'club' => false, 'notes' => 'Jeune athlète volontaire, progression constante.']],

                // Blessée
                ['fn' => 'Laura', 'ln' => 'Pannatier', 'by' => 2012, 'lic' => 'SA-9048', 'eval' => ['inj' => true, 'att' => 5, 'lat' => 0, 'comp' => 1, 'vol' => 2, 'c4' => 8.0, 'c5' => 8.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 7.0, 'c8' => 7.5, 'club' => false, 'notes' => 'Blessure périostite, maintien avec réathlétisation adaptée.']],

                // Sursis probatoire
                ['fn' => 'Elena', 'ln' => 'Pralong', 'by' => 2012, 'lic' => 'SA-9049', 'eval' => ['att' => 10, 'lat' => 3, 'comp' => 2, 'vol' => 1, 'c4' => 6.5, 'c5' => 6.0, 'c6' => null, 'c7' => 6.0, 'c8' => 5.5, 'club' => false, 'notes' => 'Manque de régularité et retards fréquents. Sursis accordé.']],
                ['fn' => 'Louise', 'ln' => 'Torrent', 'by' => 2011, 'lic' => 'SA-9050', 'eval' => ['att' => 9, 'lat' => 4, 'comp' => 2, 'vol' => 0, 'c4' => 6.0, 'c5' => 5.5, 'c6' => null, 'c7' => 6.0, 'c8' => 6.0, 'club' => false, 'notes' => 'Comportement C5 à recadrer avec le responsable technique.']],

                // Non retenue
                ['fn' => 'Lucie', 'ln' => 'Vuissoz', 'by' => 2012, 'lic' => 'SA-9051', 'eval' => ['att' => 6, 'lat' => 6, 'comp' => 1, 'vol' => 0, 'c4' => 5.0, 'c5' => 5.0, 'c6' => null, 'c7' => 4.5, 'c8' => 4.5, 'club' => false, 'notes' => 'Présences insuffisantes et manque de motivation évident.']],
            ];

            $this->seedAthletesAndEvaluations($groupU16F, $sessionActive, $athletesDataU16F);

            // 6. Athlètes U18 / U20 Élite (8 athlètes)
            $athletesDataU18 = [
                ['fn' => 'Arnaud', 'ln' => 'Frossard', 'by' => 2009, 'lic' => 'SA-8001', 'eval' => ['att' => 20, 'lat' => 0, 'comp' => 8, 'vol' => 0, 'c4' => 9.5, 'c5' => 9.5, 'c6' => AthleticLevel::International, 'c7' => 9.5, 'c8' => 9.0, 'club' => true, 'notes' => 'Cadre national Swiss Athletics, participation aux CE U18.']],
                ['fn' => 'Valentin', 'ln' => 'Mabillard', 'by' => 2010, 'lic' => 'SA-8002', 'eval' => ['att' => 19, 'lat' => 0, 'comp' => 8, 'vol' => 0, 'c4' => 9.0, 'c5' => 9.0, 'c6' => AthleticLevel::National, 'c7' => 9.0, 'c8' => 8.5, 'club' => true, 'notes' => 'Finaliste championnats suisses, très fort investissement.']],
                ['fn' => 'Matteo', 'ln' => 'Pellissier', 'by' => 2009, 'lic' => 'SA-8003', 'eval' => ['att' => 18, 'lat' => 1, 'comp' => 7, 'vol' => 0, 'c4' => 8.5, 'c5' => 9.0, 'c6' => AthleticLevel::National, 'c7' => 8.5, 'c8' => 8.0, 'club' => false, 'notes' => 'Progression constante en sprint court.']],
                ['fn' => 'Xavier', 'ln' => 'Pitteloud', 'by' => 2010, 'lic' => 'SA-8004', 'eval' => ['att' => 17, 'lat' => 1, 'comp' => 6, 'vol' => 0, 'c4' => 8.0, 'c5' => 8.5, 'c6' => AthleticLevel::Regional, 'c7' => 8.0, 'c8' => 8.0, 'club' => false, 'notes' => 'Dernière place du quota Élite validée.']],

                // Hors quota Élite
                ['fn' => 'Thomas', 'ln' => 'Rouiller', 'by' => 2010, 'lic' => 'SA-8005', 'eval' => ['att' => 15, 'lat' => 2, 'comp' => 5, 'vol' => 0, 'c4' => 7.5, 'c5' => 8.0, 'c6' => AthleticLevel::Regional, 'c7' => 7.5, 'c8' => 7.5, 'club' => false, 'notes' => 'Hors quota Élite (4 places). Proposé pour groupe Performance U18.']],
                ['fn' => 'Quentin', 'ln' => 'Savioz', 'by' => 2009, 'lic' => 'SA-8006', 'eval' => ['att' => 14, 'lat' => 2, 'comp' => 5, 'vol' => 0, 'c4' => 7.0, 'c5' => 7.5, 'c6' => AthleticLevel::Regional, 'c7' => 7.0, 'c8' => 7.0, 'club' => false, 'notes' => 'Hors quota Élite, niveau regional satisfaisant.']],
                ['fn' => 'Benjamin', 'ln' => 'Tissières', 'by' => 2010, 'lic' => 'SA-8007', 'eval' => ['inj' => true, 'att' => 8, 'lat' => 1, 'comp' => 2, 'vol' => 0, 'c4' => 7.5, 'c5' => 8.0, 'c6' => AthleticLevel::Regional, 'c7' => 7.0, 'c8' => 7.0, 'club' => false, 'notes' => 'Blessure ischios, réévaluation à la reprise.']],
                ['fn' => 'Nicolas', 'ln' => 'Voutaz', 'by' => 2009, 'lic' => 'SA-8008', 'eval' => ['att' => 11, 'lat' => 5, 'comp' => 3, 'vol' => 0, 'c4' => 6.0, 'c5' => 6.5, 'c6' => AthleticLevel::Cantonal, 'c7' => 6.0, 'c8' => 5.5, 'club' => false, 'notes' => 'Manque d\'implication pour le groupe Élite. Non retenu.']],
            ];

            $this->seedAthletesAndEvaluations($groupU18, $sessionActive, $athletesDataU18);

            // 7. Nouveaux arrivants en période d'adaptation (hors session collective)
            $newJoiner1 = Athlete::create([
                'group_id' => $groupU16G->id,
                'first_name' => 'Paul',
                'last_name' => 'Berclaz',
                'birth_year' => 2012,
                'license_number' => 'SA-9090',
                'status' => AthleteStatus::Adaptation,
            ]);

            Evaluation::create([
                'athlete_id' => $newJoiner1->id,
                'group_id' => $groupU16G->id,
                'context' => EvaluationContext::Adaptation,
                'start_date' => $today->copy()->subDays(10),
                'end_date' => $today->copy()->addDays(20),
                'weeks_count' => 4,
                'sessions_per_week' => 3,
                'competitions_planned' => 4,
                'real_attendances' => 5,
                'competitions_done' => 1,
                'parent_volunteering_count' => 1,
                'c4_commitment' => 8.0,
                'c5_behavior' => 8.5,
                'c6_level' => AthleticLevel::Cantonal,
                'c7_progress' => 7.5,
                'c8_environment' => 8.0,
                'lateness_count' => 0,
                'coach_notes' => 'Période d\'adaptation très encourageante. Bon esprit d\'équipe.',
            ]);

            // 8. Arbitrage et calcul global pour tous les groupes
            $calculator = app(EvaluationCalculatorService::class);
            $calculator->arbitrateGroup($groupU16G, $sessionActive);
            $calculator->arbitrateGroup($groupU16F, $sessionActive);
            $calculator->arbitrateGroup($groupU18, $sessionActive);
        });
    }

    /**
     * Crée les athlètes et leurs évaluations pour un groupe et une session donnés.
     */
    private function seedAthletesAndEvaluations(Group $group, EvaluationSession $session, array $athletesData): void
    {
        foreach ($athletesData as $data) {
            $athlete = Athlete::create([
                'group_id' => $group->id,
                'first_name' => $data['fn'],
                'last_name' => $data['ln'],
                'birth_year' => $data['by'],
                'license_number' => $data['lic'],
                'status' => AthleteStatus::Active,
            ]);

            $eData = $data['eval'];
            $totalPlannedSessions = $group->default_sessions_per_week * $session->weeks_count;

            Evaluation::create([
                'athlete_id' => $athlete->id,
                'group_id' => $group->id,
                'evaluation_session_id' => $session->id,
                'context' => EvaluationContext::Collective,
                'start_date' => $session->start_date,
                'end_date' => $session->end_date,
                'weeks_count' => $session->weeks_count,
                'sessions_per_week' => $group->default_sessions_per_week,
                'competitions_planned' => $group->default_competitions_planned,
                'is_injured' => $eData['inj'] ?? false,
                'real_attendances' => $eData['att'] ?? $totalPlannedSessions,
                'lateness_count' => $eData['lat'] ?? 0,
                'competitions_done' => $eData['comp'] ?? $group->default_competitions_planned,
                'parent_volunteering_count' => $eData['vol'] ?? 0,
                'c4_commitment' => $eData['c4'] ?? 7.0,
                'c5_behavior' => $eData['c5'] ?? 7.0,
                'c6_level' => $eData['c6'] ?? null,
                'c7_progress' => $eData['c7'] ?? 7.0,
                'c8_environment' => $eData['c8'] ?? 7.0,
                'has_club_engagement' => $eData['club'] ?? false,
                'coach_notes' => $eData['notes'] ?? null,
            ]);
        }
    }
}
