<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use Carbon\Carbon;
use OpenSpout\Reader\XLSX\Reader;

class NdsImportService
{
    /**
     * Ingestion du classeur officiel NDS Jeunesse+Sport et mise à jour des présences réelles (C1)
     * selon les dates de la session d'évaluation.
     *
     * @return array{synced: int, unmatched: array<int, string>}
     */
    public function import(string $filePath, EvaluationSession $session, EvaluationCalculatorService $calculator): array
    {
        $reader = new Reader;
        $reader->open($filePath);

        $syncedCount = 0;
        $unmatchedAthletes = [];

        $sessionStart = Carbon::parse($session->start_date)->startOfDay();
        $sessionEnd = Carbon::parse($session->end_date)->endOfDay();

        foreach ($reader->getSheetIterator() as $sheet) {
            $dateMap = []; // colIndex => Carbon (date of activity)
            $participantsStarted = false;
            $isJsFormat = false;

            // Variables pour format tabulaire alternatif
            $flatHeaderMap = [];
            $rowIndex = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->toArray();

                // 1. Détection du format officiel J+S (Ligne 'Date')
                $rowPrefix = trim((string) ($cells[0] ?? ''));
                if (strcasecmp($rowPrefix, 'Date') === 0 || (isset($cells[1]) && strcasecmp(trim((string) $cells[1]), 'Date') === 0)) {
                    $isJsFormat = true;
                    foreach ($cells as $colIdx => $val) {
                        if ($val instanceof \DateTimeInterface) {
                            $dateMap[$colIdx] = Carbon::instance($val)->startOfDay();
                        } elseif (is_numeric($val) && $val > 30000 && $val < 60000) {
                            $timestamp = (int) (($val - 25569) * 86400);
                            $dateMap[$colIdx] = Carbon::createFromTimestampUTC($timestamp)->startOfDay();
                        } elseif (is_string($val) && ! empty(trim($val)) && strcasecmp(trim($val), 'Date') !== 0) {
                            try {
                                $dateMap[$colIdx] = Carbon::parse($val)->startOfDay();
                            } catch (\Throwable) {
                                // Ignorer les en-têtes non-date
                            }
                        }
                    }

                    continue;
                }

                // Détection de la section des participants J+S
                $rowText = implode(' ', array_slice(array_map('strval', $cells), 0, 4));
                if (preg_match('/participant/i', $rowText)) {
                    $participantsStarted = true;

                    continue;
                }

                // 2. Traitement d'une ligne d'athlète au format officiel J+S
                if ($isJsFormat && $participantsStarted) {
                    $lastName = trim((string) ($cells[1] ?? ''));
                    $firstName = trim((string) ($cells[2] ?? ''));

                    if (empty($lastName) || empty($firstName)) {
                        continue;
                    }

                    $rawBirth = $cells[3] ?? null;
                    $birthYear = null;
                    if ($rawBirth instanceof \DateTimeInterface) {
                        $birthYear = (int) $rawBirth->format('Y');
                    } elseif (is_numeric($rawBirth)) {
                        if ($rawBirth > 30000 && $rawBirth < 60000) {
                            $timestamp = (int) (($rawBirth - 25569) * 86400);
                            $birthYear = (int) gmdate('Y', $timestamp);
                        } else {
                            $birthYear = (int) $rawBirth;
                        }
                    } elseif (is_string($rawBirth) && ! empty(trim($rawBirth))) {
                        try {
                            $birthYear = (int) Carbon::parse($rawBirth)->format('Y');
                        } catch (\Throwable) {
                        }
                    }

                    // Comptage des présences situées dans la période de la session d'évaluation
                    $attendancesCount = 0;
                    $sessionStartDate = $sessionStart->format('Y-m-d');
                    $sessionEndDate = $sessionEnd->format('Y-m-d');

                    foreach ($dateMap as $colIdx => $activityDate) {
                        $activityDateStr = $activityDate->format('Y-m-d');
                        if ($activityDateStr >= $sessionStartDate && $activityDateStr <= $sessionEndDate) {
                            $mark = trim((string) ($cells[$colIdx] ?? ''));
                            if ($mark !== '' && $mark !== '-' && $mark !== '0') {
                                $attendancesCount++;
                            }
                        }
                    }

                    // Réconciliation de l'athlète
                    $athlete = $this->findAthlete($lastName, $firstName, $birthYear);

                    if ($athlete) {
                        $evaluation = Evaluation::where('evaluation_session_id', $session->id)
                            ->where('athlete_id', $athlete->id)
                            ->first();

                        if ($evaluation) {
                            $evaluation->real_attendances = $attendancesCount;
                            $evaluation->save();
                            $calculator->calculateAthlete($evaluation);
                            $syncedCount++;
                        } else {
                            $unmatchedAthletes[] = "{$lastName} {$firstName} (Non inscrit dans cette session)";
                        }
                    } else {
                        $unmatchedAthletes[] = "{$lastName} {$firstName} (Non trouvé dans la base du club)";
                    }

                    continue;
                }

                // 3. Fallback : Format tabulaire classique (Nom, Prénom, Présences)
                if (! $isJsFormat) {
                    if ($rowIndex === 1) {
                        foreach ($cells as $colIndex => $cellValue) {
                            $normalized = trim(mb_strtolower((string) $cellValue));
                            if (str_contains($normalized, 'nds') || str_contains($normalized, 'numéro')) {
                                $flatHeaderMap['nds_number'] = $colIndex;
                            } elseif (str_contains($normalized, 'nom') && ! str_contains($normalized, 'prénom')) {
                                $flatHeaderMap['last_name'] = $colIndex;
                            } elseif (str_contains($normalized, 'prénom') || str_contains($normalized, 'prenom')) {
                                $flatHeaderMap['first_name'] = $colIndex;
                            } elseif (str_contains($normalized, 'présence') || str_contains($normalized, 'total')) {
                                $flatHeaderMap['attendances'] = $colIndex;
                            }
                        }

                        continue;
                    }

                    $lastName = trim((string) ($cells[$flatHeaderMap['last_name'] ?? 0] ?? ''));
                    $firstName = trim((string) ($cells[$flatHeaderMap['first_name'] ?? 1] ?? ''));
                    $attendances = isset($flatHeaderMap['attendances']) ? (int) ($cells[$flatHeaderMap['attendances']] ?? 0) : 0;

                    if (empty($lastName) && empty($firstName)) {
                        continue;
                    }

                    $athlete = $this->findAthlete($lastName, $firstName, null);
                    if ($athlete) {
                        $evaluation = Evaluation::where('evaluation_session_id', $session->id)
                            ->where('athlete_id', $athlete->id)
                            ->first();

                        if ($evaluation) {
                            $evaluation->real_attendances = $attendances;
                            $evaluation->save();
                            $calculator->calculateAthlete($evaluation);
                            $syncedCount++;
                        }
                    }
                }
            }
        }

        $reader->close();

        return [
            'synced' => $syncedCount,
            'unmatched' => array_unique($unmatchedAthletes),
        ];
    }

    /**
     * Recherche un athlète de manière tolérante aux accents, casse et prénoms composés.
     */
    protected function findAthlete(string $lastName, string $firstName, ?int $birthYear): ?Athlete
    {
        $cleanLast = mb_strtolower(trim($lastName));
        $cleanFirst = mb_strtolower(trim($firstName));

        // Recherche exacte avec année
        if ($birthYear) {
            $athlete = Athlete::whereRaw('LOWER(TRIM(last_name)) = ? AND LOWER(TRIM(first_name)) = ?', [$cleanLast, $cleanFirst])
                ->where('birth_year', $birthYear)
                ->first();

            if ($athlete) {
                return $athlete;
            }
        }

        // Recherche exacte sans année
        $athlete = Athlete::whereRaw('LOWER(TRIM(last_name)) = ? AND LOWER(TRIM(first_name)) = ?', [$cleanLast, $cleanFirst])->first();
        if ($athlete) {
            return $athlete;
        }

        // Recherche sur le premier prénom en cas de prénom composé (ex. 'Elyne Géraldine' -> 'Elyne')
        $firstPart = explode(' ', $cleanFirst)[0] ?? $cleanFirst;
        if ($birthYear) {
            $athleteFirst = Athlete::whereRaw('LOWER(TRIM(last_name)) = ? AND LOWER(TRIM(first_name)) LIKE ?', [$cleanLast, "{$firstPart}%"])
                ->where('birth_year', $birthYear)
                ->first();

            if ($athleteFirst) {
                return $athleteFirst;
            }
        }

        return Athlete::whereRaw('LOWER(TRIM(last_name)) = ? AND LOWER(TRIM(first_name)) LIKE ?', [$cleanLast, "{$firstPart}%"])->first();
    }
}
