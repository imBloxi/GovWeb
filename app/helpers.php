<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape for HTML output. Use on every value printed into a page. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL path where the app is mounted, e.g. "" or "/GovWeb".
 * Worked out from the running script so the app runs from any folder.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $root = str_replace('\\', '/', APP_ROOT);
    $relative = str_starts_with($script, $root) ? substr($script, strlen($root)) : '';
    $name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = ($relative !== '' && str_ends_with($name, $relative))
        ? substr($name, 0, -strlen($relative))
        : '';
    return rtrim($base, '/');
}

function url(string $path = '', array $query = []): string
{
    $url = base_path() . '/' . ltrim($path, '/');
    $query = array_filter($query, fn($v) => $v !== null && $v !== '');
    return $query ? $url . '?' . http_build_query($query) : $url;
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $version = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . $path) . '?v=' . $version;
}

function redirect(string $path, array $query = []): never
{
    header('Location: ' . url($path, $query));
    exit;
}

/** Redirect back to the page that sent the form, or to a fallback. */
function back(string $fallback = 'index.php'): never
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($referer && parse_url($referer, PHP_URL_HOST) === $host) {
        header('Location: ' . $referer);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Trimmed string from GET/POST, or the default. */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function input_int(string $key): ?int
{
    $value = filter_var(input($key), FILTER_VALIDATE_INT);
    return $value === false ? null : $value;
}

/** A random, readable code like "W-26-7Q4K9P" (no 0/O/1/I to avoid misreading). */
function random_code(string $prefix, int $length = 6): string
{
    $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return sprintf('%s-%s-%s', $prefix, date('y'), $code);
}

function format_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '';
    }
    $time = strtotime($date);
    return $time ? date($format, $time) : $date;
}

function format_datetime(?string $date): string
{
    return format_date($date, 'j M Y, H:i');
}

function format_money(int $cents): string
{
    return '€' . number_format($cents / 100, 2, '.', ' ');
}

/** "12.50" or "12,50" -> 1250. Returns null if it isn't a positive amount. */
function parse_money(?string $value): ?int
{
    $value = str_replace([' ', ','], ['', '.'], (string) $value);
    if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $value)) {
        return null;
    }
    $cents = (int) round(((float) $value) * 100);
    return $cents > 0 ? $cents : null;
}

function time_ago(?string $date): string
{
    if (!$date) {
        return '';
    }
    $seconds = time() - strtotime($date);
    return match (true) {
        $seconds < 60 => 'just now',
        $seconds < 3600 => intdiv($seconds, 60) . ' min ago',
        $seconds < 86400 => intdiv($seconds, 3600) . ' h ago',
        $seconds < 86400 * 7 => intdiv($seconds, 86400) . ' d ago',
        default => format_date($date),
    };
}

function age(string $dob): int
{
    return (new DateTimeImmutable($dob))->diff(new DateTimeImmutable('today'))->y;
}

function full_name(array $row, string $prefix = ''): string
{
    return trim(($row[$prefix . 'first_name'] ?? '') . ' ' . ($row[$prefix . 'last_name'] ?? ''));
}

function abort(int $status, string $message = ''): never
{
    http_response_code($status);
    $titles = [403 => 'Access denied', 404 => 'Not found', 419 => 'Session expired', 500 => 'Something went wrong'];
    $title = $titles[$status] ?? 'Error';
    $message = $message ?: match ($status) {
        403 => 'Your role does not have access to this page.',
        404 => 'This page or record does not exist.',
        419 => 'The form expired. Go back, reload the page and try again.',
        default => 'The server hit an error. Try again in a moment.',
    };
    render_error_page($status, $title, $message);
    exit;
}

function handle_exception(Throwable $e): void
{
    error_log((string) $e);
    $message = config('debug')
        ? $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()
        : '';
    if ($e instanceof PDOException && str_contains($e->getMessage(), 'SQLSTATE[HY000] [')) {
        $message = 'Could not connect to the database. Check the settings in app/config.php '
            . 'and that you imported database/schema.sql.';
    }
    abort(500, $message);
}

/**
 * Send rows as a CSV download and stop. Every export is logged, since it
 * copies personal data out of the system.
 */
function export_csv(string $name, array $header, array $rows): never
{
    audit('export', $name, null, 'Downloaded ' . count($rows) . " rows of $name as CSV");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '-' . date('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 (ā, š, ž) correctly
    $safe = function ($value): string {
        $value = (string) ($value ?? '');
        // Stop spreadsheets from running cell text as a formula.
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    };
    fputcsv($out, $header, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($out, array_map($safe, $row), ',', '"', '');
    }
    fclose($out);
    exit;
}
