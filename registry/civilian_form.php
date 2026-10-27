<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('civilians.manage');

$id = input_int('id');
$person = $id ? (db_one('SELECT * FROM civilians WHERE id = ?', [$id]) ?? abort(404)) : null;
$errors = [];
$values = $person ?? [];

if (is_post() && input('_action') === 'delete' && $person) {
    db_delete('civilians', $id);
    audit('delete', 'civilian', $id, 'Deleted civilian ' . full_name($person) . " ({$person['person_code']})");
    flash('success', full_name($person) . ' was removed from the register.');
    redirect('registry/civilians.php');
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'first_name' => 'required|max:60',
        'last_name'  => 'required|max:60',
        'gender'     => 'required|in:male,female,other',
        'dob'        => 'required|date|past',
        'email'      => 'email|max:120',
        'phone'      => 'phone|max:30',
        'address'    => 'max:255',
        'occupation' => 'max:80',
    ]);
    $values = $data;

    if (!$errors) {
        try {
            if ($person) {
                db_update('civilians', $id, $data);
                audit('update', 'civilian', $id, 'Updated civilian ' . full_name($data));
                flash('success', 'Details saved.');
            } else {
                // Person code: birth date (YYMMDD) plus a random number, retried if taken.
                for ($try = 0; $try < 5; $try++) {
                    $data['person_code'] = date('ymd', strtotime($data['dob'])) . '-' . random_int(10000, 99999);
                    if (!db_value('SELECT 1 FROM civilians WHERE person_code = ?', [$data['person_code']])) break;
                }
                $id = db_insert('civilians', $data);
                audit('create', 'civilian', $id, 'Registered civilian ' . full_name($data) . " ({$data['person_code']})");
                flash('success', full_name($data) . " is registered with person code {$data['person_code']}.");
            }
            redirect('registry/civilian.php', ['id' => $id]);
        } catch (PDOException $e) {
            if (!is_duplicate($e)) throw $e;
            $errors['email'] = 'Someone in the register already uses this email address.';
        }
    }
}

$title = $person ? 'Edit ' . full_name($person) : 'Register person';
form_context($values, $errors);
page_start($title, 'civilians');
?>
<p class="crumbs"><a href="<?= e($person ? url('registry/civilian.php', ['id' => $id]) : url('registry/civilians.php')) ?>"><?= icon('arrow-left') ?><?= $person ? e(full_name($person)) : 'Civilians' ?></a></p>
<?php page_header($title, $person ? 'Person code ' . $person['person_code'] : 'A person code is created from the date of birth when you save.'); ?>

<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= field('first_name', 'First name', ['required' => true, 'autocomplete' => 'off']) ?>
        <?= field('last_name', 'Last name', ['required' => true, 'autocomplete' => 'off']) ?>
        <?= field('dob', 'Date of birth', ['type' => 'date', 'required' => true, 'attrs' => 'max="' . date('Y-m-d') . '"']) ?>
        <?= field('gender', 'Gender', ['type' => 'select', 'required' => true, 'blank' => 'Choose', 'options' => ['female' => 'Female', 'male' => 'Male', 'other' => 'Other']]) ?>
        <?= field('email', 'Email', ['type' => 'email']) ?>
        <?= field('phone', 'Phone', ['type' => 'tel']) ?>
        <?= field('address', 'Address', ['wide' => true]) ?>
        <?= field('occupation', 'Occupation') ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $person ? 'Save changes' : 'Register person' ?></button>
            <a class="btn btn-ghost" href="<?= e($person ? url('registry/civilian.php', ['id' => $id]) : url('registry/civilians.php')) ?>">Cancel</a>
        </div>
    </form>
</section>

<?php if ($person): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Remove from register</h2>
        <p class="muted">This also deletes their licenses, businesses, criminal record, warrants and court documents. It can't be undone.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete ' . full_name($person), 'danger', 'Delete ' . full_name($person) . ' and everything linked to them?') ?>
    </section>
<?php endif; ?>
<?php page_end();
