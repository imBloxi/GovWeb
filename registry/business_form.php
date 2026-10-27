<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('businesses.view');

$id = input_int('id');
$business = $id ? (db_one('SELECT * FROM businesses WHERE id = ?', [$id]) ?? abort(404)) : null;

// People who can view but not manage get a read-only page.
$readOnly = !can('businesses.manage');
if ($readOnly && !$business) {
    abort(403);
}

$errors = [];
$values = $business ?? ['owner_id' => input_int('owner_id'), 'registered_on' => date('Y-m-d'), 'status' => 'active'];

if (is_post() && $readOnly) {
    abort(403);
}

if (is_post() && input('_action') === 'delete' && $business) {
    db_delete('businesses', $id);
    audit('delete', 'business', $id, "Deleted business {$business['name']}");
    flash('success', "{$business['name']} was deleted.");
    redirect('registry/businesses.php');
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'name'          => 'required|max:120',
        'business_type' => 'required|max:80',
        'owner_id'      => 'required|int',
        'address'       => 'required|max:255',
        'registered_on' => 'required|date',
        'status'        => 'required|in:active,closed',
    ]);
    if (!isset($errors['owner_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['owner_id']])) {
        $errors['owner_id'] = 'Choose an owner from the list.';
    }
    $values = $data;

    if (!$errors) {
        if ($business) {
            db_update('businesses', $id, $data);
            audit('update', 'business', $id, "Updated business {$data['name']}");
            flash('success', 'Business saved.');
        } else {
            $id = db_insert('businesses', $data);
            audit('create', 'business', $id, "Registered business {$data['name']}");
            flash('success', "{$data['name']} is registered.");
        }
        redirect('registry/businesses.php');
    }
}

$title = $business ? ($readOnly ? $business['name'] : "Edit {$business['name']}") : 'Register business';
form_context($values, $errors);
page_start($title, 'businesses');
?>
<p class="crumbs"><a href="<?= e(url('registry/businesses.php')) ?>"><?= icon('arrow-left') ?>Businesses</a></p>
<?php page_header($title); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <fieldset <?= $readOnly ? 'disabled' : '' ?> class="form-grid-fieldset">
            <?= field('name', 'Business name', ['required' => true, 'wide' => true]) ?>
            <?= field('business_type', 'Type of business', ['required' => true, 'placeholder' => 'For example: Retail, Construction']) ?>
            <?= person_field('owner_id', 'Owner', ['required' => true]) ?>
            <?= field('address', 'Address', ['required' => true, 'wide' => true]) ?>
            <?= field('registered_on', 'Registered on', ['type' => 'date', 'required' => true]) ?>
            <?= field('status', 'Status', ['type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'closed' => 'Closed']]) ?>
        </fieldset>
        <?php if (!$readOnly): ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $business ? 'Save changes' : 'Register business' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('registry/businesses.php')) ?>">Cancel</a>
        </div>
        <?php endif; ?>
    </form>
</section>
<?php if ($business && !$readOnly): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete business</h2>
        <p class="muted">If the business stopped trading, set its status to closed instead.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete business', 'danger', "Delete {$business['name']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
