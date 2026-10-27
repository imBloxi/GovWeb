<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_login();
if ($user['role'] === 'civilian') {
    abort(403);
}

$q = input('q');
$like = '%' . $q . '%';
$groups = [];

if ($q !== '') {
    if (can('civilians.view')) {
        $rows = db_all(
            "SELECT id, first_name, last_name, person_code, dob FROM civilians
             WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR person_code LIKE ? OR email LIKE ? OR phone LIKE ?
             ORDER BY last_name, first_name LIMIT 20",
            [$like, $like, $like, $like]
        );
        $groups['People'] = ['registry', array_map(fn($r) => [
            url('registry/civilian.php', ['id' => $r['id']]), full_name($r), $r['person_code'] . ', born ' . format_date($r['dob']),
        ], $rows)];
    }
    if (can('licenses.view')) {
        $rows = db_all(
            'SELECT l.id, l.license_number, l.license_type, c.first_name, c.last_name, l.civilian_id FROM licenses l
             JOIN civilians c ON c.id = l.civilian_id WHERE l.license_number LIKE ? LIMIT 10',
            [$like]
        );
        $groups['Licenses'] = ['registry', array_map(fn($r) => [
            url('registry/civilian.php', ['id' => $r['civilian_id']]), $r['license_number'], ucfirst($r['license_type']) . ' license, ' . full_name($r),
        ], $rows)];
    }
    if (can('businesses.view')) {
        $rows = db_all('SELECT id, name, business_type FROM businesses WHERE name LIKE ? LIMIT 10', [$like]);
        $groups['Businesses'] = ['registry', array_map(fn($r) => [
            url('registry/business_form.php', ['id' => $r['id']]), $r['name'], $r['business_type'],
        ], $rows)];
    }
    if (can('warrants.view')) {
        $rows = db_all(
            'SELECT w.code, w.reason, w.status, c.first_name, c.last_name FROM warrants w
             JOIN civilians c ON c.id = w.civilian_id WHERE w.code LIKE ? LIMIT 10',
            [$like]
        );
        $groups['Warrants'] = ['police', array_map(fn($r) => [
            url('police/warrants.php', ['q' => $r['code']]), $r['code'], $r['reason'] . ', ' . full_name($r),
        ], $rows)];
    }
    if (can('court.view')) {
        $rows = db_all('SELECT id, file_number, case_title FROM court_files WHERE file_number LIKE ? OR case_title LIKE ? LIMIT 10', [$like, $like]);
        $groups['Case files'] = ['court', array_map(fn($r) => [
            url('court/file.php', ['id' => $r['id']]), $r['file_number'], $r['case_title'],
        ], $rows)];
        $rows = db_all('SELECT id, document_code, case_number, court_name FROM court_documents WHERE document_code LIKE ? OR case_number LIKE ? LIMIT 10', [$like, $like]);
        $groups['Court documents'] = ['court', array_map(fn($r) => [
            url('court/document.php', ['id' => $r['id']]), $r['document_code'], $r['court_name'] . ', case ' . $r['case_number'],
        ], $rows)];
    }
    $groups = array_filter($groups, fn($g) => $g[1]);
}

page_start($q === '' ? 'Search' : "Search: $q", 'dashboard');
?>
<form class="lookup-form" method="get" role="search">
    <label class="sr-only" for="search-q">Search records</label>
    <?= icon('search') ?>
    <input id="search-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name, person code, license, warrant or case number" autofocus>
    <button class="btn btn-primary" type="submit">Search</button>
</form>

<?php if ($q === ''): ?>
    <?= empty_state('Type a name or number to search every register you have access to.') ?>
<?php elseif (!$groups): ?>
    <?= empty_state("Nothing matches \"$q\". Check the spelling, or search by part of a name or number.") ?>
<?php else: ?>
    <div class="results">
        <?php foreach ($groups as $label => [$dept, $items]): ?>
            <section class="results-group" data-dept="<?= e($dept) ?>">
                <h2 class="panel-title"><?= e($label) ?> <span class="muted"><?= count($items) ?></span></h2>
                <ul class="result-list">
                    <?php foreach ($items as [$href, $title, $meta]): ?>
                        <li><a href="<?= e($href) ?>"><strong><?= e($title) ?></strong><span class="muted"><?= e($meta) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php page_end();
