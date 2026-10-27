<?php
// Management overview: how the registers are growing and what's outstanding.
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_ROOT . '/app/charts.php';

require_can('reports.view');

/** Count rows per month for the last 12 months, including empty months. */
function monthly(string $sql): array
{
    $counts = [];
    foreach (db_all($sql) as $r) {
        $counts[$r['m']] = (float) $r['n'];
    }
    $points = [];
    for ($i = 11; $i >= 0; $i--) {
        $t = strtotime("first day of -$i month");
        $key = date('Y-m', $t);
        $points[] = [date('M', $t), $counts[$key] ?? 0, date('F Y', $t)];
    }
    return $points;
}

$since = "DATE_FORMAT(CURDATE() - INTERVAL 11 MONTH, '%Y-%m-01')";

$tiles = [
    ['People in the register', number_format((int) db_value('SELECT COUNT(*) FROM civilians')), 'registry'],
    ['Valid driving licenses', number_format((int) db_value("SELECT COUNT(*) FROM licenses WHERE status = 'active' AND expires_on >= CURDATE()")), 'registry'],
    ['Registered vehicles', number_format((int) db_value("SELECT COUNT(*) FROM vehicles WHERE status <> 'deregistered'")), 'registry'],
    ['Unpaid fines', format_money((int) db_value("SELECT COALESCE(SUM(amount_cents), 0) FROM fines WHERE status = 'unpaid'")), 'police'],
    ['Active warrants', number_format((int) db_value("SELECT COUNT(*) FROM warrants WHERE status = 'issued'")), 'police'],
    ['Open case files', number_format((int) db_value("SELECT COUNT(*) FROM court_files WHERE status = 'open'")), 'court'],
];

$fineTotals = db_one("SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_cents END), 0) AS paid,
                             COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN amount_cents END), 0) AS issued FROM fines");
$collectionRate = $fineTotals['issued'] ? round(100 * $fineTotals['paid'] / $fineTotals['issued']) : null;

$applicationDays = db_value("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, decided_at)) / 24 FROM applications
                             WHERE decided_at IS NOT NULL AND created_at >= CURDATE() - INTERVAL 90 DAY");

$charts = [
    ['New people in the register', 'registry', monthly("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS n FROM civilians WHERE created_at >= $since GROUP BY m"), null],
    ['Licenses issued', 'registry', monthly("SELECT DATE_FORMAT(issued_on, '%Y-%m') AS m, COUNT(*) AS n FROM licenses WHERE issued_on >= $since GROUP BY m"), null],
    ['Offenses recorded', 'police', monthly("SELECT DATE_FORMAT(offense_date, '%Y-%m') AS m, COUNT(*) AS n FROM criminal_records WHERE offense_date >= $since GROUP BY m"), null],
    ['Fines issued, in euros', 'police', monthly("SELECT DATE_FORMAT(issued_on, '%Y-%m') AS m, SUM(amount_cents) / 100 AS n FROM fines WHERE issued_on >= $since AND status <> 'cancelled' GROUP BY m"),
        fn($v) => '€' . number_format($v, 0, '.', ' ')],
];

$licenseTypes = array_map(fn($r) => [LICENSE_TYPES[$r['license_type']] ?? $r['license_type'], (int) $r['n']], db_all(
    "SELECT license_type, COUNT(*) AS n FROM licenses WHERE status = 'active' AND expires_on >= CURDATE() GROUP BY license_type ORDER BY n DESC"));
$offenses = array_map(fn($r) => [$r['offense'], (int) $r['n']], db_all(
    'SELECT offense, COUNT(*) AS n FROM criminal_records GROUP BY offense ORDER BY n DESC, offense LIMIT 8'));

page_start('Statistics', 'reports');
page_header('Statistics', 'The registers at a glance. Monthly charts cover the last 12 months.');
?>
<dl class="tiles">
    <?php foreach ($tiles as [$label, $value, $dept]): ?>
        <div class="tile" data-dept="<?= e($dept) ?>"><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
    <?php endforeach; ?>
    <div class="tile" data-dept="police"><dt>Fines collected</dt><dd><?= $collectionRate === null ? 'None issued' : $collectionRate . '%' ?></dd></div>
    <div class="tile" data-dept="registry"><dt>Average time to decide an application</dt><dd><?= $applicationDays === null ? 'No decisions yet' : (round((float) $applicationDays, 1) . ' days') ?></dd></div>
</dl>

<div class="chart-grid">
    <?php foreach ($charts as [$title, $dept, $points, $format]): ?>
        <figure class="panel chart-panel" data-dept="<?= e($dept) ?>">
            <figcaption class="panel-title"><?= e($title) ?></figcaption>
            <?= column_chart($points, $title, $format) ?>
        </figure>
    <?php endforeach; ?>
    <figure class="panel chart-panel" data-dept="registry">
        <figcaption class="panel-title">Valid licenses by type</figcaption>
        <?= bar_chart($licenseTypes, 'Valid licenses by type') ?>
    </figure>
    <figure class="panel chart-panel" data-dept="police">
        <figcaption class="panel-title">Most recorded offenses</figcaption>
        <?= bar_chart($offenses, 'Most recorded offenses') ?>
    </figure>
</div>
<?php page_end();
