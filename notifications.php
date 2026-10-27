<?php
require __DIR__ . '/app/bootstrap.php';

$user = require_login();

// Opening a notification marks it read, then follows its link.
if ($openId = input_int('open')) {
    $n = db_one('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [$openId, $user['id']]) ?? abort(404);
    db_run('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ?', [$openId]);
    // Links are app paths we stored ourselves; refuse anything that looks like another site.
    $link = (string) $n['link'];
    if ($link !== '' && (!preg_match('#^[a-z0-9_./?=&%-]+$#i', $link) || str_contains($link, '//'))) {
        $link = '';
    }
    redirect($link !== '' ? $link : 'notifications.php');
}

if (is_post() && input('_action') === 'read_all') {
    db_run('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$user['id']]);
    flash('success', 'All notifications marked as read.');
    redirect('notifications.php');
}

$result = paginate(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC',
    'SELECT COUNT(*) FROM notifications WHERE user_id = ?',
    [$user['id']],
    30
);
$unread = unread_notifications();

page_start('Notifications', 'notifications');
page_header(
    'Notifications',
    $unread ? ($unread === 1 ? '1 unread' : "$unread unread") : 'You\'re up to date.',
    $unread ? action_form('', ['_action' => 'read_all'], 'Mark all as read', 'secondary') : ''
);
?>
<?php if ($result['rows']): ?>
    <ul class="notification-list">
        <?php foreach ($result['rows'] as $n): ?>
            <li class="<?= $n['read_at'] ? '' : 'is-unread' ?>">
                <a href="<?= e(url('notifications.php', ['open' => $n['id']])) ?>">
                    <span class="notification-title"><?= e($n['title']) ?></span>
                    <?php if ($n['body']): ?><span class="muted"><?= e($n['body']) ?></span><?php endif; ?>
                    <span class="muted small"><?= e(time_ago($n['created_at'])) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= pagination($result) ?>
<?php else: ?>
    <?= empty_state('Nothing yet. Requests and decisions that need you will show up here.') ?>
<?php endif; ?>
<?php page_end();
