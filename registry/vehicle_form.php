<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('vehicles.view');

$id = input_int('id');
$vehicle = $id ? (db_one('SELECT * FROM vehicles WHERE id = ?', [$id]) ?? abort(404)) : null;
$readOnly = !can('vehicles.manage');
if ($readOnly && !$vehicle) {
    abort(403);
}
$errors = [];
$values = $vehicle ?? ['owner_id' => input_int('owner_id'), 'registered_on' => date('Y-m-d'), 'status' => 'registered'];

// Officers can report a vehicle stolen or recovered without full edit rights.
if (is_post() && in_array(input('_action'), ['stolen', 'recovered'], true) && $vehicle) {
    if (!can('vehicles.flag') && !can('vehicles.manage')) abort(403);
    $to = input('_action') === 'stolen' ? 'stolen' : 'registered';
    db_update('vehicles', $id, ['status' => $to]);
    audit('update', 'vehicle', $id, "Vehicle {$vehicle['plate']} " . ($to === 'stolen' ? 'reported stolen' : 'marked recovered'));
    if ($to === 'stolen') {
        notify_role('officer', "Stolen vehicle: {$vehicle['plate']}", "{$vehicle['year']} {$vehicle['make']} {$vehicle['model']}" . ($vehicle['color'] ? ", {$vehicle['color']}" : ''), "police/plate.php?plate={$vehicle['plate']}");
    }
    notify_civilian((int) $vehicle['owner_id'], "Your vehicle {$vehicle['plate']} was " . ($to === 'stolen' ? 'reported stolen' : 'marked recovered'), '', 'me.php');
    flash('success', "{$vehicle['plate']} is now " . ($to === 'stolen' ? 'reported stolen.' : 'marked recovered.'));
    redirect('registry/vehicle_form.php', ['id' => $id]);
}

if (is_post() && $readOnly) {
    abort(403);
}

if (is_post() && input('_action') === 'delete' && $vehicle) {
    db_delete('vehicles', $id);
    audit('delete', 'vehicle', $id, "Deleted vehicle {$vehicle['plate']}");
    flash('success', "Vehicle {$vehicle['plate']} was deleted.");
    redirect('registry/vehicles.php');
}

if (is_post() && input('_action') === 'save') {
    [$data, $errors] = validate([
        'plate'            => 'max:12',
        'vin'              => 'min:11|max:17',
        'owner_id'         => 'required|int',
        'make'             => 'required|max:40',
        'model'            => 'required|max:60',
        'year'             => 'required|int',
        'color'            => 'max:30',
        'status'           => 'required|in:registered,stolen,deregistered',
        'registered_on'    => 'required|date',
        'inspection_until' => 'date',
    ]);
    if (!isset($errors['year']) && ($data['year'] < 1900 || $data['year'] > (int) date('Y') + 1)) {
        $errors['year'] = 'Enter the year the vehicle was made.';
    }
    if (!isset($errors['owner_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['owner_id']])) {
        $errors['owner_id'] = 'Choose an owner from the list.';
    }
    $data['plate'] = $data['plate'] ? normalize_plate($data['plate']) : null;
    $data['vin'] = $data['vin'] ? strtoupper($data['vin']) : null;
    $values = $data;

    if (!$errors) {
        $data['plate'] ??= new_plate();
        try {
            if ($vehicle) {
                db_update('vehicles', $id, $data);
                audit('update', 'vehicle', $id, "Updated vehicle {$data['plate']}");
                if ((int) $vehicle['owner_id'] !== (int) $data['owner_id']) {
                    audit('update', 'vehicle', $id, "Vehicle {$data['plate']} transferred to a new owner");
                    notify_civilian((int) $data['owner_id'], "Vehicle {$data['plate']} is now registered to you", '', 'me.php');
                }
                flash('success', 'Vehicle saved.');
            } else {
                $id = db_insert('vehicles', $data);
                audit('create', 'vehicle', $id, "Registered vehicle {$data['plate']}");
                flash('success', "Vehicle registered with plate {$data['plate']}.");
            }
            redirect('registry/vehicle_form.php', ['id' => $id]);
        } catch (PDOException $e) {
            if (!is_duplicate($e)) throw $e;
            $field = str_contains($e->getMessage(), 'vin') ? 'vin' : 'plate';
            $errors[$field] = $field === 'vin' ? 'A vehicle with this VIN is already registered.' : 'This plate is already in use.';
        }
    }
}

$owner = $vehicle ? db_one('SELECT * FROM civilians WHERE id = ?', [$vehicle['owner_id']]) : null;
$title = $vehicle ? $vehicle['plate'] : 'Register vehicle';
form_context($values, $errors);
page_start($title, 'vehicles');
?>
<p class="crumbs"><a href="<?= e(url('registry/vehicles.php')) ?>"><?= icon('arrow-left') ?>Vehicles</a></p>
<?php if ($vehicle): ?>
    <header class="page-header">
        <div>
            <p class="plate-badge"><?= e($vehicle['plate']) ?></p>
            <p class="page-subtitle"><?= e($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?>, owned by <?= $owner ? person_link($owner + ['civilian_id' => $owner['id']]) : 'unknown' ?></p>
        </div>
        <div class="page-actions">
            <?= badge($vehicle['status']) ?>
            <?php if (can('vehicles.flag') || can('vehicles.manage')): ?>
                <?php if ($vehicle['status'] === 'registered'): ?>
                    <?= action_form('', ['_action' => 'stolen', 'id' => $id], 'Report stolen', 'danger', "Report {$vehicle['plate']} as stolen? All officers will be notified.") ?>
                <?php elseif ($vehicle['status'] === 'stolen'): ?>
                    <?= action_form('', ['_action' => 'recovered', 'id' => $id], 'Mark recovered', 'secondary') ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
<?php else: ?>
    <?php page_header($title, 'Leave the plate empty to assign the next free one.'); ?>
<?php endif; ?>

<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="save">
        <fieldset <?= $readOnly ? 'disabled' : '' ?> class="form-grid-fieldset">
            <?= person_field('owner_id', 'Owner', ['required' => true, 'wide' => true]) ?>
            <?= field('plate', 'Number plate', ['placeholder' => 'Assigned automatically']) ?>
            <?= field('vin', 'VIN', ['hint' => 'Vehicle identification number, 11 to 17 characters.']) ?>
            <?= field('make', 'Make', ['required' => true]) ?>
            <?= field('model', 'Model', ['required' => true]) ?>
            <?= field('year', 'Year made', ['type' => 'number', 'required' => true]) ?>
            <?= field('color', 'Colour') ?>
            <?= field('registered_on', 'Registered on', ['type' => 'date', 'required' => true]) ?>
            <?= field('inspection_until', 'Technical inspection valid until', ['type' => 'date']) ?>
            <?= field('status', 'Status', ['type' => 'select', 'required' => true, 'options' => ['registered' => 'Registered', 'stolen' => 'Reported stolen', 'deregistered' => 'Deregistered']]) ?>
        </fieldset>
        <?php if (!$readOnly): ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= $vehicle ? 'Save changes' : 'Register vehicle' ?></button>
                <a class="btn btn-ghost" href="<?= e(url('registry/vehicles.php')) ?>">Cancel</a>
            </div>
        <?php endif; ?>
    </form>
</section>

<?php if ($vehicle && !$readOnly): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete vehicle</h2>
        <p class="muted">If the vehicle is off the road, set its status to deregistered instead.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete vehicle', 'danger', "Delete vehicle {$vehicle['plate']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
