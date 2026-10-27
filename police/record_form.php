<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('records.manage');

$id = input_int('id');
$record = $id ? (db_one('SELECT * FROM criminal_records WHERE id = ?', [$id]) ?? abort(404)) : null;
$errors = [];
$values = $record ?? ['civilian_id' => input_int('civilian_id'), 'offense_date' => date('Y-m-d')];

if (is_post() && input('_action') === 'delete' && $record && can('records.delete')) {
    db_delete('criminal_records', $id);
    audit('delete', 'criminal_record', $id, "Deleted criminal record entry: {$record['offense']}");
    flash('success', 'Entry deleted.');
    redirect('police/records.php');
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'civilian_id'  => 'required|int',
        'offense'      => 'required|max:120',
        'offense_date' => 'required|date|past',
        'sentence'     => 'max:255',
        'description'  => 'max:5000',
    ]);
    if (!isset($errors['civilian_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['civilian_id']])) {
        $errors['civilian_id'] = 'Choose a person from the list.';
    }
    $values = $data;
    if (!$errors) {
        if ($record) {
            db_update('criminal_records', $id, $data);
            audit('update', 'criminal_record', $id, "Updated criminal record entry: {$data['offense']}");
            flash('success', 'Entry saved.');
        } else {
            $id = db_insert('criminal_records', $data + ['officer_id' => $user['id']]);
            audit('create', 'criminal_record', $id, "Recorded offense: {$data['offense']}");
            flash('success', 'Offense recorded.');
        }
        redirect('registry/civilian.php', ['id' => $data['civilian_id']]);
    }
}

$title = $record ? 'Edit record entry' : 'Record an offense';
form_context($values, $errors);
page_start($title, 'records');
?>
<p class="crumbs"><a href="<?= e(url('police/records.php')) ?>"><?= icon('arrow-left') ?>Criminal records</a></p>
<?php page_header($title); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= field('civilian_id', 'Person', ['type' => 'select', 'required' => true, 'blank' => 'Choose a person', 'options' => civilian_options(), 'wide' => true]) ?>
        <?= field('offense', 'Offense', ['required' => true, 'placeholder' => 'For example: Theft']) ?>
        <?= field('offense_date', 'Date of offense', ['type' => 'date', 'required' => true, 'attrs' => 'max="' . date('Y-m-d') . '"']) ?>
        <?= field('sentence', 'Sentence', ['wide' => true, 'hint' => 'Leave empty until a court decides.']) ?>
        <?= field('description', 'What happened', ['type' => 'textarea', 'wide' => true]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $record ? 'Save changes' : 'Record offense' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('police/records.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php if ($record && can('records.delete')): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete entry</h2>
        <p class="muted">Only administrators can delete record entries.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete entry', 'danger', 'Delete this record entry?') ?>
    </section>
<?php endif; ?>
<?php page_end();
