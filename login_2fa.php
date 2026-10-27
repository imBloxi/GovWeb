<?php
// Second sign-in step: a code from the authenticator app, or a recovery code.
require __DIR__ . '/app/bootstrap.php';

const TWO_FACTOR_TIMEOUT = 300; // seconds to enter the code after the password

$pending = $_SESSION['two_factor'] ?? null;
if (current_user()) {
    redirect('index.php');
}
if (!$pending || time() - $pending['started'] > TWO_FACTOR_TIMEOUT) {
    unset($_SESSION['two_factor']);
    if ($pending) flash('warning', 'That took too long. Sign in again.');
    redirect('login.php');
}

$user = db_one('SELECT * FROM users WHERE id = ?', [$pending['user_id']]);
if (!$user || !two_factor_enabled($user)) {
    unset($_SESSION['two_factor']);
    redirect('login.php');
}

$error = null;
$useRecovery = input('method') === 'recovery';

if (is_post()) {
    if (is_locked($user)) {
        unset($_SESSION['two_factor']);
        flash('error', 'Too many failed attempts. Try again in ' . LOCKOUT_MINUTES . ' minutes.');
        redirect('login.php');
    }
    $code = input('code');
    $ok = false;
    if ($useRecovery) {
        $ok = use_recovery_code((int) $user['id'], $code);
        if ($ok) {
            $left = (int) db_value('SELECT COUNT(*) FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$user['id']]);
            audit('update', 'user', (int) $user['id'], "{$user['username']} used a recovery code ($left left)", (int) $user['id']);
            flash('warning', $left
                ? "You used a recovery code. $left left. If you lost your phone, turn two-factor off and on again on your account page."
                : 'You used your last recovery code. Make new ones on your account page now.');
        }
    } else {
        $step = totp_verify($user['totp_secret'], $code, $user['totp_last_step'] !== null ? (int) $user['totp_last_step'] : null);
        if ($step !== null) {
            db_run('UPDATE users SET totp_last_step = ? WHERE id = ?', [$step, $user['id']]);
            $ok = true;
        }
    }
    if ($ok) {
        complete_login($user);
        redirect_after_login($user);
    }
    record_failed_login($user, 'two-factor code');
    $error = $useRecovery ? 'That recovery code is wrong or already used.' : 'That code is wrong or expired. Enter the newest code from your app.';
}

page_start('Two-factor sign-in');
?>
<div class="auth-card">
    <div class="brand brand-lg">
        <span class="brand-mark" aria-hidden="true"></span>
        <span><?= e(config('app_name')) ?></span>
    </div>
    <h1><?= $useRecovery ? 'Enter a recovery code' : 'Enter your code' ?></h1>
    <p class="page-subtitle">
        <?= $useRecovery
            ? 'Use one of the recovery codes you saved when you turned on two-factor sign-in. Each works once.'
            : 'Open your authenticator app and enter the 6-digit code for ' . e(config('app_name')) . '.' ?>
    </p>
    <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="method" value="<?= $useRecovery ? 'recovery' : 'app' ?>">
        <div class="field">
            <label for="code"><?= $useRecovery ? 'Recovery code' : 'Code' ?></label>
            <?php if ($useRecovery): ?>
                <input id="code" name="code" autocomplete="off" placeholder="xxxxx-xxxxx" required autofocus>
            <?php else: ?>
                <input id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" placeholder="123456" required autofocus>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Verify and sign in</button>
    </form>
    <p class="auth-alt">
        <?php if ($useRecovery): ?>
            <a href="<?= e(url('login_2fa.php')) ?>">Use the code from my app instead</a>
        <?php else: ?>
            <a href="<?= e(url('login_2fa.php', ['method' => 'recovery'])) ?>">I can't use my phone</a>
        <?php endif; ?>
    </p>
</div>
<?php page_end();
