<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('vehicles.view');

$q = input('q');
$status = input('status');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = "(v.plate LIKE ? OR v.vin LIKE ? OR CONCAT(v.make, ' ', v.model) LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
    array_push($params, '%' . normalize_plate($q) . '%', "%$q%", "%$q%", "%$q%");
}
if (in_array($status, ['registered', 'stolen', 'deregistered'], true)) {
    $conditions[] = 'v.status = ?';
    $params[] = $status;
} elseif ($status === 'inspection') {
    $conditions[] = "v.status = 'registered' AND v.inspection_until < CURDATE()";
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM vehicles v JOIN civilians c ON c.id = v.owner_id $where";
$sql = "SELECT v.*, c.first_name, c.last_name, v.owner_id AS civilian_id $from ORDER BY v.plate";

if (input('export') === 'csv') {
    require_can('data.export');
    export_csv('vehicles', ['Plate', 'VIN', 'Make', 'Model', 'Year', 'Colour', 'Owner', 'Status', 'Registered', 'Inspection until'],
        array_map(fn($v) => [$v['plate'], $v['vin'], $v['make'], $v['model'], $v['year'], $v['color'], full_name($v), $v['status'], $v['registered_on'], $v['inspection_until']], db_all($sql, $params)));
}

$result = paginate($sql, "SELECT COUNT(*) $from", $params);

$actions = (can('data.export') ? button_link('Download CSV', url('registry/vehicles.php', $_GET + ['export' => 'csv']), 'secondary', 'download') : '')
    . (can('vehicles.manage') ? button_link('Register vehicle', url('registry/vehicle_form.php'), 'primary', 'plus') : '');

page_start('Vehicles', 'vehicles');
page_header('Vehicles', 'Every vehicle registered for road use', $actions);
echo search_bar('Plate, VIN, make or owner', ['status' => [
    '' => 'All vehicles', 'registered' => 'Registered', 'inspection' => 'Inspection overdue', 'stolen' => 'Reported stolen', 'deregistered' => 'Deregistered',
]]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Plate</th><th>Vehicle</th><th>Owner</th><th>Inspection until</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $v): ?>
                <tr>
                    <td class="code"><a class="row-link" href="<?= e(url('registry/vehicle_form.php', ['id' => $v['id']])) ?>"><?= e($v['plate']) ?></a></td>
                    <td><?= e($v['year'] . ' ' . $v['make'] . ' ' . $v['model']) ?></td>
                    <td><?= person_link($v) ?></td>
                    <td><?= $v['inspection_until'] ? e(format_date($v['inspection_until'])) . ($v['inspection_until'] < date('Y-m-d') ? ' ' . badge('expired', 'Overdue') : '') : '' ?></td>
                    <td><?= badge($v['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No vehicles match these filters.' : 'No vehicles are registered yet.') ?>
<?php endif; ?>
<?php page_end();
