<?php

namespace App\Services;

use App\Enums\AthleteStatus;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class TiivaImportService
{
    /**
     * Importe les données d'athlètes et de groupes depuis un export Tiiva (Excel ou CSV).
     *
     * @return array{created: int, updated: int, groups_created: int}
     */
    public function import(string $filePath, ?EvaluationSession $session = null): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $reader = new CsvReader;
        } else {
            $reader = new XlsxReader;
        }

        $reader->open($filePath);

        $headerMap = [];
        $createdAthletes = 0;
        $updatedAthletes = 0;
        $createdGroups = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowIndex = 0;
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->toArray();

                // Ligne d'en-tête
                if ($rowIndex === 1) {
                    foreach ($cells as $colIndex => $cellValue) {
                        $normalized = trim(mb_strtolower((string) $cellValue));
                        if (str_contains($normalized, 'prénom') || str_contains($normalized, 'prenom')) {
                            $headerMap['first_name'] = $colIndex;
                        } elseif (str_contains($normalized, 'nom') && ! str_contains($normalized, 'groupe')) {
                            $headerMap['last_name'] = $colIndex;
                        } elseif (str_contains($normalized, 'naissance') || str_contains($normalized, 'annee') || str_contains($normalized, 'année')) {
                            $headerMap['birth_year'] = $colIndex;
                        } elseif (str_contains($normalized, 'groupe')) {
                            $headerMap['group'] = $colIndex;
                        } elseif (str_contains($normalized, 'tiiva') || str_contains($normalized, 'identifiant')) {
                            $headerMap['tiiva_id'] = $colIndex;
                        } elseif (str_contains($normalized, 'compétition') || str_contains($normalized, 'competition')) {
                            $headerMap['competitions_done'] = $colIndex;
                        }
                    }

                    continue;
                }

                // Récupération des valeurs
                $firstName = trim((string) ($cells[$headerMap['first_name'] ?? 0] ?? ''));
                $lastName = trim((string) ($cells[$headerMap['last_name'] ?? 1] ?? ''));

                if (empty($firstName) || empty($lastName)) {
                    continue;
                }

                $rawBirth = $cells[$headerMap['birth_year'] ?? 2] ?? null;
                $birthYear = (int) date('Y');
                if ($rawBirth instanceof \DateTimeInterface) {
                    $birthYear = (int) $rawBirth->format('Y');
                } elseif (is_numeric($rawBirth)) {
                    $birthYear = (int) $rawBirth;
                } elseif (is_string($rawBirth) && preg_match('/(\d{4})/', $rawBirth, $m)) {
                    $birthYear = (int) $m[1];
                }

                $groupName = trim((string) ($cells[$headerMap['group'] ?? 3] ?? 'Groupe Général'));
                $tiivaId = isset($headerMap['tiiva_id']) ? trim((string) ($cells[$headerMap['tiiva_id']] ?? '')) : null;
                $competitionsDone = isset($headerMap['competitions_done']) ? (int) ($cells[$headerMap['competitions_done']] ?? 0) : 0;

                // Trouver ou créer le groupe à la volée
                $group = Group::whereRaw('LOWER(name) = ?', [mb_strtolower($groupName)])->first();
                if (! $group) {
                    $group = Group::create([
                        'name' => $groupName,
                    ]);
                    $createdGroups++;
                }

                // Trouver ou créer l'athlète
                $athlete = null;
                if (! empty($tiivaId)) {
                    $athlete = Athlete::where('tiiva_id', $tiivaId)->first();
                }

                if (! $athlete) {
                    $athlete = Athlete::whereRaw('LOWER(first_name) = ? AND LOWER(last_name) = ? AND birth_year = ?', [
                        mb_strtolower($firstName),
                        mb_strtolower($lastName),
                        $birthYear,
                    ])->first();
                }

                if ($athlete) {
                    $athlete->update([
                        'group_id' => $group->id,
                        'tiiva_id' => $tiivaId ?: $athlete->tiiva_id,
                    ]);
                    $updatedAthletes++;
                } else {
                    $athlete = Athlete::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'birth_year' => $birthYear,
                        'group_id' => $group->id,
                        'tiiva_id' => $tiivaId,
                        'status' => AthleteStatus::Active,
                    ]);
                    $createdAthletes++;
                }

                // Si une session d'évaluation est associée, synchroniser competitions_done
                if ($session) {
                    $evaluation = Evaluation::where('evaluation_session_id', $session->id)
                        ->where('athlete_id', $athlete->id)
                        ->first();

                    if ($evaluation) {
                        $evaluation->competitions_done = $competitionsDone;
                        $evaluation->save();
                    }
                }
            }
        }

        $reader->close();

        return [
            'created' => $createdAthletes,
            'updated' => $updatedAthletes,
            'groups_created' => $createdGroups,
        ];
    }
}
