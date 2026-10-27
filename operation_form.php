<?php
require __DIR__ . '/app/bootstrap.php';

$id = input_int('id');
$operation = $id ? (db_one('SELECT * FROM operations WHERE id = ?', [$id]) ?? abort(404)) : null;
$agency = $operation['agency'] ?? (input('agency') === 'police' ? 'police' : 'government');
$prefix = $agency === 'police' ? 'police_ops' : 'gov_ops';
require_can("$prefix.manage");

$errors = [];
$values = $operation ?? ['start_date' => date('Y-m-d'), 'status' => 'planned'];

if (is_post() && input('_action') === 'delete' && $operation) {
    db_delete('operations', $id);
    audit('delete', 'operation', $id, "Deleted operation {$operation['name']}");
    flash('success', "{$operation['name']} was deleted.");
    redirect('operations.php', ['agency' => $agency]);
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'name'           => 'required|max:160',
        'operation_type' => 'required|max:80',
        'status'         => 'required|in:planned,active,completed,cancelled',
        'start_date'     => 'required|date',
        'end_date'       => 'date|after:start_date',
        'description'    => 'max:5000',
    ]);
    $values = $data;
    if (!$errors) {
        if ($operation) {
            db_update('operations', $id, $data);
            audit('update', 'operation', $id, "Updated operation {$data['name']}");
            flash('success', 'Operation saved.');
        } else {
            $id = db_insert('operations', $data + ['agency' => $agency]);
            audit('create', 'operation', $id, "Created operation {$data['name']}");
            flash('success', "{$data['name']} was created.");
        }
        redirect('operations.php', ['agency' => $agency]);
    }
}

$listTitle = $agency === 'police' ? 'Police operations' : 'Public works';
$title = $operation ? "Edit {$operation['name']}" : 'New operation';
form_context($values, $errors);
page_start($title, $prefix);
?>
<p class="crumbs"><a href="<?= e(url('operations.php', ['agency' => $agency])) ?>"><?= icon('arrow-left') ?><?= e($listTitle) ?></a></p>
<?php page_header($title); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="agency" value="<?= e($agency) ?>">
        <?= field('name', 'Name', ['required' => true, 'wide' => true]) ?>
        <?= field('operation_type', 'Type', ['required' => true, 'placeholder' => $agency === 'police' ? 'Patrol, Investigation' : 'Infrastructure, Public health']) ?>
        <?= field('status', 'Status', ['type' => 'select', 'required' => true, 'options' => ['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled']]) ?>
        <?= field('start_date', 'Start date', ['type' => 'date', 'required' => true]) ?>
        <?= field('end_date', 'End date', ['type' => 'date', 'hint' => 'Leave empty while it is ongoing.']) ?>
        <?= field('description', 'Description', ['type' => 'textarea', 'wide' => true]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $operation ? 'Save changes' : 'Create operation' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('operations.php', ['agency' => $agency])) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php if ($operation): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete operation</h2>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete operation', 'danger', "Delete {$operation['name']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
