<?php
declare(strict_types=1);

// Small server-rendered SVG charts. Colour comes from CSS (the department
// accent), so charts follow light and dark mode. Each bar has a <title>
// for a hover tooltip, and every chart ships with a table view.

/** Round an axis maximum up to a clean number (1, 2, 5 x 10^n). */
function nice_max(float $value): float
{
    if ($value <= 0) {
        return 1;
    }
    $exp = 10 ** floor(log10($value));
    foreach ([1, 2, 5, 10] as $m) {
        if ($value <= $m * $exp) {
            return $m * $exp;
        }
    }
    return 10 * $exp;
}

function compact_number(float $n): string
{
    return match (true) {
        $n >= 1_000_000 => round($n / 1_000_000, 1) . 'M',
        $n >= 10_000 => round($n / 1000) . 'K',
        $n >= 1000 => round($n / 1000, 1) . 'K',
        default => (string) round($n),
    };
}

/** SVG path for a bar with a 4px rounded data end and a square baseline. */
function bar_path(float $x, float $y, float $w, float $h, string $direction = 'up'): string
{
    $r = min(4, $w / 2, $h);
    if ($h <= 0) {
        return '';
    }
    if ($direction === 'up') {
        return sprintf('M%.1f %.1fV%.1fQ%.1f %.1f %.1f %.1fH%.1fQ%.1f %.1f %.1f %.1fV%.1fZ',
            $x, $y + $h, $y + $r, $x, $y, $x + $r, $y, $x + $w - $r, $x + $w, $y, $x + $w, $y + $r, $y + $h);
    }
    // 'right': baseline on the left, rounded end on the right ($h is the bar length, $w its thickness)
    return sprintf('M%.1f %.1fH%.1fQ%.1f %.1f %.1f %.1fV%.1fQ%.1f %.1f %.1f %.1fH%.1fZ',
        $x, $y, $x + $h - $r, $x + $h, $y, $x + $h, $y + $r, $y + $w - $r, $x + $h, $y + $w, $x + $h - $r, $y + $w, $x);
}

/**
 * Vertical columns over time. $points: list of [label, value, tooltip label].
 * Labels the latest and the highest value; the axis and tooltips carry the rest.
 */
function column_chart(array $points, string $title, ?callable $format = null): string
{
    $format ??= fn($v) => number_format($v);
    $w = 560; $h = 200; $left = 36; $right = 8; $top = 18; $bottom = 26;
    $plotW = $w - $left - $right; $plotH = $h - $top - $bottom;
    $max = nice_max(max(array_column($points, 1) ?: [0]));
    $n = max(1, count($points));
    $band = $plotW / $n;
    $barW = min(24, $band - 2);
    $maxIndex = array_keys(array_column($points, 1), max(array_column($points, 1) ?: [0]))[0] ?? -1;

    $svg = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($title) . '">';
    foreach ([0, 0.5, 1] as $t) {
        $y = $top + $plotH * (1 - $t);
        $svg .= sprintf('<line class="chart-rule" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $left, $w - $right, $y, $y);
        $svg .= sprintf('<text class="chart-tick" x="%d" y="%.1f" text-anchor="end" dominant-baseline="middle">%s</text>', $left - 6, $y, e(compact_number($max * $t)));
    }
    foreach ($points as $i => [$label, $value, $long]) {
        $x = $left + $band * $i + ($band - $barW) / 2;
        $barH = $plotH * ($value / $max);
        $y = $top + $plotH - $barH;
        $svg .= '<g class="chart-mark"><title>' . e($long . ': ' . $format($value)) . '</title>';
        $svg .= sprintf('<rect class="chart-hit" x="%.1f" y="%d" width="%.1f" height="%d"/>', $left + $band * $i, $top, $band, $plotH);
        $svg .= '<path class="chart-bar" d="' . bar_path($x, $y, $barW, $barH) . '"/>';
        if ($value > 0 && ($i === $n - 1 || $i === $maxIndex)) {
            $svg .= sprintf('<text class="chart-value" x="%.1f" y="%.1f" text-anchor="middle">%s</text>', $x + $barW / 2, $y - 5, e($format($value)));
        }
        $svg .= '</g>';
        if ($n <= 12 || $i % 2 === 0) {
            $svg .= sprintf('<text class="chart-tick" x="%.1f" y="%d" text-anchor="middle">%s</text>', $x + $barW / 2, $h - 8, e($label));
        }
    }
    return $svg . '</svg>' . chart_table($points, $format);
}

/** Horizontal bars for categories, largest first. $points: list of [label, value]. */
function bar_chart(array $points, string $title, ?callable $format = null): string
{
    $format ??= fn($v) => number_format($v);
    if (!$points) {
        return '<p class="muted">No data yet.</p>';
    }
    $rowH = 30; $barW = 18; $labelW = 150; $valueW = 70; $w = 560;
    $h = count($points) * $rowH;
    $plotW = $w - $labelW - $valueW;
    $max = max(array_column($points, 1)) ?: 1;

    $svg = '<svg class="chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . e($title) . '">';
    foreach ($points as $i => [$label, $value]) {
        $y = $i * $rowH + ($rowH - $barW) / 2;
        $len = $plotW * ($value / $max);
        $svg .= '<g class="chart-mark"><title>' . e($label . ': ' . $format($value)) . '</title>';
        $svg .= sprintf('<rect class="chart-hit" x="0" y="%d" width="%d" height="%d"/>', $i * $rowH, $w, $rowH);
        $svg .= sprintf('<text class="chart-label" x="%d" y="%.1f" text-anchor="end" dominant-baseline="middle">%s</text>', $labelW - 10, $y + $barW / 2, e(mb_strimwidth($label, 0, 22, '…')));
        $svg .= '<path class="chart-bar" d="' . bar_path($labelW, $y, $barW, $len, 'right') . '"/>';
        $svg .= sprintf('<text class="chart-value" x="%.1f" y="%.1f" dominant-baseline="middle">%s</text>', $labelW + $len + 6, $y + $barW / 2, e($format($value)));
        $svg .= '</g>';
    }
    return $svg . '</svg>' . chart_table($points, $format);
}

function chart_table(array $points, callable $format): string
{
    $html = '<details class="chart-table"><summary>Show as table</summary><table class="table"><tbody>';
    foreach ($points as $p) {
        $html .= '<tr><td>' . e($p[2] ?? $p[0]) . '</td><td class="num">' . e($format($p[1])) . '</td></tr>';
    }
    return $html . '</tbody></table></details>';
}
