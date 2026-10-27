<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('users.manage');

$q = input('q');
$role = input('role');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(u.username LIKE ? OR u.full_name LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (isset(ROLES[$role])) {
    $conditions[] = 'u.role = ?';
    $params[] = $role;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$result = paginate(
    "SELECT u.*, c.first_name, c.last_name FROM users u LEFT JOIN civilians c ON c.id = u.civilian_id $where ORDER BY u.username",
    "SELECT COUNT(*) FROM users u $where",
    $params
);

page_start('Users', 'users');
page_header('Users', 'Who can sign in and what each role can do', button_link('Add user', url('admin/user_form.php'), 'primary', 'plus'));
echo search_bar('Username or name', ['role' => ['' => 'All roles'] + ROLES]);
?>
<div class="table-wrap">
    <table class="table table-hover">
        <thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Two-factor</th><th>Last sign-in</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($result['rows'] as $u): ?>
            <tr>
                <td class="code"><a class="row-link" href="<?= e(url('admin/user_form.php', ['id' => $u['id']])) ?>"><?= e($u['username']) ?></a></td>
                <td><?= e($u['full_name']) ?><?php if ($u['civilian_id']): ?><p class="muted small">Linked to <?= person_link($u) ?></p><?php endif; ?></td>
                <td><?= e(role_label($u['role'])) ?></td>
                <td><?= $u['totp_enabled_at'] ? badge('active', 'On') : '<span class="muted">Off</span>' ?></td>
                <td><?= $u['last_login_at'] ? e(time_ago($u['last_login_at'])) : '<span class="muted">Never</span>' ?></td>
                <td><?= ($u['locked_until'] && strtotime($u['locked_until']) > time()) ? badge('revoked', 'Locked') : badge('active') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pagination($result) ?>

<section class="panel roles-help">
    <h2 class="panel-title">What each role can do</h2>
    <dl class="facts">
        <div><dt>Administrator</dt><dd>Everything, including users and the activity log.</dd></div>
        <div><dt>Registrar</dt><dd>Registers people, licenses, businesses and public works.</dd></div>
        <div><dt>Police officer</dt><dd>Dispatch, criminal records, operations. Requests and serves warrants.</dd></div>
        <div><dt>Judge</dt><dd>Case files and court documents. Issues or cancels warrants.</dd></div>
        <div><dt>Civilian</dt><dd>Sees their own record, licenses, businesses and court documents.</dd></div>
    </dl>
</section>
<?php page_end();
