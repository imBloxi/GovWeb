<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('records.view');

$q = input('q');
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE r.offense LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ? OR c.person_code LIKE ?";
    $params = array_fill(0, 3, "%$q%");
}
$from = "FROM criminal_records r JOIN civilians c ON c.id = r.civilian_id LEFT JOIN users u ON u.id = r.officer_id $where";
$result = paginate(
    "SELECT r.*, c.first_name, c.last_name, c.person_code, u.full_name AS officer $from ORDER BY r.offense_date DESC",
    "SELECT COUNT(*) $from",
    $params
);

page_start('Criminal records', 'records');
page_header(
    'Criminal records',
    'Offenses recorded against people in the register',
    can('records.manage') ? button_link('Add entry', url('police/record_form.php'), 'primary', 'plus') : ''
);
echo search_bar('Offense, name or person code');
?>
<?php if ($result['rows']): ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Person</th><th>Offense</th><th>Sentence</th><th>Recorded by</th><?php if (can('records.manage')): ?><th><span class="sr-only">Actions</span></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $r): ?>
                <tr>
                    <td class="nowrap"><?= e(format_date($r['offense_date'])) ?></td>
                    <td><?= person_link($r) ?><p class="muted small code"><?= e($r['person_code']) ?></p></td>
                    <td>
                        <?= e($r['offense']) ?>
                        <?php if ($r['description']): ?><p class="muted small"><?= e($r['description']) ?></p><?php endif; ?>
                    </td>
                    <td><?= e($r['sentence'] ?: 'None') ?></td>
                    <td><?= e($r['officer'] ?? 'Unknown') ?></td>
                    <?php if (can('records.manage')): ?>
                        <td class="cell-actions"><a href="<?= e(url('police/record_form.php', ['id' => $r['id']])) ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state($q !== '' ? "No entries match \"$q\"." : 'No offenses have been recorded.') ?>
<?php endif; ?>
<?php page_end();
