<?php
// Officers type a number plate and see everything that matters at a roadside stop.
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('plate.check');

$plate = normalize_plate(input('plate'));
$vehicle = null;
$alerts = [];

if ($plate !== '') {
    // Match with or without the dash, so "AB1234" finds "AB-1234".
    $vehicle = db_one(
        "SELECT v.*, c.first_name, c.last_name, c.person_code, c.dob, c.address
         FROM vehicles v JOIN civilians c ON c.id = v.owner_id
         WHERE REPLACE(v.plate, '-', '') = ?",
        [str_replace('-', '', $plate)]
    );
    audit('lookup', 'vehicle', $vehicle ? (int) $vehicle['id'] : null, "Plate check: $plate" . ($vehicle ? '' : ' (no match)'));
}

if ($vehicle) {
    $ownerId = (int) $vehicle['owner_id'];
    $licenses = db_all('SELECT * FROM licenses WHERE civilian_id = ? ORDER BY expires_on DESC', [$ownerId]);
    $warrants = db_all("SELECT * FROM warrants WHERE civilian_id = ? AND status = 'issued'", [$ownerId]);
    $unpaid = db_one("SELECT COUNT(*) AS n, COALESCE(SUM(amount_cents), 0) AS total, SUM(due_on < CURDATE()) AS overdue
                      FROM fines WHERE civilian_id = ? AND status = 'unpaid'", [$ownerId]);
    $records = (int) db_value('SELECT COUNT(*) FROM criminal_records WHERE civilian_id = ?', [$ownerId]);
    $validLicense = (bool) array_filter($licenses, fn($l) => license_status($l) === 'active');

    if ($vehicle['status'] === 'stolen') $alerts[] = ['bad', 'Reported stolen'];
    if ($vehicle['status'] === 'deregistered') $alerts[] = ['bad', 'Deregistered, not allowed on the road'];
    if ($warrants) $alerts[] = ['bad', count($warrants) === 1 ? 'Owner has an active warrant' : 'Owner has ' . count($warrants) . ' active warrants'];
    if (!$validLicense) $alerts[] = ['warn', 'Owner has no valid driving license'];
    if ($vehicle['inspection_until'] && $vehicle['inspection_until'] < date('Y-m-d')) $alerts[] = ['warn', 'Technical inspection expired ' . format_date($vehicle['inspection_until'])];
    if ((int) $unpaid['overdue']) $alerts[] = ['warn', 'Owner has overdue fines'];
}

page_start($plate !== '' ? "Plate $plate" : 'Plate check', 'plate');
?>
<section class="lookup">
    <h1>Plate check</h1>
    <form class="lookup-form" method="get" role="search">
        <label class="sr-only" for="plate">Number plate</label>
        <?= icon('scan') ?>
        <input id="plate" name="plate" value="<?= e($plate) ?>" placeholder="Number plate, for example JD-1980" autocomplete="off" autocapitalize="characters" autofocus>
        <button class="btn btn-primary" type="submit">Check</button>
    </form>
</section>

<?php if ($plate !== '' && !$vehicle): ?>
    <?= empty_state("No vehicle is registered with plate $plate. Check the plate, or treat the vehicle as unregistered.") ?>
<?php elseif ($vehicle): ?>
    <article class="plate-result">
        <header class="plate-result-head">
            <p class="plate-badge plate-badge-lg"><?= e($vehicle['plate']) ?></p>
            <div>
                <h2><?= e($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']) ?></h2>
                <p class="muted"><?= e($vehicle['color'] ?: 'Colour not recorded') ?><?= $vehicle['vin'] ? ', VIN ' . e($vehicle['vin']) : '' ?></p>
            </div>
        </header>

        <?php if ($alerts): ?>
            <ul class="alerts-list">
                <?php foreach ($alerts as [$tone, $text]): ?>
                    <li class="alert-item alert-item-<?= $tone ?>"><?= e($text) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="alert-item alert-item-ok">No problems found.</p>
        <?php endif; ?>

        <dl class="facts">
            <div><dt>Owner</dt><dd><?= person_link($vehicle + ['civilian_id' => $vehicle['owner_id']]) ?></dd></div>
            <div><dt>Person code</dt><dd class="code"><?= e($vehicle['person_code']) ?></dd></div>
            <div><dt>Born</dt><dd><?= e(format_date($vehicle['dob'])) ?> (<?= age($vehicle['dob']) ?>)</dd></div>
            <div><dt>Driving licenses</dt><dd>
                <?php if ($licenses): foreach ($licenses as $l): ?>
                    <span class="nowrap"><?= e($l['license_number']) ?> <?= badge(license_status($l)) ?></span><br>
                <?php endforeach; else: ?>None<?php endif; ?>
            </dd></div>
            <div><dt>Unpaid fines</dt><dd><?= (int) $unpaid['n'] ? (int) $unpaid['n'] . ', ' . e(format_money((int) $unpaid['total'])) : 'None' ?></dd></div>
            <div><dt>Criminal record</dt><dd><?= $records ? ($records === 1 ? '1 entry' : "$records entries") : 'Clean' ?></dd></div>
        </dl>

        <div class="page-actions plate-actions">
            <?php if (can('fines.issue')): ?>
                <?= button_link('Issue fine', url('police/fine_form.php', ['civilian_id' => $vehicle['owner_id'], 'vehicle_id' => $vehicle['id']]), 'primary', 'receipt') ?>
            <?php endif; ?>
            <?= button_link('Vehicle details', url('registry/vehicle_form.php', ['id' => $vehicle['id']]), 'secondary') ?>
            <?php if ($warrants): ?>
                <?= button_link('See warrants', url('police/warrants.php', ['q' => full_name($vehicle), 'status' => 'issued']), 'secondary', 'stamp') ?>
            <?php endif; ?>
        </div>
    </article>
<?php endif; ?>
<?php page_end();
