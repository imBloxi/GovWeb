<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('court.manage');

$id = input_int('id');
$document = $id ? (db_one('SELECT * FROM court_documents WHERE id = ?', [$id]) ?? abort(404)) : null;
$errors = [];

if ($document) {
    $values = $document;
    $values['charges'] = implode("\n", array_column(db_all('SELECT charge FROM court_charges WHERE document_id = ? ORDER BY id', [$id]), 'charge'));
} else {
    $fileId = input_int('court_file_id');
    $file = $fileId ? db_one('SELECT * FROM court_files WHERE id = ?', [$fileId]) : null;
    $values = [
        'civilian_id' => input_int('civilian_id'),
        'court_file_id' => $file['id'] ?? null,
        'case_number' => $file['file_number'] ?? '',
        'judge_name' => $user['role'] === 'judge' ? $user['full_name'] : '',
        'court_date' => date('Y-m-d'),
    ];
}

if (is_post() && input('_action') === 'delete' && $document) {
    db_delete('court_documents', $id);
    audit('delete', 'court_document', $id, "Deleted court document {$document['document_code']}");
    flash('success', "Document {$document['document_code']} was deleted.");
    redirect('court/documents.php');
}

if (is_post() && input('_action') !== 'delete') {
    [$data, $errors] = validate([
        'civilian_id'   => 'required|int',
        'court_file_id' => 'int',
        'court_name'    => 'required|max:160',
        'judge_name'    => 'required|max:120',
        'case_number'   => 'required|max:40',
        'court_date'    => 'required|date',
        'description'   => 'max:10000',
    ]);
    // One charge per line; blank lines ignored.
    $charges = array_values(array_filter(array_map('trim', preg_split('/\R/', input('charges'))), fn($c) => $c !== ''));
    if (!$charges) {
        $errors['charges'] = 'Add at least one charge.';
    } elseif (array_filter($charges, fn($c) => mb_strlen($c) > 255)) {
        $errors['charges'] = 'Each charge must be at most 255 characters.';
    }
    if (!isset($errors['civilian_id']) && !db_value('SELECT 1 FROM civilians WHERE id = ?', [$data['civilian_id']])) {
        $errors['civilian_id'] = 'Choose a person from the list.';
    }
    if ($data['court_file_id'] && !db_value('SELECT 1 FROM court_files WHERE id = ?', [$data['court_file_id']])) {
        $errors['court_file_id'] = 'Choose a case file from the list.';
    }
    $values = $data + ['charges' => input('charges')];

    if (!$errors) {
        $id = db_transaction(function () use ($document, $id, $data, $charges, $user) {
            if ($document) {
                db_update('court_documents', $id, $data);
                db_run('DELETE FROM court_charges WHERE document_id = ?', [$id]);
            } else {
                do {
                    $data['document_code'] = random_code('D');
                } while (db_value('SELECT 1 FROM court_documents WHERE document_code = ?', [$data['document_code']]));
                $id = db_insert('court_documents', $data + ['issued_by' => $user['id']]);
            }
            foreach ($charges as $charge) {
                db_insert('court_charges', ['document_id' => $id, 'charge' => $charge]);
            }
            return $id;
        });
        $code = $document['document_code'] ?? db_value('SELECT document_code FROM court_documents WHERE id = ?', [$id]);
        audit($document ? 'update' : 'create', 'court_document', $id, ($document ? 'Updated' : 'Issued') . " court document $code");
        flash('success', $document ? 'Document saved.' : "Document $code issued.");
        redirect('court/document.php', ['id' => $id]);
    }
}

$fileOptions = [];
foreach (db_all("SELECT id, file_number, case_title FROM court_files ORDER BY status = 'closed', filed_on DESC") as $f) {
    $fileOptions[$f['id']] = $f['file_number'] . ', ' . $f['case_title'];
}

$title = $document ? "Edit {$document['document_code']}" : 'Issue court document';
form_context($values, $errors);
page_start($title, 'documents');
?>
<p class="crumbs"><a href="<?= e(url('court/documents.php')) ?>"><?= icon('arrow-left') ?>Documents</a></p>
<?php page_header($title, $document ? '' : 'The document code is created when you save.'); ?>
<section class="panel form-panel">
    <?= form_errors_summary($errors) ?>
    <form method="post" class="form-grid" novalidate>
        <?= csrf_field() ?>
        <?= person_field('civilian_id', 'Person', ['required' => true, 'wide' => true]) ?>
        <?= field('court_file_id', 'Case file', ['type' => 'select', 'blank' => 'Not filed under a case', 'options' => $fileOptions, 'wide' => true]) ?>
        <?= field('court_name', 'Court', ['required' => true, 'placeholder' => 'Riga City Court']) ?>
        <?= field('judge_name', 'Judge', ['required' => true]) ?>
        <?= field('case_number', 'Case number', ['required' => true]) ?>
        <?= field('court_date', 'Court date', ['type' => 'date', 'required' => true]) ?>
        <?= field('charges', 'Charges', ['type' => 'textarea', 'required' => true, 'wide' => true, 'rows' => 4, 'hint' => 'One charge per line.']) ?>
        <?= field('description', 'Details', ['type' => 'textarea', 'wide' => true, 'rows' => 6]) ?>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $document ? 'Save changes' : 'Issue document' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('court/documents.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php if ($document): ?>
    <section class="panel danger-zone">
        <h2 class="panel-title">Delete document</h2>
        <?= action_form('', ['_action' => 'delete', 'id' => $id], 'Delete document', 'danger', "Delete document {$document['document_code']}?") ?>
    </section>
<?php endif; ?>
<?php page_end();
