<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('licenses.view');

$q = input('q');
$status = input('status');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = "(l.license_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
    array_push($params, "%$q%", "%$q%");
}
match ($status) {
    'active' => $conditions[] = "l.status = 'active' AND l.expires_on >= CURDATE()",
    'expiring' => $conditions[] = "l.status = 'active' AND l.expires_on BETWEEN CURDATE() AND CURDATE() + INTERVAL 60 DAY",
    'expired' => $conditions[] = "l.status = 'active' AND l.expires_on < CURDATE()",
    'suspended', 'revoked' => $conditions[] = 'l.status = ' . db()->quote($status),
    default => null,
};
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM licenses l JOIN civilians c ON c.id = l.civilian_id $where";

$result = paginate(
    "SELECT l.*, c.first_name, c.last_name $from ORDER BY l.expires_on",
    "SELECT COUNT(*) $from",
    $params
);

$types = ['car' => 'Car', 'motorcycle' => 'Motorcycle', 'scooter' => 'Scooter', 'truck' => 'Truck'];

page_start('Licenses', 'licenses');
page_header(
    'Licenses',
    'Driving licenses issued to people in the register',
    can('licenses.manage') ? button_link('Issue license', url('registry/license_form.php'), 'primary', 'plus') : ''
);
echo search_bar('License number or holder', ['status' => [
    '' => 'All statuses', 'active' => 'Valid', 'expiring' => 'Expiring in 60 days', 'expired' => 'Expired',
    'suspended' => 'Suspended', 'revoked' => 'Revoked',
]]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Number</th><th>Holder</th><th>Type</th><th>Issued</th><th>Expires</th><th>Status</th><?php if (can('licenses.manage')): ?><th><span class="sr-only">Actions</span></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $l): ?>
                <tr>
                    <td class="code"><?= e($l['license_number']) ?></td>
                    <td><?= person_link($l) ?></td>
                    <td><?= e($types[$l['license_type']] ?? $l['license_type']) ?></td>
                    <td><?= e(format_date($l['issued_on'])) ?></td>
                    <td><?= e(format_date($l['expires_on'])) ?></td>
                    <td><?= badge(license_status($l)) ?></td>
                    <?php if (can('licenses.manage')): ?>
                        <td class="cell-actions"><a href="<?= e(url('registry/license_form.php', ['id' => $l['id']])) ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No licenses match these filters.' : 'No licenses have been issued yet.') ?>
<?php endif; ?>
<?php page_end();
