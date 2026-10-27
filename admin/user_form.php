<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$me = require_can('users.manage');

$id = input_int('id');
$account = $id ? (db_one('SELECT * FROM users WHERE id = ?', [$id]) ?? abort(404)) : null;
$isSelf = $account && (int) $account['id'] === (int) $me['id'];
$errors = [];
$values = $account ?? ['role' => 'officer'];

if (is_post() && input('_action') === 'delete' && $account) {
    if ($isSelf) {
        abort(403, 'You can\'t delete your own account.');
    }
    db_delete('users', $id);
    audit('delete', 'user', $id, "Deleted user {$account['username']}");
    flash('success', "User {$account['username']} was deleted.");
    redirect('admin/users.php');
}

if (is_post() && input('_action') === 'unlock' && $account) {
    db_run('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?', [$id]);
    audit('update', 'user', $id, "Unlocked user {$account['username']}");
    flash('success', "{$account['username']} can sign in again.");
    redirect('admin/user_form.php', ['id' => $id]);
}

if (is_post() && !in_array(input('_action'), ['delete', 'unlock'], true)) {
    [$data, $errors] = validate([
        'username'    => 'required|min:3|max:50',
        'full_name'   => 'required|max:120',
        'role'        => 'required|in:' . implode(',', array_keys(ROLES)),
        'civilian_id' => 'int',
    ]);
    if ($data['username'] && !preg_match('/^[a-z0-9._-]+$/', $data['username'])) {
        $errors['username'] = 'Use lowercase letters, numbers, dots, dashes or underscores.';
    }
    if ($isSelf && $data['role'] !== 'admin') {
        $errors['role'] = 'You can\'t remove your own administrator role.';
    }
    if ($data['role'] === 'civilian' && !$data['civilian_id']) {
        $errors['civilian_id'] = 'Link a civilian account to a person in the register.';
    }
    if ($data['role'] !== 'civilian') {
        $data['civilian_id'] = null;
    }
    $password = (string) ($_POST['password'] ?? '');
    if (!$account && $password === '') {
        $errors['password'] = 'Password is required.';
    } elseif ($password !== '' && mb_strlen($password) < 10) {
        $errors['password'] = 'Use at least 10 characters.';
    }
    $values = $data;

    if (!$errors) {
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        try {
            if ($account) {
                db_update('users', $id, $data);
                audit('update', 'user', $id, "Updated user {$data['username']}" . ($password !== '' ? ' and reset their password' : ''));
                flash('success', 'User saved.');
            } else {
                $id = db_insert('users', $data);
                audit('create', 'user', $id, "Created user {$data['username']} (" . role_label($data['role']) . ')');
                flash('success', "{$data['username']} can now sign in.");
            }
            redirect('admin/users.php');
        } catch (PDOException $e) {
            if (!is_duplicate($e)) throw $e;
            $errors['username'] = 'This username is taken.';
        }
    }
}

$title = $account ? "Edit {$account['username']}" : 'Add user';
form_context($values, $errors);
page_start($title, 'users');
?>
<p class="crumbs"><a href="<?= e(url('admin/users.php')) ?>"><?= icon('arrow-left') ?>Users</a></p>
<?php page_header($title, $account && $account['last_login_at'] ? 'Last signed in ' . format_datetime($account['last_login_at']) : ''); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= field('username', 'Username', ['required' => true, 'autocomplete' => 'off']) ?>
        <?= field('full_name', 'Full name', ['required' => true, 'autocomplete' => 'off']) ?>
        <?= field('role', 'Role', ['type' => 'select', 'required' => true, 'options' => ROLES]) ?>
        <?= field('civilian_id', 'Linked person', ['type' => 'select', 'blank' => 'None', 'options' => civilian_options(), 'hint' => 'Required for civilian accounts.']) ?>
        <?= field('password', $account ? 'New password' : 'Password', [
            'type' => 'password', 'required' => !$account, 'autocomplete' => 'new-password', 'wide' => true,
            'hint' => $account ? 'Leave empty to keep the current password.' : 'At least 10 characters. Share it with the person securely.',
        ]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $account ? 'Save changes' : 'Add user' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('admin/users.php')) ?>">Cancel</a>
        </div>
    </form>
</section>

<?php if ($account && $account['locked_until'] && strtotime($account['locked_until']) > time()): ?>
    <section class="panel">
        <h2 class="panel-title">Locked after failed sign-ins</h2>
        <p class="muted">Locked until <?= e(format_datetime($account['locked_until'])) ?>.</p>
        <?= action_form('', ['_action' => 'unlock', 'id' => $id], 'Unlock now', 'secondary') ?>
    </section>
<?php endif; ?>

<?php if ($account && !$isSelf): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete user</h2>
        <p class="muted">Records they created are kept.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete user', 'danger', "Delete user {$account['username']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
