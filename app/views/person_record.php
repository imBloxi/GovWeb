<?php
/**
 * A person's full record. Expects $person (civilians row) and $self
 * (true when the civilian is looking at their own record).
 * Each section only appears if the viewer is allowed to see it.
 */
$id = (int) $person['id'];
$self = $self ?? false;

$licenses = ($self || can('licenses.view'))
    ? db_all('SELECT * FROM licenses WHERE civilian_id = ? ORDER BY expires_on DESC', [$id]) : null;
$businesses = ($self || can('businesses.view'))
    ? db_all('SELECT * FROM businesses WHERE owner_id = ? ORDER BY registered_on DESC', [$id]) : null;
$records = can('records.view')
    ? db_all('SELECT r.*, u.full_name AS officer FROM criminal_records r LEFT JOIN users u ON u.id = r.officer_id WHERE r.civilian_id = ? ORDER BY offense_date DESC', [$id]) : null;
$warrants = can('warrants.view')
    ? db_all('SELECT * FROM warrants WHERE civilian_id = ? ORDER BY created_at DESC', [$id]) : null;
$documents = ($self || can('court.view'))
    ? db_all('SELECT * FROM court_documents WHERE civilian_id = ? ORDER BY court_date DESC', [$id]) : null;

$activeWarrants = $warrants ? count(array_filter($warrants, fn($w) => $w['status'] === 'issued')) : 0;
?>
<article class="record">
    <header class="record-head">
        <div class="record-identity">
            <p class="record-code" title="Person code"><?= e($person['person_code']) ?></p>
            <h1><?= e(full_name($person)) ?></h1>
            <?php if ($activeWarrants): ?>
                <p class="record-flag"><?= icon('stamp') ?> <?= $activeWarrants === 1 ? 'Active warrant' : "$activeWarrants active warrants" ?></p>
            <?php endif; ?>
        </div>
        <?php if (!$self && can('civilians.manage')): ?>
            <div class="page-actions">
                <?= button_link('Edit details', url('registry/civilian_form.php', ['id' => $id]), 'secondary', 'edit') ?>
            </div>
        <?php endif; ?>
    </header>

    <dl class="facts">
        <div><dt>Born</dt><dd><?= e(format_date($person['dob'])) ?> <span class="muted">(<?= age($person['dob']) ?>)</span></dd></div>
        <div><dt>Gender</dt><dd><?= e(ucfirst($person['gender'])) ?></dd></div>
        <div><dt>Occupation</dt><dd><?= e($person['occupation'] ?: 'Not recorded') ?></dd></div>
        <div><dt>Phone</dt><dd><?= e($person['phone'] ?: 'Not recorded') ?></dd></div>
        <div><dt>Email</dt><dd><?= e($person['email'] ?: 'Not recorded') ?></dd></div>
        <div><dt>Address</dt><dd><?= e($person['address'] ?: 'Not recorded') ?></dd></div>
    </dl>

    <?php if ($licenses !== null): ?>
    <section class="record-section" data-dept="registry">
        <div class="panel-head">
            <h2 class="panel-title">Licenses</h2>
            <?php if (!$self && can('licenses.manage')) echo button_link('Issue license', url('registry/license_form.php', ['civilian_id' => $id]), 'ghost', 'plus'); ?>
        </div>
        <?php if ($licenses): ?>
            <div class="table-scroll"><table class="table">
                <thead><tr><th>Number</th><th>Type</th><th>Issued</th><th>Expires</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($licenses as $l): ?>
                    <tr>
                        <td class="code"><?= e($l['license_number']) ?></td>
                        <td><?= e(ucfirst($l['license_type'])) ?></td>
                        <td><?= e(format_date($l['issued_on'])) ?></td>
                        <td><?= e(format_date($l['expires_on'])) ?></td>
                        <td><?= badge(license_status($l)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">No licenses.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($businesses !== null): ?>
    <section class="record-section" data-dept="registry">
        <div class="panel-head">
            <h2 class="panel-title">Businesses</h2>
            <?php if (!$self && can('businesses.manage')) echo button_link('Register business', url('registry/business_form.php', ['owner_id' => $id]), 'ghost', 'plus'); ?>
        </div>
        <?php if ($businesses): ?>
            <div class="table-scroll"><table class="table">
                <thead><tr><th>Name</th><th>Type</th><th>Address</th><th>Registered</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($businesses as $b): ?>
                    <tr>
                        <td><?= e($b['name']) ?></td>
                        <td><?= e($b['business_type']) ?></td>
                        <td><?= e($b['address']) ?></td>
                        <td><?= e(format_date($b['registered_on'])) ?></td>
                        <td><?= badge($b['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">No registered businesses.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($warrants !== null): ?>
    <section class="record-section" data-dept="police">
        <div class="panel-head">
            <h2 class="panel-title">Warrants</h2>
            <?php if (can('warrants.request')) echo button_link('Request warrant', url('police/warrant_form.php', ['civilian_id' => $id]), 'ghost', 'plus'); ?>
        </div>
        <?php if ($warrants): ?>
            <div class="table-scroll"><table class="table">
                <thead><tr><th>Code</th><th>Reason</th><th>Requested</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($warrants as $w): ?>
                    <tr>
                        <td class="code"><a href="<?= e(url('police/warrants.php', ['q' => $w['code']])) ?>"><?= e($w['code']) ?></a></td>
                        <td><?= e($w['reason']) ?></td>
                        <td><?= e(format_date($w['created_at'])) ?></td>
                        <td><?= badge($w['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">No warrants.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($records !== null): ?>
    <section class="record-section" data-dept="police">
        <div class="panel-head">
            <h2 class="panel-title">Criminal record</h2>
            <?php if (can('records.manage')) echo button_link('Add entry', url('police/record_form.php', ['civilian_id' => $id]), 'ghost', 'plus'); ?>
        </div>
        <?php if ($records): ?>
            <div class="table-scroll"><table class="table">
                <thead><tr><th>Date</th><th>Offense</th><th>Sentence</th><th>Recorded by</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= e(format_date($r['offense_date'])) ?></td>
                        <td>
                            <?= e($r['offense']) ?>
                            <?php if ($r['description']): ?><p class="muted small"><?= e($r['description']) ?></p><?php endif; ?>
                        </td>
                        <td><?= e($r['sentence'] ?: 'None') ?></td>
                        <td><?= e($r['officer'] ?? 'Unknown') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">Clean record.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($documents !== null): ?>
    <section class="record-section" data-dept="court">
        <div class="panel-head">
            <h2 class="panel-title">Court documents</h2>
            <?php if (!$self && can('court.manage')) echo button_link('Issue document', url('court/document_form.php', ['civilian_id' => $id]), 'ghost', 'plus'); ?>
        </div>
        <?php if ($documents): ?>
            <div class="table-scroll"><table class="table">
                <thead><tr><th>Document</th><th>Court</th><th>Case</th><th>Court date</th></tr></thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td class="code">
                            <?php if (can('court.view')): ?>
                                <a href="<?= e(url('court/document.php', ['id' => $d['id']])) ?>"><?= e($d['document_code']) ?></a>
                            <?php else: ?>
                                <?= e($d['document_code']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= e($d['court_name']) ?></td>
                        <td class="code"><?= e($d['case_number']) ?></td>
                        <td><?= e(format_date($d['court_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p class="muted">No court documents.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</article>
