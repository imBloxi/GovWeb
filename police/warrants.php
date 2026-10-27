<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('warrants.view');

// Allowed status changes: [from => [to => permission]]
const WARRANT_TRANSITIONS = [
    'pending' => ['issued' => 'warrants.decide', 'cancelled' => 'warrants.decide'],
    'issued'  => ['served' => 'warrants.serve', 'cancelled' => 'warrants.decide'],
];
const WARRANT_ACTION_LABELS = ['issued' => 'Issue', 'cancelled' => 'Cancel', 'served' => 'Mark served'];

if (is_post()) {
    $warrant = db_one('SELECT * FROM warrants WHERE id = ?', [input_int('id') ?? 0]) ?? abort(404);
    $to = input('status');
    $permission = WARRANT_TRANSITIONS[$warrant['status']][$to] ?? null;
    if (!$permission || !can($permission)) {
        abort(403, 'You can\'t change this warrant to that status.');
    }
    $changes = ['status' => $to];
    if ($permission === 'warrants.decide') {
        $changes['decided_by'] = $user['id'];
    }
    db_update('warrants', (int) $warrant['id'], $changes);
    audit('update', 'warrant', (int) $warrant['id'], "Warrant {$warrant['code']} changed from {$warrant['status']} to $to");
    if ($permission === 'warrants.decide' && $warrant['requested_by']) {
        notify_user((int) $warrant['requested_by'], "Warrant {$warrant['code']} was $to", $warrant['reason'], 'police/warrants.php?q=' . $warrant['code']);
    }
    flash('success', "Warrant {$warrant['code']} is now $to.");
    back('police/warrants.php');
}

$q = input('q');
$status = input('status');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = "(w.code LIKE ? OR w.reason LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if (in_array($status, ['pending', 'issued', 'served', 'cancelled'], true)) {
    $conditions[] = 'w.status = ?';
    $params[] = $status;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM warrants w JOIN civilians c ON c.id = w.civilian_id
         LEFT JOIN users r ON r.id = w.requested_by LEFT JOIN users d ON d.id = w.decided_by $where";
$result = paginate(
    "SELECT w.*, c.first_name, c.last_name, c.person_code, r.full_name AS requester, d.full_name AS decider $from
     ORDER BY FIELD(w.status, 'pending', 'issued', 'served', 'cancelled'), w.created_at DESC",
    "SELECT COUNT(*) $from",
    $params
);

page_start('Warrants', 'warrants');
page_header(
    'Warrants',
    'Officers request warrants, judges issue them, officers mark them served.',
    can('warrants.request') ? button_link('Request warrant', url('police/warrant_form.php'), 'primary', 'plus') : ''
);
echo search_bar('Code, reason or name', ['status' => [
    '' => 'All statuses', 'pending' => 'Waiting for a judge', 'issued' => 'Issued', 'served' => 'Served', 'cancelled' => 'Cancelled',
]]);
?>
<?php if ($result['rows']): ?>
    <ul class="warrant-list">
        <?php foreach ($result['rows'] as $w): ?>
            <li class="warrant" data-status="<?= e($w['status']) ?>">
                <div class="warrant-main">
                    <p class="warrant-meta"><span class="code"><?= e($w['code']) ?></span> <?= badge($w['status'], $w['status'] === 'pending' ? 'Waiting for a judge' : null) ?></p>
                    <h2 class="warrant-title"><?= e($w['reason']) ?></h2>
                    <p><?= person_link($w) ?> <span class="muted code"><?= e($w['person_code']) ?></span></p>
                    <?php if ($w['description']): ?><p class="muted"><?= e($w['description']) ?></p><?php endif; ?>
                    <p class="muted small">
                        Requested by <?= e($w['requester'] ?? 'unknown') ?> on <?= e(format_date($w['created_at'])) ?><?php if ($w['decider']): ?>, decided by <?= e($w['decider']) ?><?php endif; ?>
                    </p>
                </div>
                <div class="warrant-actions">
                    <?php foreach (WARRANT_TRANSITIONS[$w['status']] ?? [] as $to => $permission):
                        if (!can($permission)) continue; ?>
                        <?= action_form('', ['id' => $w['id'], 'status' => $to], WARRANT_ACTION_LABELS[$to],
                            $to === 'cancelled' ? 'ghost' : 'primary',
                            $to === 'cancelled' ? "Cancel warrant {$w['code']}?" : null) ?>
                    <?php endforeach; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No warrants match these filters.' : 'No warrants yet.') ?>
<?php endif; ?>
<?php page_end();
