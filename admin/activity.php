<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('audit.view');

$q = input('q');
$kind = input('kind');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(a.summary LIKE ? OR u.username LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
match ($kind) {
    'changes' => $conditions[] = "a.action NOT IN ('login', 'logout', 'login_failed')",
    'signins' => $conditions[] = "a.action IN ('login', 'logout', 'login_failed')",
    'failed' => $conditions[] = "a.action = 'login_failed'",
    default => null,
};
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $where";
$result = paginate("SELECT a.*, u.username, u.full_name $from ORDER BY a.id DESC", "SELECT COUNT(*) $from", $params, 50);

page_start('Activity log', 'activity');
page_header('Activity log', 'Every change and sign-in, newest first');
echo search_bar('What changed, or username', ['kind' => [
    '' => 'Everything', 'changes' => 'Changes to records', 'signins' => 'Sign-ins and sign-outs', 'failed' => 'Failed sign-ins',
]]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>When</th><th>Who</th><th>What</th><th>IP address</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $a): ?>
                <tr>
                    <td class="nowrap"><?= e(format_datetime($a['created_at'])) ?></td>
                    <td><?= e($a['full_name'] ?? ($a['action'] === 'login_failed' ? 'Unknown' : 'Deleted user')) ?></td>
                    <td><?= $a['action'] === 'login_failed' ? badge('revoked', 'Failed') . ' ' : '' ?><?= e($a['summary']) ?></td>
                    <td class="code muted"><?= e($a['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' ? "No activity matches \"$q\"." : 'Changes people make will show up here.') ?>
<?php endif; ?>
<?php page_end();
