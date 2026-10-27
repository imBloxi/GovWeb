<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_login();
$row = db_one('SELECT * FROM users WHERE id = ?', [$user['id']]);
$action = input('_action');
$errors = [];
$newRecoveryCodes = null;

/** Most account changes ask for the current password again. */
$passwordOk = fn(string $field = 'current_password') => password_verify((string) ($_POST[$field] ?? ''), $row['password_hash']);

if (is_post() && $action === 'password') {
    $new = (string) ($_POST['new_password'] ?? '');
    if (!$passwordOk()) {
        $errors['current_password'] = 'This is not your current password.';
    }
    if (mb_strlen($new) < 10) {
        $errors['new_password'] = 'Use at least 10 characters.';
    } elseif ($new === ($_POST['current_password'] ?? '')) {
        $errors['new_password'] = 'Choose a password different from your current one.';
    }
    if ($new !== ($_POST['confirm_password'] ?? '')) {
        $errors['confirm_password'] = 'The passwords don\'t match.';
    }
    if (!$errors) {
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        audit('update', 'user', $user['id'], "{$user['username']} changed their password");
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect('account.php');
    }
}

// Step 1 of turning on two-factor: make a secret and keep it in the session until confirmed.
if (is_post() && $action === '2fa_start' && !two_factor_enabled($row)) {
    $_SESSION['totp_setup'] = totp_new_secret();
    redirect('account.php', ['setup' => 1]);
}

// Step 2: the person proves their app works by entering a code.
if (is_post() && $action === '2fa_confirm' && !two_factor_enabled($row)) {
    $secret = $_SESSION['totp_setup'] ?? null;
    $step = $secret ? totp_verify($secret, input('code')) : null;
    if ($step === null) {
        $errors['code'] = 'That code doesn\'t match. Check the time on your phone is correct and enter the newest code.';
    } else {
        db_run('UPDATE users SET totp_secret = ?, totp_enabled_at = NOW(), totp_last_step = ? WHERE id = ?', [$secret, $step, $user['id']]);
        unset($_SESSION['totp_setup']);
        $newRecoveryCodes = generate_recovery_codes((int) $user['id']);
        audit('update', 'user', $user['id'], "{$user['username']} turned on two-factor sign-in");
        $row = db_one('SELECT * FROM users WHERE id = ?', [$user['id']]);
    }
}

if (is_post() && $action === '2fa_codes' && two_factor_enabled($row)) {
    if (!$passwordOk('codes_password')) {
        $errors['codes_password'] = 'This is not your current password.';
    } else {
        $newRecoveryCodes = generate_recovery_codes((int) $user['id']);
        audit('update', 'user', $user['id'], "{$user['username']} made new recovery codes");
    }
}

if (is_post() && $action === '2fa_disable' && two_factor_enabled($row)) {
    if (in_array($user['role'], config('require_two_factor', []), true)) {
        abort(403, 'Your role must use two-factor sign-in, so it can\'t be turned off.');
    }
    if (!$passwordOk('disable_password')) {
        $errors['disable_password'] = 'This is not your current password.';
    } else {
        db_run('UPDATE users SET totp_secret = NULL, totp_enabled_at = NULL, totp_last_step = NULL WHERE id = ?', [$user['id']]);
        db_run('DELETE FROM recovery_codes WHERE user_id = ?', [$user['id']]);
        audit('update', 'user', $user['id'], "{$user['username']} turned off two-factor sign-in");
        flash('success', 'Two-factor sign-in is off.');
        redirect('account.php');
    }
}

$setupSecret = (!two_factor_enabled($row) && input('setup') === '1' || $action === '2fa_confirm') ? ($_SESSION['totp_setup'] ?? null) : null;
$codesLeft = (int) db_value('SELECT COUNT(*) FROM recovery_codes WHERE user_id = ? AND used_at IS NULL', [$user['id']]);

form_context([], $errors);
page_start('Your account', 'account', ['scripts' => $setupSecret ? ['vendor/qrcode-generator-1.4.4.js'] : []]);
page_header('Your account', $user['full_name'] . ', ' . role_label($user['role']) . ', signed in as ' . $user['username']);
?>
<section class="panel form-panel" id="two-factor">
    <div class="panel-head">
        <h2 class="panel-title">Two-factor sign-in</h2>
        <?= two_factor_enabled($row) ? badge('active', 'On') : badge('cancelled', 'Off') ?>
    </div>

    <?php if ($newRecoveryCodes): ?>
        <div class="alert alert-success" role="status">Two-factor sign-in is on. Save these recovery codes now; they won't be shown again.</div>
        <p class="muted">Each code works once, if you can't use your phone. Store them somewhere safe, like a password manager or a printed sheet.</p>
        <ol class="recovery-codes">
            <?php foreach ($newRecoveryCodes as $c): ?><li class="code"><?= e($c) ?></li><?php endforeach; ?>
        </ol>
        <p><button class="btn btn-secondary" type="button" data-print><?= icon('printer') ?><span>Print codes</span></button></p>

    <?php elseif ($setupSecret): ?>
        <ol class="setup-steps">
            <li>
                <p>Scan this with an authenticator app, such as Google Authenticator, Microsoft Authenticator or 1Password.</p>
                <div class="qr" data-qr="<?= e(totp_uri($setupSecret, $user['username'])) ?>" role="img" aria-label="QR code for your authenticator app"></div>
                <details>
                    <summary>Can't scan? Enter the key by hand</summary>
                    <p class="code setup-key"><?= e(trim(chunk_split($setupSecret, 4, ' '))) ?></p>
                </details>
            </li>
            <li>
                <p>Enter the 6-digit code the app shows.</p>
                <form method="post" class="form-grid form-grid-single" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="2fa_confirm">
                    <?= field('code', 'Code', ['required' => true, 'autocomplete' => 'one-time-code', 'attrs' => 'inputmode="numeric" maxlength="7" class="code-input"']) ?>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Turn on</button>
                        <a class="btn btn-ghost" href="<?= e(url('account.php')) ?>">Cancel</a>
                    </div>
                </form>
            </li>
        </ol>

    <?php elseif (two_factor_enabled($row)): ?>
        <p class="muted">On since <?= e(format_date($row['totp_enabled_at'])) ?>. You have <?= $codesLeft === 1 ? '1 recovery code' : "$codesLeft recovery codes" ?> left.</p>
        <div class="split-forms">
            <form method="post" class="form-grid form-grid-single" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="2fa_codes">
                <?= field('codes_password', 'Current password', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
                <div class="form-actions"><button class="btn btn-secondary" type="submit">Make new recovery codes</button></div>
            </form>
            <?php if (!in_array($user['role'], config('require_two_factor', []), true)): ?>
                <form method="post" class="form-grid form-grid-single" novalidate data-confirm="Turn off two-factor sign-in? Your account will only be protected by your password.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="2fa_disable">
                    <?= field('disable_password', 'Current password', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
                    <div class="form-actions"><button class="btn btn-danger" type="submit">Turn off</button></div>
                </form>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p class="muted">Ask for a code from your phone each time you sign in, so a stolen password alone can't open your account.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="2fa_start">
            <button class="btn btn-primary" type="submit">Set up two-factor sign-in</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel form-panel">
    <h2 class="panel-title">Change password</h2>
    <form method="post" class="form-grid form-grid-single" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="password">
        <?= field('current_password', 'Current password', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
        <?= field('new_password', 'New password', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.']) ?>
        <?= field('confirm_password', 'Repeat new password', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password']) ?>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Change password</button></div>
    </form>
</section>
<?php page_end();
