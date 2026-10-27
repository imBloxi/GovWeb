<?php
// Civilians keep their own contact details up to date.
// Name, birth date and person code can only be changed by a registrar.
require __DIR__ . '/app/bootstrap.php';

$user = require_can('self.view');
$person = $user['civilian_id'] ? db_one('SELECT * FROM civilians WHERE id = ?', [$user['civilian_id']]) : null;
if (!$person) {
    redirect('me.php');
}

$errors = [];
$values = $person;

if (is_post()) {
    [$data, $errors] = validate([
        'email'      => 'email|max:120',
        'phone'      => 'phone|max:30',
        'address'    => 'max:255',
        'occupation' => 'max:80',
    ]);
    $values = $data + $person;
    if (!$errors) {
        try {
            db_update('civilians', (int) $person['id'], $data);
            $changed = array_keys(array_diff_assoc(array_map('strval', $data), array_map('strval', array_intersect_key($person, $data))));
            audit('update', 'civilian', (int) $person['id'], full_name($person) . ' updated their own '
                . ($changed ? implode(', ', $changed) : 'details (no changes)'));
            flash('success', 'Your details are saved.');
            redirect('me.php');
        } catch (PDOException $e) {
            if (!is_duplicate($e)) throw $e;
            $errors['email'] = 'Another person in the register uses this email address.';
        }
    }
}

form_context($values, $errors);
page_start('Update my details', 'me');
?>
<p class="crumbs"><a href="<?= e(url('me.php')) ?>"><?= icon('arrow-left') ?>My record</a></p>
<?php page_header('Update my details', 'To change your name or date of birth, visit the registry office with an identity document.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= field('email', 'Email', ['type' => 'email', 'autocomplete' => 'email']) ?>
        <?= field('phone', 'Phone', ['type' => 'tel', 'autocomplete' => 'tel']) ?>
        <?= field('address', 'Address', ['wide' => true, 'autocomplete' => 'street-address']) ?>
        <?= field('occupation', 'Occupation') ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Save my details</button>
            <a class="btn btn-ghost" href="<?= e(url('me.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php page_end();
