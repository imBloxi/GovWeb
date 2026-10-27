<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('court.view');

$q = input('q');
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE d.document_code LIKE ? OR d.case_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
    $params = array_fill(0, 3, "%$q%");
}
$from = "FROM court_documents d JOIN civilians c ON c.id = d.civilian_id $where";
$result = paginate(
    "SELECT d.*, c.first_name, c.last_name $from ORDER BY d.court_date DESC",
    "SELECT COUNT(*) $from",
    $params
);

page_start('Court documents', 'documents');
page_header(
    'Court documents',
    'Summonses, rulings and other documents issued by the court',
    can('court.manage') ? button_link('Issue document', url('court/document_form.php'), 'primary', 'plus') : ''
);
echo search_bar('Document code, case number or name');
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>Document</th><th>Person</th><th>Court</th><th>Case</th><th>Court date</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $d): ?>
                <tr>
                    <td class="code"><a class="row-link" href="<?= e(url('court/document.php', ['id' => $d['id']])) ?>"><?= e($d['document_code']) ?></a></td>
                    <td><?= person_link($d) ?></td>
                    <td><?= e($d['court_name']) ?></td>
                    <td class="code"><?= e($d['case_number']) ?></td>
                    <td><?= e(format_date($d['court_date'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' ? "No documents match \"$q\"." : 'No court documents yet.') ?>
<?php endif; ?>
<?php page_end();
