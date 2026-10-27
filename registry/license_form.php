<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('licenses.manage');

const LICENSE_PREFIX = ['car' => 'CAR', 'motorcycle' => 'MOT', 'scooter' => 'SCO', 'truck' => 'TRK'];

$id = input_int('id');
$license = $id ? (db_one('SELECT * FROM licenses WHERE id = ?', [$id]) ?? abort(404)) : null;
$errors = [];
$values = $license ?? [
    'civilian_id' => input_int('civilian_id'),
    'issued_on' => date('Y-m-d'),
    'expires_on' => date('Y-m-d', strtotime('+10 years')),
    'status' => 'active',
];

if (is_post() && input('_action') === 'delete' && $license) {
    db_delete('licenses', $id);
    audit('delete', 'license', $id, "Deleted license {$license['license_number']}");
    flash('success', "License {$license['license_number']} was deleted.");
    redirect('registry/civilian.php', ['id' => $license['civilian_id']]);
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'civilian_id'  => 'required|int',
        'license_type' => 'required|in:' . implode(',', array_keys(LICENSE_PREFIX)),
        'issued_on'    => 'required|date',
        'expires_on'   => 'required|date|after:issued_on',
        'status'       => 'required|in:active,suspended,revoked',
    ]);
    if (!isset($errors['civilian_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['civilian_id']])) {
        $errors['civilian_id'] = 'Choose a person from the list.';
    }
    $values = $data;

    if (!$errors) {
        if ($license) {
            db_update('licenses', $id, $data);
            audit('update', 'license', $id, "Updated license {$license['license_number']}");
            flash('success', 'License saved.');
        } else {
            do {
                $data['license_number'] = LICENSE_PREFIX[$data['license_type']] . '-' . random_int(100000, 999999);
            } while (db_value('SELECT 1 FROM licenses WHERE license_number = ?', [$data['license_number']]));
            $id = db_insert('licenses', $data);
            audit('create', 'license', $id, "Issued license {$data['license_number']}");
            flash('success', "License {$data['license_number']} issued.");
        }
        redirect('registry/civilian.php', ['id' => $data['civilian_id']]);
    }
}

$title = $license ? "Edit license {$license['license_number']}" : 'Issue license';
form_context($values, $errors);
page_start($title, 'licenses');
?>
<p class="crumbs"><a href="<?= e(url('registry/licenses.php')) ?>"><?= icon('arrow-left') ?>Licenses</a></p>
<?php page_header($title, $license ? '' : 'The license number is created when you save.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= person_field('civilian_id', 'Holder', ['required' => true, 'wide' => true]) ?>
        <?= field('license_type', 'Type', ['type' => 'select', 'required' => true, 'blank' => 'Choose', 'options' => ['car' => 'Car', 'motorcycle' => 'Motorcycle', 'scooter' => 'Scooter', 'truck' => 'Truck']]) ?>
        <?= field('status', 'Status', ['type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'suspended' => 'Suspended', 'revoked' => 'Revoked']]) ?>
        <?= field('issued_on', 'Issued on', ['type' => 'date', 'required' => true]) ?>
        <?= field('expires_on', 'Expires on', ['type' => 'date', 'required' => true]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $license ? 'Save changes' : 'Issue license' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('registry/licenses.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php if ($license): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete license</h2>
        <p class="muted">To stop someone driving, set the status to suspended or revoked instead. Deleting removes the history.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete license', 'danger', "Delete license {$license['license_number']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
