<?php
require __DIR__ . '/app/bootstrap.php';

// One page for both police operations and government public works.
$agency = input('agency') === 'police' ? 'police' : 'government';
$prefix = $agency === 'police' ? 'police_ops' : 'gov_ops';
require_can("$prefix.view");

$q = input('q');
$status = input('status');
$conditions = ['agency = ?'];
$params = [$agency];
if ($q !== '') {
    $conditions[] = '(name LIKE ? OR operation_type LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($status, ['planned', 'active', 'completed', 'cancelled'], true)) {
    $conditions[] = 'status = ?';
    $params[] = $status;
}
$where = 'WHERE ' . implode(' AND ', $conditions);
$result = paginate(
    "SELECT * FROM operations $where ORDER BY FIELD(status, 'active', 'planned', 'completed', 'cancelled'), start_date DESC",
    "SELECT COUNT(*) FROM operations $where",
    $params
);

$title = $agency === 'police' ? 'Police operations' : 'Public works';
page_start($title, $prefix);
page_header(
    $title,
    $agency === 'police' ? 'Patrols, investigations and planned actions' : 'Government projects and public programmes',
    can("$prefix.manage") ? button_link('New operation', url('operation_form.php', ['agency' => $agency]), 'primary', 'plus') : ''
);
echo search_bar('Name or type', ['status' => [
    '' => 'All statuses', 'active' => 'Active', 'planned' => 'Planned', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
]]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Type</th><th>Runs</th><th>Status</th><?php if (can("$prefix.manage")): ?><th><span class="sr-only">Actions</span></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $op): ?>
                <tr>
                    <td>
                        <?= e($op['name']) ?>
                        <?php if ($op['description']): ?><p class="muted small"><?= e($op['description']) ?></p><?php endif; ?>
                    </td>
                    <td><?= e($op['operation_type']) ?></td>
                    <td class="nowrap"><?= e(format_date($op['start_date'])) ?> to <?= $op['end_date'] ? e(format_date($op['end_date'])) : 'ongoing' ?></td>
                    <td><?= badge($op['status']) ?></td>
                    <?php if (can("$prefix.manage")): ?>
                        <td class="cell-actions"><a href="<?= e(url('operation_form.php', ['agency' => $agency, 'id' => $op['id']])) ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No operations match these filters.' : 'No operations yet.') ?>
<?php endif; ?>
<?php page_end();
