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
    $allowed = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0'];
    $total = 0;
    $xCount = 0;

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

        $total += (int) $shot;
    }

    return [
        'total_score' => $total,
        'x_count' => $xCount,
        'shot_count' => count($shots),
    ];
}

function parse_shots_input(string $value): array
{
    $parts = preg_split('/[\s,;\-]+/', strtoupper(trim($value))) ?: [];
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
    if (strcasecmp(trim($discipline), 'Precision') !== 0) {
        return null;
    }

    if (count($shots) !== 5) {
        return null;
    }

    $thresholds = pistolskyttemarke_precision_thresholds($weaponClass, $age);
    if ($thresholds === null) {
        return null;
    }

    try {
        $score = score_shots($shots)['total_score'];
    } catch (InvalidArgumentException) {
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
