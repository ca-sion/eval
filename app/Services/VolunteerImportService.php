<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\EvaluationSession;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class VolunteerImportService
{
    /**
     * Importe la matrice des participations bénévoles Tiiva et met à jour les scores de bénévolat parental (C9).
     *
     * @return array{synced: int, total_contacts: int, total_missions: int}
     */
    public function import(string $filePath, EvaluationSession $session, EvaluationCalculatorService $calculator): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $reader = new CsvReader;
        } else {
            $reader = new XlsxReader;
        }

        $reader->open($filePath);

        $participationsByTiivaId = []; // string (tiiva_id) => int (count)
        $idCol = 0;
        $participationsCol = 10;
        $totalMissions = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowIndex = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->toArray();

                // Ligne d'en-tête : détection dynamique des colonnes
                if ($rowIndex === 1) {
                    foreach ($cells as $idx => $val) {
                        $norm = trim(mb_strtolower((string) $val));
                        if ($norm === 'id') {
                            $idCol = $idx;
                        } elseif (str_contains($norm, 'total participations') || str_contains($norm, 'participations')) {
                            $participationsCol = $idx;
                        }
                    }

                    continue;
                }

                $tiivaId = trim((string) ($cells[$idCol] ?? ''));
                $count = (int) ($cells[$participationsCol] ?? 0);

                if (! empty($tiivaId)) {
                    $participationsByTiivaId[$tiivaId] = $count;
                    $totalMissions += $count;
                }
            }
        }

        $reader->close();

        // Réconciliation avec les évaluations de la session
        $syncedCount = 0;
        $evaluations = Evaluation::where('evaluation_session_id', $session->id)
            ->with('athlete')
            ->get();

        foreach ($evaluations as $evaluation) {
            $athlete = $evaluation->athlete;
            if (! $athlete) {
                continue;
            }

            $guardianIds = $athlete->guardian_tiiva_ids ?? [];
            $parentVolunteering = 0;

            // Somme des participations de tous les responsables légaux enregistrés dans Tiiva
            if (! empty($guardianIds)) {
                foreach ($guardianIds as $gId) {
                    $parentVolunteering += $participationsByTiivaId[(string) $gId] ?? 0;
                }
            } else {
                // Fallback si l'athlète lui-même a un ID Tiiva et que la participation a été saisie à son nom
                if (! empty($athlete->tiiva_id) && isset($participationsByTiivaId[(string) $athlete->tiiva_id])) {
                    $parentVolunteering = $participationsByTiivaId[(string) $athlete->tiiva_id];
                }
            }

            $evaluation->parent_volunteering_count = $parentVolunteering;
            $evaluation->save();
            $calculator->calculateAthlete($evaluation);
            $syncedCount++;
        }

        return [
            'synced' => $syncedCount,
            'total_contacts' => count($participationsByTiivaId),
            'total_missions' => $totalMissions,
        ];
    }
}
