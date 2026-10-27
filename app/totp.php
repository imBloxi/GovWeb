<?php
declare(strict_types=1);

// Time-based one-time passwords (RFC 6238), the 6-digit codes that
// authenticator apps (Google Authenticator, Microsoft Authenticator, 1Password...) show.

const TOTP_PERIOD = 30;
const TOTP_DIGITS = 6;
const RECOVERY_CODE_COUNT = 10;

function base32_encode(string $bytes): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($bytes) as $byte) {
        $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $out;
}

function base32_decode(string $text): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text))) as $char) {
        $bits .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $out .= chr(bindec($chunk));
        }
    }
    return $out;
}

function totp_new_secret(): string
{
    return base32_encode(random_bytes(20)); // 160 bits, as the RFC recommends
}

function totp_code(string $secret, int $step): string
{
    $hash = hash_hmac('sha1', pack('J', $step), base32_decode($secret), true);
    $offset = ord($hash[19]) & 0x0f;
    $number = (unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff) % (10 ** TOTP_DIGITS);
    return str_pad((string) $number, TOTP_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * Check a code, allowing one step of clock drift either way.
 * Returns the matching time step, or null. Pass the last accepted step
 * so the same code can't be used twice.
 */
function totp_verify(string $secret, string $code, ?int $lastStep = null): ?int
{
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{' . TOTP_DIGITS . '}$/', $code)) {
        return null;
    }
    $now = intdiv(time(), TOTP_PERIOD);
    foreach ([0, -1, 1] as $drift) {
        $step = $now + $drift;
        if ($lastStep !== null && $step <= $lastStep) {
            continue;
        }
        if (hash_equals(totp_code($secret, $step), $code)) {
            return $step;
        }
    }
    return null;
}

/** The link an authenticator app reads from the QR code. */
function totp_uri(string $secret, string $username): string
{
    $issuer = config('app_name');
    return 'otpauth://totp/' . rawurlencode("$issuer:$username")
        . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer)
        . '&period=' . TOTP_PERIOD . '&digits=' . TOTP_DIGITS;
}

/** Make new single-use recovery codes, replacing any old ones. Returns them in plain text, once. */
function generate_recovery_codes(int $userId): array
{
    $codes = [];
    db_run('DELETE FROM recovery_codes WHERE user_id = ?', [$userId]);
    for ($i = 0; $i < RECOVERY_CODE_COUNT; $i++) {
        $code = strtolower(substr(base32_encode(random_bytes(10)), 0, 10));
        $code = substr($code, 0, 5) . '-' . substr($code, 5);
        $codes[] = $code;
        db_insert('recovery_codes', ['user_id' => $userId, 'code_hash' => password_hash($code, PASSWORD_DEFAULT)]);
    }
    return $codes;
}

/** Use up a recovery code. True if it was valid and unused. */
function use_recovery_code(int $userId, string $code): bool
{
    $code = strtolower(trim($code));
    foreach (db_all('SELECT id, code_hash FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$userId]) as $row) {
        if (password_verify($code, $row['code_hash'])) {
            db_run('UPDATE recovery_codes SET used_at = NOW() WHERE id = ?', [$row['id']]);
            return true;
        }
    }
    return false;
}

function two_factor_enabled(array $user): bool
{
    return !empty($user['totp_enabled_at']);
}
