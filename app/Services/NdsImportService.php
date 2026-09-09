<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use OpenSpout\Reader\XLSX\Reader;

class NdsImportService
{
    /**
     * Ingestion du classeur officiel NDS Jeunesse+Sport et mise à jour des présences réelles (C1).
     *
     * @return array{synced: int, unmatched: array<int, string>}
     */
    public function import(string $filePath, EvaluationSession $session, EvaluationCalculatorService $calculator): array
    {
        $reader = new Reader;
        $reader->open($filePath);

        $syncedCount = 0;
        $unmatchedAthletes = [];
        $headerMap = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowIndex = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->toArray();

                // Ligne d'en-tête
                if ($rowIndex === 1) {
                    foreach ($cells as $colIndex => $cellValue) {
                        $normalized = trim(mb_strtolower((string) $cellValue));
                        if (str_contains($normalized, 'nds') || str_contains($normalized, 'numéro') || str_contains($normalized, 'numero')) {
                            $headerMap['nds_number'] = $colIndex;
                        } elseif (str_contains($normalized, 'nom') && ! str_contains($normalized, 'prénom')) {
                            $headerMap['last_name'] = $colIndex;
                        } elseif (str_contains($normalized, 'prénom') || str_contains($normalized, 'prenom')) {
                            $headerMap['first_name'] = $colIndex;
                        } elseif (str_contains($normalized, 'naissance') || str_contains($normalized, 'annee') || str_contains($normalized, 'année')) {
                            $headerMap['birth_year'] = $colIndex;
                        } elseif (str_contains($normalized, 'présence') || str_contains($normalized, 'presence') || str_contains($normalized, 'total')) {
                            $headerMap['attendances'] = $colIndex;
                        }
                    }

                    continue;
                }

                $ndsNumber = isset($headerMap['nds_number']) ? trim((string) ($cells[$headerMap['nds_number']] ?? '')) : null;
                $lastName = trim((string) ($cells[$headerMap['last_name'] ?? 0] ?? ''));
                $firstName = trim((string) ($cells[$headerMap['first_name'] ?? 1] ?? ''));

                if (empty($lastName) && empty($ndsNumber)) {
                    continue;
                }

                $rawBirth = $cells[$headerMap['birth_year'] ?? 2] ?? null;
                $birthYear = null;
                if ($rawBirth instanceof \DateTimeInterface) {
                    $birthYear = (int) $rawBirth->format('Y');
                } elseif (is_numeric($rawBirth)) {
                    $birthYear = (int) $rawBirth;
                }

                $attendances = isset($headerMap['attendances']) ? (int) ($cells[$headerMap['attendances']] ?? 0) : 0;

                // Réconciliation
                $athlete = null;
                if (! empty($ndsNumber)) {
                    $athlete = Athlete::where('nds_number', $ndsNumber)->first();
                }

                if (! $athlete && ! empty($lastName) && ! empty($firstName)) {
                    $query = Athlete::whereRaw('LOWER(TRIM(last_name)) = ? AND LOWER(TRIM(first_name)) = ?', [
                        mb_strtolower($lastName),
                        mb_strtolower($firstName),
                    ]);

                    if ($birthYear) {
                        $query->where('birth_year', $birthYear);
                    }

                    $athlete = $query->first();
                }

                if ($athlete) {
                    if (! empty($ndsNumber) && empty($athlete->nds_number)) {
                        $athlete->update(['nds_number' => $ndsNumber]);
                    }

                    $evaluation = Evaluation::where('evaluation_session_id', $session->id)
                        ->where('athlete_id', $athlete->id)
                        ->first();

                    if ($evaluation) {
                        $evaluation->real_attendances = $attendances;
                        $evaluation->save();
                        $calculator->calculateAthlete($evaluation);
                        $syncedCount++;
                    } else {
                        $unmatchedAthletes[] = "{$lastName} {$firstName} (Pas d'évaluation dans la session)";
                    }
                } else {
                    $unmatchedAthletes[] = "{$lastName} {$firstName} (Non trouvé dans la base club)";
                }
            }
        }

        $reader->close();

        return [
            'synced' => $syncedCount,
            'unmatched' => array_unique($unmatchedAthletes),
        ];
    }
}
