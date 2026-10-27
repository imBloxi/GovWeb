<?php
// Registrars review applications civilians sent online.
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('applications.review');

if (is_post()) {
    $app = db_one("SELECT * FROM applications WHERE id = ? AND status = 'submitted'", [input_int('id') ?? 0])
        ?? abort(404, 'This application was already decided or withdrawn.');
    $decision = input('decision');
    $note = mb_substr(input('note'), 0, 255);
    $d = application_details($app);
    $civilianId = (int) $app['civilian_id'];

    if ($decision === 'reject') {
        if ($note === '') {
            flash('error', 'Write a reason so the applicant knows what to fix.');
            back('registry/applications.php');
        }
        db_update('applications', (int) $app['id'], [
            'status' => 'rejected', 'decision_note' => $note, 'decided_by' => $user['id'], 'decided_at' => date('Y-m-d H:i:s'),
        ]);
        audit('update', 'application', (int) $app['id'], "Rejected application {$app['code']}");
        notify_civilian($civilianId, "Application {$app['code']} was rejected", $note, 'applications.php');
        flash('success', "Application {$app['code']} rejected.");
        redirect('registry/applications.php');
    }

    if ($decision !== 'approve') {
        abort(400, 'Choose approve or reject.');
    }

    if ($app['type'] === 'license' && !isset(LICENSE_TYPES[$d['license_type'] ?? ''])) {
        abort(400, 'This application has an unknown license type.');
    }
    if ($app['type'] === 'vehicle' && !empty($d['vin']) && db_value('SELECT 1 FROM vehicles WHERE vin = ?', [$d['vin']])) {
        flash('error', "A vehicle with VIN {$d['vin']} is already registered. Reject the application instead.");
        back('registry/applications.php');
    }

    // Create what was applied for, and record the result in the decision note.
    $result = db_transaction(function () use ($app, $d, $civilianId) {
        switch ($app['type']) {
            case 'license':
                $license = create_license($civilianId, $d['license_type']);
                return ["License {$license['license_number']} issued", 'registry/civilian.php?id=' . $civilianId];
            case 'business':
                $id = db_insert('businesses', [
                    'owner_id' => $civilianId, 'name' => $d['name'], 'business_type' => $d['business_type'],
                    'address' => $d['address'], 'registered_on' => date('Y-m-d'),
                ]);
                audit('create', 'business', $id, "Registered business {$d['name']}");
                return ["Business {$d['name']} registered", 'registry/business_form.php?id=' . $id];
            case 'vehicle':
                $plate = new_plate();
                $id = db_insert('vehicles', [
                    'plate' => $plate, 'vin' => $d['vin'] ?: null, 'owner_id' => $civilianId,
                    'make' => $d['make'], 'model' => $d['model'], 'year' => (int) $d['year'],
                    'color' => $d['color'] ?: null, 'registered_on' => date('Y-m-d'),
                ]);
                audit('create', 'vehicle', $id, "Registered vehicle $plate");
                return ["Vehicle registered with plate $plate", 'registry/vehicle_form.php?id=' . $id];
        }
        throw new RuntimeException('Unknown application type: ' . $app['type']);
    });
    [$outcome, $link] = $result;

    db_update('applications', (int) $app['id'], [
        'status' => 'approved',
        'decision_note' => $note !== '' ? "$outcome. $note" : "$outcome.",
        'decided_by' => $user['id'],
        'decided_at' => date('Y-m-d H:i:s'),
    ]);
    audit('update', 'application', (int) $app['id'], "Approved application {$app['code']}: $outcome");
    notify_civilian($civilianId, "Application {$app['code']} was approved", $outcome, 'me.php');
    flash('success', "Approved. $outcome.");
    redirect('registry/applications.php');
}

$status = input('status', 'submitted');
$conditions = [];
$params = [];
if (in_array($status, ['submitted', 'approved', 'rejected', 'withdrawn'], true)) {
    $conditions[] = 'a.status = ?';
    $params[] = $status;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$from = "FROM applications a JOIN civilians c ON c.id = a.civilian_id LEFT JOIN users u ON u.id = a.decided_by $where";
$result = paginate(
    "SELECT a.*, c.first_name, c.last_name, c.person_code, u.full_name AS decider $from ORDER BY a.status <> 'submitted', a.id " . ($status === 'submitted' ? 'ASC' : 'DESC'),
    "SELECT COUNT(*) $from",
    $params
);

page_start('Applications', 'applications');
page_header('Applications', $status === 'submitted'
    ? ($result['total'] === 1 ? '1 application waiting, oldest first' : "{$result['total']} applications waiting, oldest first")
    : 'Applications civilians sent online');
?>
<form class="toolbar" method="get">
    <label class="sr-only" for="filter-status">Show</label>
    <select id="filter-status" name="status" data-autosubmit>
        <?php foreach (['submitted' => 'Waiting for a decision', 'approved' => 'Approved', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn', 'all' => 'All applications'] as $v => $l): ?>
            <option value="<?= $v ?>"<?= $status === $v ? ' selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-secondary" type="submit">Show</button></noscript>
</form>

<?php if ($result['rows']): ?>
    <ul class="warrant-list">
        <?php foreach ($result['rows'] as $a): ?>
            <li class="warrant" data-status="<?= $a['status'] === 'submitted' ? 'pending' : e($a['status']) ?>">
                <div class="warrant-main">
                    <p class="warrant-meta"><span class="code"><?= e($a['code']) ?></span> <?= badge($a['status'], $a['status'] === 'submitted' ? 'Waiting' : null) ?></p>
                    <h2 class="warrant-title"><?= e(APPLICATION_TYPES[$a['type']]) ?></h2>
                    <p><?= person_link($a) ?> <span class="muted code"><?= e($a['person_code']) ?></span></p>
                    <dl class="inline-facts">
                        <?php foreach (application_summary($a) as $label => $value): ?>
                            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                    <?php if ($a['decision_note']): ?><p class="decision-note"><?= e($a['decision_note']) ?></p><?php endif; ?>
                    <p class="muted small">Sent <?= e(time_ago($a['created_at'])) ?><?= $a['decider'] ? ', decided by ' . e($a['decider']) : '' ?></p>
                </div>
                <?php if ($a['status'] === 'submitted'): ?>
                    <form method="post" class="decision-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <label class="sr-only" for="note-<?= (int) $a['id'] ?>">Note to the applicant</label>
                        <input id="note-<?= (int) $a['id'] ?>" name="note" placeholder="Note to the applicant (needed to reject)" maxlength="255">
                        <div class="decision-buttons">
                            <button class="btn btn-primary btn-sm" name="decision" value="approve">Approve</button>
                            <button class="btn btn-ghost btn-sm" name="decision" value="reject">Reject</button>
                        </div>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($status === 'submitted' ? 'No applications are waiting.' : 'No applications here.') ?>
<?php endif; ?>
<?php page_end();
