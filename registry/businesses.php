<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('businesses.view');

$q = input('q');
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE b.name LIKE ? OR b.business_type LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
    $params = array_fill(0, 3, "%$q%");
}
$from = "FROM businesses b JOIN civilians c ON c.id = b.owner_id $where";
$result = paginate("SELECT b.*, c.first_name, c.last_name $from ORDER BY b.name", "SELECT COUNT(*) $from", $params);

page_start('Businesses', 'businesses');
page_header(
    'Businesses',
    'Companies and sole traders in the business register',
    can('businesses.manage') ? button_link('Register business', url('registry/business_form.php'), 'primary', 'plus') : ''
);
echo search_bar('Business name, type or owner');
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Name</th><th>Type</th><th>Owner</th><th>Address</th><th>Registered</th><th>Status</th><?php if (can('businesses.manage')): ?><th><span class="sr-only">Actions</span></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $b): ?>
                <tr>
                    <td><?= e($b['name']) ?></td>
                    <td><?= e($b['business_type']) ?></td>
                    <td><?= person_link($b, '', 'owner_id') ?></td>
                    <td><?= e($b['address']) ?></td>
                    <td><?= e(format_date($b['registered_on'])) ?></td>
                    <td><?= badge($b['status']) ?></td>
                    <?php if (can('businesses.manage')): ?>
                        <td class="cell-actions"><a href="<?= e(url('registry/business_form.php', ['id' => $b['id']])) ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' ? "No businesses match \"$q\"." : 'No businesses are registered yet.') ?>
<?php endif; ?>
<?php page_end();
