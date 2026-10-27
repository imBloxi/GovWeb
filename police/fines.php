<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('fines.view');

if (is_post()) {
    $fine = db_one("SELECT * FROM fines WHERE id = ? AND status = 'unpaid'", [input_int('id') ?? 0])
        ?? abort(404, 'This fine is already paid or cancelled.');
    $action = input('_action');
    if ($action === 'paid' && can('fines.record_payment')) {
        $paidOn = is_valid_date(input('paid_on')) && input('paid_on') <= date('Y-m-d') ? input('paid_on') : date('Y-m-d');
        db_update('fines', (int) $fine['id'], ['status' => 'paid', 'paid_on' => $paidOn]);
        audit('update', 'fine', (int) $fine['id'], "Recorded payment for fine {$fine['code']}");
        notify_civilian((int) $fine['civilian_id'], "Payment received for fine {$fine['code']}", format_money((int) $fine['amount_cents']), 'me.php');
        flash('success', "Fine {$fine['code']} marked as paid.");
    } elseif ($action === 'cancel' && can('fines.cancel')) {
        db_update('fines', (int) $fine['id'], ['status' => 'cancelled']);
        audit('update', 'fine', (int) $fine['id'], "Cancelled fine {$fine['code']}");
        notify_civilian((int) $fine['civilian_id'], "Fine {$fine['code']} was cancelled", 'You don\'t need to pay it.', 'me.php');
        flash('success', "Fine {$fine['code']} cancelled.");
    } else {
        abort(403);
    }
    back('police/fines.php');
}

$q = input('q');
$status = input('status');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = "(f.code LIKE ? OR f.reason LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR v.plate LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
match ($status) {
    'unpaid' => $conditions[] = "f.status = 'unpaid' AND f.due_on >= CURDATE()",
    'overdue' => $conditions[] = "f.status = 'unpaid' AND f.due_on < CURDATE()",
    'paid', 'cancelled' => $conditions[] = 'f.status = ' . db()->quote($status),
    default => null,
};
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM fines f JOIN civilians c ON c.id = f.civilian_id LEFT JOIN vehicles v ON v.id = f.vehicle_id
         LEFT JOIN users u ON u.id = f.issued_by $where";
$sql = "SELECT f.*, c.first_name, c.last_name, v.plate, u.full_name AS issuer $from ORDER BY f.status = 'unpaid' DESC, f.due_on";

if (input('export') === 'csv') {
    require_can('data.export');
    export_csv('fines', ['Code', 'Person', 'Plate', 'Reason', 'Amount (EUR)', 'Issued', 'Due', 'Status', 'Paid on'],
        array_map(fn($f) => [$f['code'], full_name($f), $f['plate'], $f['reason'], number_format($f['amount_cents'] / 100, 2, '.', ''), $f['issued_on'], $f['due_on'], fine_status($f), $f['paid_on']], db_all($sql, $params)));
}

$result = paginate($sql, "SELECT COUNT(*) $from", $params);
$totals = db_one("SELECT COALESCE(SUM(amount_cents), 0) AS owed, COALESCE(SUM(CASE WHEN due_on < CURDATE() THEN amount_cents END), 0) AS overdue FROM fines WHERE status = 'unpaid'");

$actions = (can('data.export') ? button_link('Download CSV', url('police/fines.php', $_GET + ['export' => 'csv']), 'secondary', 'download') : '')
    . (can('fines.issue') ? button_link('Issue fine', url('police/fine_form.php'), 'primary', 'plus') : '');

page_start('Fines', 'fines');
page_header('Fines', format_money((int) $totals['owed']) . ' unpaid, of which ' . format_money((int) $totals['overdue']) . ' overdue', $actions);
echo search_bar('Code, name, plate or reason', ['status' => [
    '' => 'All fines', 'unpaid' => 'Unpaid, not yet due', 'overdue' => 'Overdue', 'paid' => 'Paid', 'cancelled' => 'Cancelled',
]]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Code</th><th>Person</th><th>Reason</th><th class="num">Amount</th><th>Due</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $f): ?>
                <tr>
                    <td class="code"><?= e($f['code']) ?></td>
                    <td><?= person_link($f) ?><?php if ($f['plate']): ?><p class="muted small code"><?= e($f['plate']) ?></p><?php endif; ?></td>
                    <td><?= e($f['reason']) ?><p class="muted small">Issued <?= e(format_date($f['issued_on'])) ?> by <?= e($f['issuer'] ?? 'unknown') ?></p></td>
                    <td class="num nowrap"><?= e(format_money((int) $f['amount_cents'])) ?></td>
                    <td class="nowrap"><?= e(format_date($f['due_on'])) ?></td>
                    <td><?= badge(fine_status($f)) ?><?php if ($f['paid_on']): ?><p class="muted small"><?= e(format_date($f['paid_on'])) ?></p><?php endif; ?></td>
                    <td class="cell-actions">
                        <?php if ($f['status'] === 'unpaid'): ?>
                            <?php if (can('fines.record_payment')) echo action_form('', ['_action' => 'paid', 'id' => $f['id']], 'Record payment', 'secondary', "Record payment of " . format_money((int) $f['amount_cents']) . " for {$f['code']}?"); ?>
                            <?php if (can('fines.cancel')) echo action_form('', ['_action' => 'cancel', 'id' => $f['id']], 'Cancel', 'ghost', "Cancel fine {$f['code']}?"); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No fines match these filters.' : 'No fines have been issued.') ?>
<?php endif; ?>
<?php page_end();
