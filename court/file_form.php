<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_can('court.manage');

$id = input_int('id');
$file = $id ? (db_one('SELECT * FROM court_files WHERE id = ?', [$id]) ?? abort(404)) : null;
$errors = [];
$values = $file ?? ['filed_on' => date('Y-m-d'), 'status' => 'open'];

if (is_post() && input('_action') === 'delete' && $file) {
    db_delete('court_files', $id);
    audit('delete', 'court_file', $id, "Deleted case file {$file['file_number']}");
    flash('success', "Case file {$file['file_number']} was deleted. Its documents were kept.");
    redirect('court/files.php');
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'case_title'  => 'required|max:200',
        'filed_on'    => 'required|date',
        'status'      => 'required|in:open,closed',
        'description' => 'max:5000',
    ]);
    $values = $data;
    if (!$errors) {
        if ($file) {
            db_update('court_files', $id, $data);
            audit('update', 'court_file', $id, "Updated case file {$file['file_number']}");
            flash('success', 'Case file saved.');
        } else {
            // File numbers run per year: CF-2026-0001, CF-2026-0002...
            $year = date('Y', strtotime($data['filed_on']));
            $next = (int) db_value("SELECT COUNT(*) FROM court_files WHERE file_number LIKE ?", ["CF-$year-%"]) + 1;
            do {
                $data['file_number'] = sprintf('CF-%s-%04d', $year, $next++);
            } while (db_value('SELECT 1 FROM court_files WHERE file_number = ?', [$data['file_number']]));
            $id = db_insert('court_files', $data);
            audit('create', 'court_file', $id, "Opened case file {$data['file_number']}");
            flash('success', "Case file {$data['file_number']} opened.");
        }
        redirect('court/file.php', ['id' => $id]);
    }
}

$title = $file ? "Edit {$file['file_number']}" : 'Open case file';
form_context($values, $errors);
page_start($title, 'files');
?>
<p class="crumbs"><a href="<?= e(url('court/files.php')) ?>"><?= icon('arrow-left') ?>Case files</a></p>
<?php page_header($title, $file ? '' : 'The file number is created when you save.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= field('case_title', 'Case title', ['required' => true, 'wide' => true, 'placeholder' => 'State vs Jane Smith']) ?>
        <?= field('filed_on', 'Filed on', ['type' => 'date', 'required' => true]) ?>
        <?= field('status', 'Status', ['type' => 'select', 'required' => true, 'options' => ['open' => 'Open', 'closed' => 'Closed']]) ?>
        <?= field('description', 'Description', ['type' => 'textarea', 'wide' => true]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $file ? 'Save changes' : 'Open case file' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('court/files.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php if ($file): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete case file</h2>
        <p class="muted">Documents in this file are kept but no longer linked to it.</p>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete case file', 'danger', "Delete case file {$file['file_number']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
