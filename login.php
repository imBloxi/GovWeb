<?php
require __DIR__ . '/app/bootstrap.php';

if (current_user()) {
    redirect('index.php');
}

$error = null;
$username = '';

if (is_post()) {
    $username = input('username');
    $result = attempt_login($username, (string) ($_POST['password'] ?? ''));
    if (is_array($result)) {
        if (($_POST['password'] ?? '') === 'password') {
            flash('warning', 'You signed in with the demo password. Change it on your account page.');
        }
        if (two_factor_enabled($result)) {
            // Password is right; the session stays signed out until the code is checked.
            session_regenerate_id(true);
            $_SESSION['two_factor'] = ['user_id' => (int) $result['id'], 'started' => time()];
            redirect('login_2fa.php');
        }
        complete_login($result);
        redirect_after_login($result);
    }
    $error = $result;
}

page_start('Sign in');
?>
<div class="auth-card">
    <div class="brand brand-lg">
        <span class="brand-mark" aria-hidden="true"></span>
        <span><?= e(config('app_name')) ?></span>
    </div>
    <h1>Sign in</h1>
    <p class="page-subtitle">Civil registry, police and court records.</p>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="stack">
        <?= csrf_field() ?>
        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    </form>

    <?php if (config('show_demo_accounts', true)): ?>
        <details class="demo-accounts">
            <summary>Demo accounts</summary>
            <p>Each uses the password <code>password</code>.</p>
            <ul>
                <li><code>admin</code> Administrator</li>
                <li><code>registrar</code> Registrar</li>
                <li><code>officer</code> Police officer</li>
                <li><code>judge</code> Judge</li>
                <li><code>john</code> Civilian</li>
            </ul>
        </details>
    <?php endif; ?>
</div>
<?php page_end();
