<?php
declare(strict_types=1);

const ROLES = [
    'admin'     => 'Administrator',
    'registrar' => 'Registrar',
    'officer'   => 'Police officer',
    'judge'     => 'Judge',
    'civilian'  => 'Civilian',
];

// Which roles may do what. Admins can do everything.
const PERMISSIONS = [
    'civilians.view'    => ['registrar', 'officer', 'judge'],
    'civilians.manage'  => ['registrar'],
    'licenses.view'     => ['registrar', 'officer'],
    'licenses.manage'   => ['registrar'],
    'businesses.view'   => ['registrar', 'officer'],
    'businesses.manage' => ['registrar'],
    'records.view'      => ['officer', 'judge'],
    'records.manage'    => ['officer'],
    'records.delete'    => [],
    'warrants.view'     => ['officer', 'judge'],
    'warrants.request'  => ['officer'],
    'warrants.decide'   => ['judge'],
    'warrants.serve'    => ['officer'],
    'dispatch.use'      => ['officer'],
    'police_ops.view'   => ['officer'],
    'police_ops.manage' => ['officer'],
    'gov_ops.view'      => ['registrar', 'officer', 'judge'],
    'gov_ops.manage'    => ['registrar'],
    'court.view'        => ['judge'],
    'court.manage'      => ['judge'],
    'users.manage'      => [],
    'audit.view'        => [],
    'self.view'         => ['civilian'],
];

const MAX_FAILED_LOGINS = 5;
const LOCKOUT_MINUTES = 10;

function start_session(): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('govweb_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Sign out after 2 hours of inactivity.
    $now = time();
    if (isset($_SESSION['last_seen']) && $now - $_SESSION['last_seen'] > 7200) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = $now;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        abort(419);
    }
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['user_id'] ?? null;
        $user = $id ? db_one('SELECT id, username, full_name, role, civilian_id FROM users WHERE id = ?', [$id]) : null;
    }
    return $user;
}

/**
 * Check a username and password. Returns the user row, or an error string.
 * Locks the account for a while after too many failed attempts.
 */
function attempt_login(string $username, string $password): array|string
{
    $user = db_one('SELECT * FROM users WHERE username = ?', [$username]);
    if (!$user) {
        // Verify against a dummy hash so unknown usernames take as long as wrong passwords.
        password_verify($password, '$2y$10$tJuHx0z2z9vxRGdiI4xqVOqZNmh.iXDRfkwYFk8.L1Txz6.1/diTe');
        return 'Wrong username or password.';
    }
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return 'Too many failed attempts. Try again in ' . LOCKOUT_MINUTES . ' minutes.';
    }
    if (!password_verify($password, $user['password_hash'])) {
        $fails = $user['failed_logins'] + 1;
        $lock = $fails >= MAX_FAILED_LOGINS ? date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60) : null;
        db_run('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?', [$lock ? 0 : $fails, $lock, $user['id']]);
        return 'Wrong username or password.';
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    db_run('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?', [$user['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['csrf']);
    return $user;
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
        redirect('login.php');
    }
    return $user;
}

function can(string $permission): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    if ($user['role'] === 'admin') {
        return $permission !== 'self.view';
    }
    return in_array($user['role'], PERMISSIONS[$permission] ?? [], true);
}

/** Stop the page with 403 unless the user has at least one of the permissions. */
function require_can(string ...$permissions): array
{
    $user = require_login();
    foreach ($permissions as $permission) {
        if (can($permission)) {
            return $user;
        }
    }
    abort(403);
}

function role_label(string $role): string
{
    return ROLES[$role] ?? ucfirst($role);
}
