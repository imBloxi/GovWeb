<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_can('dispatch.use');
$errors = [];
$values = ['priority' => 'normal'];

if (is_post() && input('_action') === 'close') {
    $id = input_int('id') ?? 0;
    db_run("UPDATE dispatches SET status = 'closed' WHERE id = ?", [$id]);
    audit('update', 'dispatch', $id, "Closed dispatch call #$id");
    flash('success', 'Call closed.');
    redirect('police/dispatch.php');
}

if (is_post() && input('_action') === 'send') {
    [$data, $errors] = validate([
        'message'  => 'required|max:2000',
        'location' => 'max:160',
        'priority' => 'required|in:low,normal,urgent',
    ]);
    $values = $data;
    if (!$errors) {
        $id = db_insert('dispatches', $data + ['created_by' => $user['id']]);
        audit('create', 'dispatch', $id, 'Sent dispatch: ' . $data['message']);
        flash('success', 'Dispatch sent to all units.');
        redirect('police/dispatch.php');
    }
}

$open = db_all("SELECT d.*, u.full_name FROM dispatches d LEFT JOIN users u ON u.id = d.created_by
                WHERE d.status = 'open' ORDER BY FIELD(d.priority, 'urgent', 'normal', 'low'), d.created_at DESC");
$closed = db_all("SELECT d.*, u.full_name FROM dispatches d LEFT JOIN users u ON u.id = d.created_by
                  WHERE d.status = 'closed' ORDER BY d.created_at DESC LIMIT 20");

form_context($values, $errors);
page_start('Dispatch', 'dispatch');
page_header('Dispatch', count($open) === 1 ? '1 open call' : count($open) . ' open calls');
?>
<div class="split">
    <section class="panel">
        <h2 class="panel-title">New call</h2>
        <?= form_errors_summary($errors) ?>
        <form method="post" class="form-grid form-grid-single" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="send">
            <?= field('message', 'Message', ['type' => 'textarea', 'required' => true, 'rows' => 3, 'placeholder' => 'What is happening and what units should do']) ?>
            <?= field('location', 'Location') ?>
            <?= field('priority', 'Priority', ['type' => 'select', 'required' => true, 'options' => ['urgent' => 'Urgent', 'normal' => 'Normal', 'low' => 'Low']]) ?>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Send dispatch</button></div>
        </form>
    </section>

    <div>
        <section class="panel">
            <h2 class="panel-title">Open calls</h2>
            <?php if ($open): ?>
                <ul class="feed feed-dispatch">
                    <?php foreach ($open as $d): ?>
                        <li data-priority="<?= e($d['priority']) ?>">
                            <?= badge($d['priority']) ?>
                            <div>
                                <p><?= e($d['message']) ?></p>
                                <p class="muted small"><?= e($d['location'] ?: 'No location') ?>, <?= e(time_ago($d['created_at'])) ?> by <?= e($d['full_name'] ?? 'unknown') ?></p>
                            </div>
                            <?= action_form('', ['_action' => 'close', 'id' => $d['id']], 'Close', 'ghost') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">No open calls. New dispatches appear here.</p>
            <?php endif; ?>
        </section>

        <?php if ($closed): ?>
        <details class="panel">
            <summary class="panel-title">Closed calls</summary>
            <ul class="feed">
                <?php foreach ($closed as $d): ?>
                    <li>
                        <div>
                            <p><?= e($d['message']) ?></p>
                            <p class="muted small"><?= e($d['location'] ?: 'No location') ?>, <?= e(format_datetime($d['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    </div>
</div>
<?php page_end();
