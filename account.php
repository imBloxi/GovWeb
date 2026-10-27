<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_login();
$errors = [];

if (is_post()) {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $hash = db_value('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);

    if (!password_verify($current, $hash)) {
        $errors['current_password'] = 'This is not your current password.';
    }
    if (mb_strlen($new) < 10) {
        $errors['new_password'] = 'Use at least 10 characters.';
    } elseif ($new === $current) {
        $errors['new_password'] = 'Choose a password different from your current one.';
    }
    if ($new !== $confirm) {
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

form_context([], $errors);
page_start('Your account', 'account');
page_header('Your account', $user['full_name'] . ', ' . role_label($user['role']) . ', signed in as ' . $user['username']);
?>
<section class="panel form-panel">
    <h2 class="panel-title">Change password</h2>
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid form-grid-single">
        <?= csrf_field() ?>
        <?= field('current_password', 'Current password', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
        <?= field('new_password', 'New password', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.']) ?>
        <?= field('confirm_password', 'Repeat new password', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password']) ?>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Change password</button></div>
    </form>
</section>
<?php page_end();
