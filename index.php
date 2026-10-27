<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_login();
if ($user['role'] === 'civilian') {
    redirect('me.php');
}

// "Needs attention" items: only what this role can act on.
$attention = [];
if (can('warrants.decide')) {
    $n = (int) db_value("SELECT COUNT(*) FROM warrants WHERE status = 'pending'");
    if ($n) $attention[] = [$n, $n === 1 ? 'warrant request waiting for your decision' : 'warrant requests waiting for your decision', url('police/warrants.php', ['status' => 'pending']), 'court'];
}
if (can('dispatch.use')) {
    $n = (int) db_value("SELECT COUNT(*) FROM dispatches WHERE status = 'open'");
    if ($n) $attention[] = [$n, $n === 1 ? 'open dispatch call' : 'open dispatch calls', url('police/dispatch.php'), 'police'];
}
if (can('warrants.serve')) {
    $n = (int) db_value("SELECT COUNT(*) FROM warrants WHERE status = 'issued'");
    if ($n) $attention[] = [$n, $n === 1 ? 'issued warrant to serve' : 'issued warrants to serve', url('police/warrants.php', ['status' => 'issued']), 'police'];
}
if (can('licenses.manage')) {
    $n = (int) db_value("SELECT COUNT(*) FROM licenses WHERE status = 'active' AND expires_on BETWEEN CURDATE() AND CURDATE() + INTERVAL 60 DAY");
    if ($n) $attention[] = [$n, $n === 1 ? 'license expires in the next 60 days' : 'licenses expire in the next 60 days', url('registry/licenses.php', ['status' => 'expiring']), 'registry'];
}
if (can('applications.review')) {
    $n = (int) db_value("SELECT COUNT(*) FROM applications WHERE status = 'submitted'");
    if ($n) $attention[] = [$n, $n === 1 ? 'application waiting for a decision' : 'applications waiting for a decision', url('registry/applications.php'), 'registry'];
}
if (can('fines.record_payment')) {
    $n = (int) db_value("SELECT COUNT(*) FROM fines WHERE status = 'unpaid' AND due_on < CURDATE()");
    if ($n) $attention[] = [$n, $n === 1 ? 'fine is overdue' : 'fines are overdue', url('police/fines.php', ['status' => 'overdue']), 'police'];
}
if (can('plate.check')) {
    $n = (int) db_value("SELECT COUNT(*) FROM vehicles WHERE status = 'stolen'");
    if ($n) $attention[] = [$n, $n === 1 ? 'vehicle reported stolen' : 'vehicles reported stolen', url('registry/vehicles.php', ['status' => 'stolen']), 'police'];
}
if (can('court.view')) {
    $n = (int) db_value("SELECT COUNT(*) FROM court_files WHERE status = 'open'");
    if ($n) $attention[] = [$n, $n === 1 ? 'open case file' : 'open case files', url('court/files.php', ['status' => 'open']), 'court'];
}

$dispatches = can('dispatch.use')
    ? db_all("SELECT * FROM dispatches WHERE status = 'open' ORDER BY FIELD(priority, 'urgent', 'normal', 'low'), created_at DESC LIMIT 5")
    : [];

$recentPeople = can('civilians.view')
    ? db_all('SELECT id, first_name, last_name, person_code, updated_at FROM civilians ORDER BY updated_at DESC LIMIT 5')
    : [];

$activity = can('audit.view')
    ? db_all("SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
              WHERE a.action IN ('create', 'update', 'delete') ORDER BY a.id DESC LIMIT 6")
    : [];

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', preg_replace('/^(Officer|Judge)\s+/', '', $user['full_name']))[0];

page_start('Overview', 'dashboard');
?>
<section class="lookup">
    <h1><?= e($greeting) ?>, <?= e($firstName) ?>.</h1>
    <form class="lookup-form" action="<?= e(url('search.php')) ?>" method="get" role="search">
        <label class="sr-only" for="lookup-q">Search records</label>
        <?= icon('search') ?>
        <input id="lookup-q" type="search" name="q" placeholder="Search by name, person code, plate, license, warrant or case number" autocomplete="off" autofocus>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</section>

<div class="dashboard-grid">
    <section class="panel">
        <h2 class="panel-title">Needs attention</h2>
        <?php if ($attention): ?>
            <ul class="worklist">
                <?php foreach ($attention as [$count, $label, $href, $dept]): ?>
                    <li data-dept="<?= e($dept) ?>">
                        <a href="<?= e($href) ?>"><span class="worklist-count"><?= $count ?></span><span><?= e($label) ?></span></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Nothing is waiting on you.</p>
        <?php endif; ?>
    </section>

    <?php if (can('dispatch.use')): ?>
    <section class="panel" data-dept="police">
        <div class="panel-head">
            <h2 class="panel-title">Open dispatch calls</h2>
            <a href="<?= e(url('police/dispatch.php')) ?>">Open dispatch</a>
        </div>
        <?php if ($dispatches): ?>
            <ul class="feed">
                <?php foreach ($dispatches as $d): ?>
                    <li>
                        <?= badge($d['priority']) ?>
                        <div>
                            <p><?= e($d['message']) ?></p>
                            <p class="muted small"><?= e($d['location'] ?: 'No location') ?>, <?= e(time_ago($d['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No open calls.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($recentPeople): ?>
    <section class="panel" data-dept="registry">
        <div class="panel-head">
            <h2 class="panel-title">Recently updated people</h2>
            <a href="<?= e(url('registry/civilians.php')) ?>">All civilians</a>
        </div>
        <ul class="feed">
            <?php foreach ($recentPeople as $p): ?>
                <li>
                    <div>
                        <p><a href="<?= e(url('registry/civilian.php', ['id' => $p['id']])) ?>"><?= e(full_name($p)) ?></a></p>
                        <p class="muted small code"><?= e($p['person_code']) ?></p>
                    </div>
                    <span class="muted small"><?= e(time_ago($p['updated_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if (can('audit.view')): ?>
    <section class="panel" data-dept="admin">
        <div class="panel-head">
            <h2 class="panel-title">Latest activity</h2>
            <a href="<?= e(url('admin/activity.php')) ?>">Activity log</a>
        </div>
        <?php if ($activity): ?>
            <ul class="feed">
                <?php foreach ($activity as $a): ?>
                    <li>
                        <div>
                            <p><?= e($a['summary']) ?></p>
                            <p class="muted small"><?= e($a['full_name'] ?? 'System') ?>, <?= e(time_ago($a['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Changes people make will show up here.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
<?php page_end();
