<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('fines.issue');

const FINE_DUE_DAYS = 30;

$errors = [];
$values = ['civilian_id' => input_int('civilian_id'), 'vehicle_id' => input_int('vehicle_id')];

if (is_post()) {
    [$data, $errors] = validate([
        'civilian_id' => 'required|int',
        'vehicle_id'  => 'int',
        'amount'      => 'required|max:12',
        'reason'      => 'required|max:160',
    ]);
    $cents = parse_money($data['amount']);
    if (!isset($errors['amount']) && $cents === null) {
        $errors['amount'] = 'Enter an amount in euros, for example 40 or 12.50.';
    }
    if (!isset($errors['civilian_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['civilian_id']])) {
        $errors['civilian_id'] = 'Choose a person from the list.';
    }
    if ($data['vehicle_id'] && !db_value('SELECT 1 FROM vehicles WHERE id = ?', [$data['vehicle_id']])) {
        $data['vehicle_id'] = null;
    }
    $values = $data;

    if (!$errors) {
        $code = unique_value('fines', 'code', fn() => random_code('F'));
        $id = db_insert('fines', [
            'code' => $code,
            'civilian_id' => $data['civilian_id'],
            'vehicle_id' => $data['vehicle_id'],
            'amount_cents' => $cents,
            'reason' => $data['reason'],
            'issued_by' => $user['id'],
            'issued_on' => date('Y-m-d'),
            'due_on' => date('Y-m-d', strtotime('+' . FINE_DUE_DAYS . ' days')),
        ]);
        audit('create', 'fine', $id, "Issued fine $code, " . format_money($cents) . ": {$data['reason']}");
        notify_civilian((int) $data['civilian_id'], "New fine $code: " . format_money($cents), $data['reason'] . '. Due in ' . FINE_DUE_DAYS . ' days.', 'me.php');
        flash('success', "Fine $code issued. It is due in " . FINE_DUE_DAYS . ' days.');
        redirect('registry/civilian.php', ['id' => $data['civilian_id']]);
    }
}

$vehicle = $values['vehicle_id'] ? db_one('SELECT * FROM vehicles WHERE id = ?', [$values['vehicle_id']]) : null;
form_context($values, $errors);
page_start('Issue fine', 'fines');
?>
<p class="crumbs"><a href="<?= e(url('police/fines.php')) ?>"><?= icon('arrow-left') ?>Fines</a></p>
<?php page_header('Issue fine', 'The person is notified and has ' . FINE_DUE_DAYS . ' days to pay.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?php if ($vehicle): ?>
            <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
            <p class="field-wide">For vehicle <span class="plate-badge"><?= e($vehicle['plate']) ?></span></p>
        <?php endif; ?>
        <?= person_field('civilian_id', 'Person', ['required' => true, 'wide' => true]) ?>
        <?= field('amount', 'Amount in euros', ['required' => true, 'attrs' => 'inputmode="decimal"', 'placeholder' => '40.00']) ?>
        <?= field('reason', 'Reason', ['required' => true, 'wide' => true, 'placeholder' => 'For example: Speeding, 72 km/h in a 50 zone']) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Issue fine</button>
            <a class="btn btn-ghost" href="<?= e(url('police/fines.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php page_end();
