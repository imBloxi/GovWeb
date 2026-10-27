<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('court.view');

$q = input('q');
$status = input('status');
$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(f.file_number LIKE ? OR f.case_title LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($status, ['open', 'closed'], true)) {
    $conditions[] = 'f.status = ?';
    $params[] = $status;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$result = paginate(
    "SELECT f.*, (SELECT COUNT(*) FROM court_documents d WHERE d.court_file_id = f.id) AS documents
     FROM court_files f $where ORDER BY f.status = 'closed', f.filed_on DESC",
    "SELECT COUNT(*) FROM court_files f $where",
    $params
);

page_start('Case files', 'files');
page_header(
    'Case files',
    'Every court case and the documents filed under it',
    can('court.manage') ? button_link('Open case file', url('court/file_form.php'), 'primary', 'plus') : ''
);
echo search_bar('File number or case title', ['status' => ['' => 'All files', 'open' => 'Open', 'closed' => 'Closed']]);
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table table-hover">
            <thead><tr><th>File</th><th>Case</th><th>Filed</th><th>Documents</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $f): ?>
                <tr>
                    <td class="code"><a class="row-link" href="<?= e(url('court/file.php', ['id' => $f['id']])) ?>"><?= e($f['file_number']) ?></a></td>
                    <td><?= e($f['case_title']) ?></td>
                    <td><?= e(format_date($f['filed_on'])) ?></td>
                    <td class="num"><?= (int) $f['documents'] ?></td>
                    <td><?= badge($f['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' || $status !== '' ? 'No case files match these filters.' : 'No case files yet.') ?>
<?php endif; ?>
<?php page_end();
