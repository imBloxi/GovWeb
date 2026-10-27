<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('court.view');

$file = db_one('SELECT * FROM court_files WHERE id = ?', [input_int('id') ?? 0]) ?? abort(404);
$documents = db_all(
    'SELECT d.*, c.first_name, c.last_name FROM court_documents d
     JOIN civilians c ON c.id = d.civilian_id WHERE d.court_file_id = ? ORDER BY d.court_date DESC',
    [$file['id']]
);

$actions = '';
if (can('court.manage')) {
    $actions = button_link('Edit file', url('court/file_form.php', ['id' => $file['id']]), 'secondary', 'edit')
        . button_link('Issue document', url('court/document_form.php', ['court_file_id' => $file['id']]), 'primary', 'plus');
}

page_start($file['file_number'], 'files');
?>
<p class="crumbs"><a href="<?= e(url('court/files.php')) ?>"><?= icon('arrow-left') ?>Case files</a></p>
<?php page_header($file['case_title'], $file['file_number'] . ', filed ' . format_date($file['filed_on']), $actions); ?>

<section class="panel">
    <p><?= badge($file['status']) ?></p>
    <p class="prose"><?= nl2br(e($file['description'] ?: 'No description.')) ?></p>
</section>

<section class="record-section" data-dept="court">
    <h2 class="panel-title">Documents</h2>
    <?php if ($documents): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Document</th><th>Person</th><th>Court</th><th>Court date</th></tr></thead>
                <tbody>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td class="code"><a href="<?= e(url('court/document.php', ['id' => $d['id']])) ?>"><?= e($d['document_code']) ?></a></td>
                        <td><?= person_link($d) ?></td>
                        <td><?= e($d['court_name']) ?></td>
                        <td><?= e(format_date($d['court_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">No documents in this file yet.</p>
    <?php endif; ?>
</section>
<?php page_end();
