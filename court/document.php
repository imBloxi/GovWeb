<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('court.view');

$doc = db_one(
    'SELECT d.*, c.first_name, c.last_name, c.person_code, c.dob, c.gender, c.address,
            f.file_number, f.case_title, u.full_name AS issuer
     FROM court_documents d
     JOIN civilians c ON c.id = d.civilian_id
     LEFT JOIN court_files f ON f.id = d.court_file_id
     LEFT JOIN users u ON u.id = d.issued_by
     WHERE d.id = ?',
    [input_int('id') ?? 0]
) ?? abort(404);
$charges = array_column(db_all('SELECT charge FROM court_charges WHERE document_id = ? ORDER BY id', [$doc['id']]), 'charge');

$actions = '<button class="btn btn-secondary" type="button" data-print>' . icon('printer') . '<span>Print</span></button>';
if (can('court.manage')) {
    $actions .= button_link('Edit', url('court/document_form.php', ['id' => $doc['id']]), 'secondary', 'edit');
}

page_start($doc['document_code'], 'documents');
?>
<p class="crumbs no-print"><a href="<?= e(url('court/documents.php')) ?>"><?= icon('arrow-left') ?>Documents</a></p>
<div class="no-print"><?php page_header('Court document', '', $actions); ?></div>

<article class="court-paper">
    <header class="court-paper-head">
        <div>
            <p class="court-paper-court"><?= e($doc['court_name']) ?></p>
            <p class="muted">Case <?= e($doc['case_number']) ?><?= ($doc['file_number'] && $doc['file_number'] !== $doc['case_number']) ? ', file ' . e($doc['file_number']) : '' ?></p>
        </div>
        <p class="court-paper-code"><?= e($doc['document_code']) ?></p>
    </header>

    <h1 class="court-paper-title"><?= e($doc['case_title'] ?: 'Court document') ?></h1>

    <dl class="facts facts-paper">
        <div><dt>Concerning</dt><dd><?= e(full_name($doc)) ?></dd></div>
        <div><dt>Person code</dt><dd class="code"><?= e($doc['person_code']) ?></dd></div>
        <div><dt>Born</dt><dd><?= e(format_date($doc['dob'])) ?></dd></div>
        <div><dt>Address</dt><dd><?= e($doc['address'] ?: 'Not recorded') ?></dd></div>
        <div><dt>Court date</dt><dd><?= e(format_date($doc['court_date'])) ?></dd></div>
        <div><dt>Judge</dt><dd><?= e($doc['judge_name']) ?></dd></div>
    </dl>

    <section>
        <h2>Charges</h2>
        <ol class="charges">
            <?php foreach ($charges as $charge): ?><li><?= e($charge) ?></li><?php endforeach; ?>
        </ol>
    </section>

    <?php if ($doc['description']): ?>
        <section>
            <h2>Details</h2>
            <p class="prose"><?= nl2br(e($doc['description'])) ?></p>
        </section>
    <?php endif; ?>

    <footer class="court-paper-foot">
        <div class="signature">
            <span class="signature-line"></span>
            <span><?= e($doc['judge_name']) ?></span>
        </div>
        <p class="muted small">Issued <?= e(format_datetime($doc['created_at'])) ?><?= $doc['issuer'] ? ' by ' . e($doc['issuer']) : '' ?></p>
    </footer>
</article>
<?php page_end();
