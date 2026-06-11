<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function valid_weapon_class(string $class): bool
{
    return in_array($class, ['A', 'B', 'C', 'R'], true);
}

function default_disciplines(): array
{
    return ['Precision', 'Fält', 'Militär snabbmatch', 'Pistol 50m', 'Snabbserie'];
}

function score_shots(array $shots): array
{
    $allowed = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0', '-'];
    $total = 0;
    $xCount = 0;
    $missCount = 0;

    foreach ($shots as $shot) {
        $shot = (string) $shot;
        if (!in_array($shot, $allowed, true)) {
            throw new InvalidArgumentException('Ogiltigt skottvärde.');
        }

        if ($shot === 'X') {
            $total += 10;
            $xCount++;
            continue;
        }
        if ($shot === '-' || $shot === '0') {
            $missCount++;
            continue;
        }

        $total += (int) $shot;
    }

    return [
        'total_score' => $total,
        'x_count' => $xCount,
        'miss_count' => $missCount,
        'shot_count' => count($shots),
    ];
}

function parse_shots_input(string $value): array
{
    $parts = preg_split('/[\s,;]+/', strtoupper(trim($value))) ?: [];
    return array_values(array_filter($parts, static fn (string $shot): bool => $shot !== ''));
}

function normalize_birth_date(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        return null;
    }

    $today = new DateTimeImmutable('today');
    $age = age_on_date($value, $today->format('Y-m-d'));
    if ($date > $today || $age === null || $age > 120) {
        return null;
    }

    return $value;
}

function age_on_date(?string $birthDate, string $date): ?int
{
    if ($birthDate === null || trim($birthDate) === '') {
        return null;
    }

    $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    $target = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($birth === false || $target === false || $birth > $target) {
        return null;
    }

    return (int) $birth->diff($target)->y;
}

function medal_css_class(string $level): string
{
    return match ($level) {
        'gold' => 'medal-gold',
        'silver' => 'medal-silver',
        'bronze' => 'medal-bronze',
        default => '',
    };
}

function medal_label(string $level): string
{
    return match ($level) {
        'gold' => 'Guld',
        'silver' => 'Silver',
        'bronze' => 'Brons',
        default => '',
    };
}

function discipline_key(string $discipline): string
{
    $value = strtolower(trim($discipline));
    $value = strtr($value, ['å' => 'a', 'ä' => 'a', 'ö' => 'o']);
    $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? $value;

    return $value;
}

function medal_badge_html(?array $medal): string
{
    if ($medal === null) {
        return '';
    }

    $level = (string) ($medal['level'] ?? '');
    $label = (string) ($medal['label'] ?? medal_label($level));
    $title = (string) ($medal['title'] ?? $label);

    return '<span class="medal-badge ' . e(medal_css_class($level)) . '" title="' . e($title) . '" aria-label="' . e($title) . '"><span aria-hidden="true"></span>' . e($label) . '</span>';
}

function medal_from_thresholds(array $thresholds, int $score, string $labelPrefix, string $titleContext, string $unit = 'p'): ?array
{
    foreach (['gold', 'silver', 'bronze'] as $level) {
        if (!isset($thresholds[$level])) {
            continue;
        }

        if ($score >= $thresholds[$level]) {
            $label = $level === 'gold'
                ? $labelPrefix . ' guld'
                : $labelPrefix . ' ' . strtolower(medal_label($level));

            return [
                'level' => $level,
                'label' => $label,
                'title' => $label . ': ' . $score . ' ' . $unit . ', krav ' . $thresholds[$level] . ' ' . $unit . ($titleContext !== '' ? ' (' . $titleContext . ')' : ''),
                'score' => $score,
                'threshold' => $thresholds[$level],
            ];
        }
    }

    return null;
}

function pistolskyttemarke_precision_thresholds(string $weaponClass, ?int $age = null): ?array
{
    $base = [
        'A' => ['bronze' => 32, 'silver' => 38, 'gold' => 43],
        'B' => ['bronze' => 33, 'silver' => 39, 'gold' => 45],
        'C' => ['bronze' => 34, 'silver' => 40, 'gold' => 46],
    ];

    $weaponClass = strtoupper(trim($weaponClass));
    if (!isset($base[$weaponClass])) {
        return null;
    }

    $reduction = 0;
    if ($age !== null) {
        if ($age >= 66) {
            $reduction = 2;
        } elseif ($age >= 56) {
            $reduction = 1;
        }
    }

    return array_map(static fn (int $value): int => max(0, $value - $reduction), $base[$weaponClass]);
}

function series_medal_for_context(string $discipline, string $weaponClass, ?int $age, array $shots): ?array
{
    try {
        $scoreData = score_shots($shots);
    } catch (InvalidArgumentException) {
        return null;
    }

    $score = $scoreData['total_score'];
    $disciplineKey = discipline_key($discipline);
    if ($disciplineKey === 'precision' || $disciplineKey === 'precisionsskjutning') {
        if (count($shots) !== 5) {
            return null;
        }

        $thresholds = pistolskyttemarke_precision_thresholds($weaponClass, $age);
        if ($thresholds === null) {
            return null;
        }

        foreach (['gold', 'silver', 'bronze'] as $level) {
            if ($score >= $thresholds[$level]) {
                $label = $level === 'gold' ? 'Guldserie' : medal_label($level);
                return [
                    'level' => $level,
                    'label' => $label,
                    'title' => $label . ' precision: ' . $score . ' p, krav ' . $thresholds[$level] . ' p',
                    'score' => $score,
                    'threshold' => $thresholds[$level],
                ];
            }
        }

        return null;
    }

    if ($disciplineKey === 'snabbserie') {
        if (count($shots) !== 6) {
            return null;
        }

        $hits = $scoreData['shot_count'] - $scoreData['miss_count'];
        if ($hits >= 6) {
            return [
                'level' => 'silver',
                'label' => 'Tillämpningsserie 6 träff',
                'title' => 'Tillämpningsserie: 6 träff. Uppfyller träffkrav för silver/guld; skjuttiden avgör valör.',
                'score' => $hits,
                'threshold' => 6,
            ];
        }

        if ($hits >= 5) {
            return [
                'level' => 'bronze',
                'label' => 'Tillämpningsserie brons',
                'title' => 'Tillämpningsserie brons: ' . $hits . ' träff, krav 5 träff',
                'score' => $hits,
                'threshold' => 5,
            ];
        }

        return null;
    }

    if (in_array($disciplineKey, ['snabbskjutning', 'snabbpistol'], true) && count($shots) === 5) {
        return medal_from_thresholds(
            ['bronze' => 45, 'silver' => 48, 'gold' => 49],
            $score,
            'Elitmärke snabbskjutning',
            'snabbskjutning, 5 skott'
        );
    }

    return null;
}

function session_series_scores(array $seriesRows): array
{
    $totalScore = 0;
    $totalHits = 0;
    $validRows = 0;

    foreach ($seriesRows as $row) {
        $shots = json_decode((string) ($row['shots_json'] ?? ''), true);
        if (!is_array($shots)) {
            continue;
        }

        try {
            $score = score_shots($shots);
        } catch (InvalidArgumentException) {
            continue;
        }

        $totalScore += $score['total_score'];
        $totalHits += $score['shot_count'] - $score['miss_count'];
        $validRows++;
    }

    return [
        'series_count' => $validRows,
        'total_score' => $totalScore,
        'total_hits' => $totalHits,
    ];
}

function session_medal_for_context(string $discipline, string $weaponClass, array $seriesRows): ?array
{
    $disciplineKey = discipline_key($discipline);
    $weaponClass = strtoupper(trim($weaponClass));
    $scores = session_series_scores($seriesRows);
    $seriesCount = $scores['series_count'];

    if ($disciplineKey === 'snabbserie' && $seriesCount >= 3) {
        $seriesHits = [];
        foreach (array_slice($seriesRows, 0, 3) as $row) {
            $shots = json_decode((string) ($row['shots_json'] ?? ''), true) ?: [];
            if (count($shots) !== 6) {
                return null;
            }

            try {
                $score = score_shots($shots);
            } catch (InvalidArgumentException) {
                return null;
            }

            $seriesHits[] = $score['shot_count'] - $score['miss_count'];
        }

        $lowestHits = min($seriesHits);
        if ($lowestHits >= 6) {
            return [
                'level' => 'silver',
                'label' => 'Tillämpningsserier 6 träff',
                'title' => 'Tre tillämpningsserier med minst 6 träff. Uppfyller träffkrav för silver/guld; skjuttiden avgör valör.',
                'score' => $lowestHits,
                'threshold' => 6,
            ];
        }

        if ($lowestHits >= 5) {
            return [
                'level' => 'bronze',
                'label' => 'Tillämpningsserier brons',
                'title' => 'Tre tillämpningsserier med minst 5 träff, krav 5 träff',
                'score' => $lowestHits,
                'threshold' => 5,
            ];
        }

        return null;
    }

    if (in_array($disciplineKey, ['snabbskjutning', 'snabbpistol'], true) && $seriesCount >= 5) {
        $seriesScores = [];
        foreach (array_slice($seriesRows, 0, 5) as $row) {
            $shots = json_decode((string) ($row['shots_json'] ?? ''), true) ?: [];
            if (count($shots) !== 5) {
                return null;
            }

            try {
                $seriesScores[] = score_shots($shots)['total_score'];
            } catch (InvalidArgumentException) {
                return null;
            }
        }

        $lowestSeries = min($seriesScores);
        return medal_from_thresholds(
            ['bronze' => 45, 'silver' => 48, 'gold' => 49],
            $lowestSeries,
            'Elitmärke snabbskjutning',
            '5 serier, lägsta serie'
        );
    }

    if (($disciplineKey === 'precision' || $disciplineKey === 'precisionsskjutning') && in_array($seriesCount, [6, 7, 10], true)) {
        $thresholds = [
            'A' => [
                6 => ['bronze' => 194, 'silver' => 231, 'gold' => 262],
                7 => ['bronze' => 226, 'silver' => 269, 'gold' => 305],
                10 => ['bronze' => 322, 'silver' => 383, 'gold' => 434],
            ],
            'B' => [
                6 => ['bronze' => 200, 'silver' => 237, 'gold' => 274],
                7 => ['bronze' => 233, 'silver' => 276, 'gold' => 319],
                10 => ['bronze' => 332, 'silver' => 393, 'gold' => 454],
            ],
            'C' => [
                6 => ['bronze' => 206, 'silver' => 243, 'gold' => 280],
                7 => ['bronze' => 240, 'silver' => 283, 'gold' => 326],
                10 => ['bronze' => 342, 'silver' => 403, 'gold' => 464],
            ],
        ];

        return isset($thresholds[$weaponClass][$seriesCount])
            ? medal_from_thresholds($thresholds[$weaponClass][$seriesCount], $scores['total_score'], 'Precisionsskyttemärke', $seriesCount . ' serier')
            : null;
    }

    if (in_array($disciplineKey, ['militarsnabbmatch', 'militarsnabb'], true) && $seriesCount >= 12) {
        $thresholds = [
            'A' => ['bronze' => 377, 'silver' => 454, 'gold' => 515],
            'B' => ['bronze' => 391, 'silver' => 472, 'gold' => 543],
            'C' => ['bronze' => 404, 'silver' => 481, 'gold' => 550],
            'R' => ['bronze' => 388, 'silver' => 467, 'gold' => 532],
        ];

        return isset($thresholds[$weaponClass])
            ? medal_from_thresholds($thresholds[$weaponClass], $scores['total_score'], 'Militär snabbmatchmärke', '12 serier')
            : null;
    }

    if (in_array($disciplineKey, ['nationellhelmatch', 'helmatch'], true) && $seriesCount >= 12) {
        $thresholds = [
            'A' => ['bronze' => 365, 'silver' => 435, 'gold' => 500],
            'B' => ['bronze' => 380, 'silver' => 450, 'gold' => 520],
            'C' => ['bronze' => 390, 'silver' => 460, 'gold' => 530],
        ];

        return isset($thresholds[$weaponClass])
            ? medal_from_thresholds($thresholds[$weaponClass], $scores['total_score'], 'Nationell helmatchmärke', '12 serier')
            : null;
    }

    if (($disciplineKey === 'falt' || $disciplineKey === 'faltskytte') && $seriesCount >= 6 && $seriesCount <= 10) {
        $thresholds = [
            'A' => [
                6 => ['bronze' => 19, 'silver' => 23, 'gold' => 27],
                7 => ['bronze' => 22, 'silver' => 27, 'gold' => 31],
                8 => ['bronze' => 25, 'silver' => 31, 'gold' => 36],
                9 => ['bronze' => 29, 'silver' => 36, 'gold' => 41],
                10 => ['bronze' => 32, 'silver' => 40, 'gold' => 46],
            ],
            'B' => [
                6 => ['bronze' => 22, 'silver' => 27, 'gold' => 31],
                7 => ['bronze' => 25, 'silver' => 31, 'gold' => 36],
                8 => ['bronze' => 29, 'silver' => 36, 'gold' => 41],
                9 => ['bronze' => 32, 'silver' => 40, 'gold' => 46],
                10 => ['bronze' => 36, 'silver' => 45, 'gold' => 51],
            ],
            'C' => [
                6 => ['bronze' => 22, 'silver' => 27, 'gold' => 31],
                7 => ['bronze' => 25, 'silver' => 31, 'gold' => 36],
                8 => ['bronze' => 29, 'silver' => 36, 'gold' => 41],
                9 => ['bronze' => 32, 'silver' => 40, 'gold' => 46],
                10 => ['bronze' => 36, 'silver' => 45, 'gold' => 51],
            ],
            'R' => [
                6 => ['bronze' => 20, 'silver' => 25, 'gold' => 29],
                7 => ['bronze' => 23, 'silver' => 29, 'gold' => 34],
                8 => ['bronze' => 27, 'silver' => 34, 'gold' => 38],
                9 => ['bronze' => 30, 'silver' => 38, 'gold' => 44],
                10 => ['bronze' => 34, 'silver' => 42, 'gold' => 49],
            ],
        ];

        return isset($thresholds[$weaponClass][$seriesCount])
            ? medal_from_thresholds($thresholds[$weaponClass][$seriesCount], $scores['total_hits'], 'Fältskyttemärke', $seriesCount . ' stationer', 'träff')
            : null;
    }

    return null;
}
