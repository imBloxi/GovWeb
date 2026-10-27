<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('civilians.view');

$q = input('q');
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR person_code LIKE ? OR email LIKE ?";
    $params = array_fill(0, 3, "%$q%");
}

if (input('export') === 'csv') {
    require_can('data.export');
    export_csv('civilians', ['Person code', 'First name', 'Last name', 'Gender', 'Born', 'Email', 'Phone', 'Address', 'Occupation'],
        array_map(fn($c) => [$c['person_code'], $c['first_name'], $c['last_name'], $c['gender'], $c['dob'], $c['email'], $c['phone'], $c['address'], $c['occupation']],
            db_all("SELECT * FROM civilians $where ORDER BY last_name, first_name", $params)));
}

$result = paginate(
    "SELECT id, person_code, first_name, last_name, dob, phone, address FROM civilians $where ORDER BY last_name, first_name",
    "SELECT COUNT(*) FROM civilians $where",
    $params
);

page_start('Civilians', 'civilians');
page_header(
    'Civilians',
    $result['total'] . ($result['total'] === 1 ? ' person' : ' people') . ' in the civil register',
    (can('data.export') ? button_link('Download CSV', url('registry/civilians.php', $_GET + ['export' => 'csv']), 'secondary', 'download') : '')
    . (can('civilians.manage') ? button_link('Register person', url('registry/civilian_form.php'), 'primary', 'plus') : '')
);
echo search_bar('Name, person code or email');
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Name</th><th>Person code</th><th>Born</th><th>Phone</th><th>Address</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $c): ?>
                <tr>
                    <td><a class="row-link" href="<?= e(url('registry/civilian.php', ['id' => $c['id']])) ?>"><?= e($c['last_name'] . ', ' . $c['first_name']) ?></a></td>
                    <td class="code"><?= e($c['person_code']) ?></td>
                    <td><?= e(format_date($c['dob'])) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= e($c['address']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state(
        $q !== '' ? "No one matches \"$q\"." : 'The civil register is empty.',
        can('civilians.manage') ? button_link('Register person', url('registry/civilian_form.php'), 'primary', 'plus') : ''
    ) ?>
<?php endif; ?>
<?php page_end();
