<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('warrants.request');

$errors = [];
$values = ['civilian_id' => input_int('civilian_id')];

if (is_post()) {
    [$data, $errors] = validate([
        'civilian_id' => 'required|int',
        'reason'      => 'required|max:160',
        'description' => 'max:5000',
    ]);
    if (!isset($errors['civilian_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['civilian_id']])) {
        $errors['civilian_id'] = 'Choose a person from the list.';
    }
    $values = $data;
    if (!$errors) {
        do {
            $data['code'] = random_code('W');
        } while (db_value('SELECT 1 FROM warrants WHERE code = ?', [$data['code']]));
        $id = db_insert('warrants', $data + ['status' => 'pending', 'requested_by' => $user['id']]);
        audit('create', 'warrant', $id, "Requested warrant {$data['code']}: {$data['reason']}");
        notify_role('judge', "Warrant {$data['code']} needs a decision", $data['reason'], 'police/warrants.php?status=pending');
        flash('success', "Warrant {$data['code']} was sent to a judge for a decision.");
        redirect('police/warrants.php');
    }
}

form_context($values, $errors);
page_start('Request warrant', 'warrants');
?>
<p class="crumbs"><a href="<?= e(url('police/warrants.php')) ?>"><?= icon('arrow-left') ?>Warrants</a></p>
<?php page_header('Request warrant', 'A judge reviews the request before the warrant is issued.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= person_field('civilian_id', 'Person', ['required' => true, 'wide' => true]) ?>
        <?= field('reason', 'Reason', ['required' => true, 'wide' => true, 'placeholder' => 'For example: Failure to appear in court']) ?>
        <?= field('description', 'Grounds', ['type' => 'textarea', 'wide' => true, 'rows' => 6, 'hint' => 'Evidence and circumstances the judge should know about.']) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Send to judge</button>
            <a class="btn btn-ghost" href="<?= e(url('police/warrants.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php page_end();
