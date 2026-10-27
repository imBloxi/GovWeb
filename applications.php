<?php
// Civilians: send applications to the registry and follow their progress.
require __DIR__ . '/app/bootstrap.php';

$user = require_can('self.view');
$civilianId = $user['civilian_id'] ? (int) $user['civilian_id'] : null;
$new = input('new');
$errors = [];
$values = [];

if ($civilianId && is_post() && input('_action') === 'withdraw') {
    $app = db_one("SELECT * FROM applications WHERE id = ? AND civilian_id = ? AND status = 'submitted'", [input_int('id') ?? 0, $civilianId]) ?? abort(404);
    db_update('applications', (int) $app['id'], ['status' => 'withdrawn']);
    audit('update', 'application', (int) $app['id'], "Withdrew application {$app['code']}");
    flash('success', "Application {$app['code']} was withdrawn.");
    redirect('applications.php');
}

if ($civilianId && is_post() && isset(APPLICATION_TYPES[$new])) {
    $rules = match ($new) {
        'license'  => ['license_type' => 'required|in:' . implode(',', array_keys(LICENSE_TYPES)), 'note' => 'max:500'],
        'business' => ['name' => 'required|max:120', 'business_type' => 'required|max:80', 'address' => 'required|max:255'],
        'vehicle'  => ['make' => 'required|max:40', 'model' => 'required|max:60', 'year' => 'required|int', 'color' => 'max:30', 'vin' => 'required|min:11|max:17'],
    };
    [$data, $errors] = validate($rules);
    if ($new === 'vehicle' && !isset($errors['year']) && ($data['year'] < 1950 || $data['year'] > (int) date('Y') + 1)) {
        $errors['year'] = 'Enter the year the vehicle was made.';
    }
    if ($new === 'license' && !$errors && db_value(
        "SELECT 1 FROM licenses WHERE civilian_id = ? AND license_type = ? AND status = 'active' AND expires_on >= CURDATE()",
        [$civilianId, $data['license_type']]
    )) {
        $errors['license_type'] = 'You already hold a valid license of this type.';
    }
    if (!$errors && db_value(
        "SELECT 1 FROM applications WHERE civilian_id = ? AND type = ? AND status = 'submitted' AND details = ?",
        [$civilianId, $new, json_encode($data, JSON_UNESCAPED_UNICODE)]
    )) {
        $errors['_form'] = 'You already sent this exact application. It is waiting for a registrar.';
    }
    $values = $data;

    if (!$errors) {
        $code = unique_value('applications', 'code', fn() => random_code('A'));
        $id = db_insert('applications', [
            'code' => $code,
            'civilian_id' => $civilianId,
            'type' => $new,
            'details' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
        audit('create', 'application', $id, "Sent application $code: " . APPLICATION_TYPES[$new]);
        notify_role('registrar', 'New application: ' . APPLICATION_TYPES[$new], "$code from {$user['full_name']}", 'registry/applications.php');
        flash('success', "Application $code was sent. You'll get a notification when it's decided.");
        redirect('applications.php');
    }
}

$applications = $civilianId
    ? db_all('SELECT a.*, u.full_name AS decider FROM applications a LEFT JOIN users u ON u.id = a.decided_by WHERE a.civilian_id = ? ORDER BY a.id DESC', [$civilianId])
    : [];

form_context($values, $errors);

if ($civilianId && isset(APPLICATION_TYPES[$new])) {
    $title = 'Apply: ' . mb_strtolower(APPLICATION_TYPES[$new]);
    page_start($title, 'my_applications');
    ?>
    <p class="crumbs"><a href="<?= e(url('applications.php')) ?>"><?= icon('arrow-left') ?>My applications</a></p>
    <?php page_header(APPLICATION_TYPES[$new], match ($new) {
        'license' => 'Apply after you pass the driving exam. The registry checks your exam result before issuing.',
        'business' => 'Register a company or sole trader in your name.',
        'vehicle' => 'Register a vehicle you own. The registry assigns the number plate.',
    }); ?>
    <section class="panel form-panel">
        <?php if (isset($errors['_form'])): ?><div class="alert alert-error" role="alert"><?= e($errors['_form']) ?></div><?php else: ?><?= form_errors_summary($errors) ?><?php endif; ?>
        <form method="post" class="form-grid" novalidate>
            <?= csrf_field() ?>
            <?php if ($new === 'license'): ?>
                <?= field('license_type', 'License', ['type' => 'select', 'required' => true, 'blank' => 'Choose', 'options' => LICENSE_TYPES]) ?>
                <?= field('note', 'Anything the registry should know', ['type' => 'textarea', 'wide' => true, 'rows' => 3, 'placeholder' => 'For example: when and where you passed the exam']) ?>
            <?php elseif ($new === 'business'): ?>
                <?= field('name', 'Business name', ['required' => true, 'wide' => true]) ?>
                <?= field('business_type', 'Type of business', ['required' => true, 'placeholder' => 'For example: Bakery']) ?>
                <?= field('address', 'Business address', ['required' => true, 'wide' => true]) ?>
            <?php else: ?>
                <?= field('make', 'Make', ['required' => true, 'placeholder' => 'For example: Toyota']) ?>
                <?= field('model', 'Model', ['required' => true]) ?>
                <?= field('year', 'Year made', ['type' => 'number', 'required' => true, 'attrs' => 'min="1950" max="' . (date('Y') + 1) . '"']) ?>
                <?= field('color', 'Colour') ?>
                <?= field('vin', 'Vehicle identification number (VIN)', ['required' => true, 'wide' => true, 'hint' => 'The 17-character code on the registration papers or the windscreen.']) ?>
            <?php endif; ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Send application</button>
                <a class="btn btn-ghost" href="<?= e(url('applications.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
    <?php
    page_end();
    exit;
}

page_start('My applications', 'my_applications');
page_header('My applications', 'Apply online instead of visiting the registry office.');

if (!$civilianId) {
    echo empty_state('Your account is not linked to a civil register entry yet. Ask a registrar to link it.');
    page_end();
    exit;
}
?>
<div class="choice-grid">
    <?php foreach (APPLICATION_TYPES as $type => $label): ?>
        <a class="choice" href="<?= e(url('applications.php', ['new' => $type])) ?>">
            <?= icon(['license' => 'card', 'business' => 'building', 'vehicle' => 'car'][$type]) ?>
            <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<section class="record-section" data-dept="registry">
    <h2 class="panel-title">Sent applications</h2>
    <?php if ($applications): ?>
        <ul class="application-list">
            <?php foreach ($applications as $a): ?>
                <li>
                    <div>
                        <p class="warrant-meta"><span class="code"><?= e($a['code']) ?></span> <?= badge($a['status'], $a['status'] === 'submitted' ? 'Waiting for a registrar' : null) ?></p>
                        <h3><?= e(APPLICATION_TYPES[$a['type']]) ?></h3>
                        <p class="muted"><?= e(implode(', ', application_summary($a))) ?></p>
                        <?php if ($a['decision_note']): ?><p class="decision-note"><?= e($a['decision_note']) ?></p><?php endif; ?>
                        <p class="muted small">Sent <?= e(format_date($a['created_at'])) ?><?= $a['decided_at'] ? ', decided ' . e(format_date($a['decided_at'])) : '' ?></p>
                    </div>
                    <?php if ($a['status'] === 'submitted'): ?>
                        <?= action_form('', ['_action' => 'withdraw', 'id' => $a['id']], 'Withdraw', 'ghost', "Withdraw application {$a['code']}?") ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted">You haven't sent any applications.</p>
    <?php endif; ?>
</section>
<?php page_end();
