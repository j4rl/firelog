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
