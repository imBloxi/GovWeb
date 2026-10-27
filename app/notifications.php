<?php
declare(strict_types=1);

// In-app notifications. Links are stored as app paths (e.g. "police/warrants.php")
// and turned into URLs when shown.

function notify_user(int $userId, string $title, string $body = '', string $link = ''): void
{
    db_insert('notifications', [
        'user_id' => $userId,
        'title' => mb_substr($title, 0, 160),
        'body' => $body === '' ? null : mb_substr($body, 0, 255),
        'link' => $link === '' ? null : $link,
    ]);
}

/** Notify everyone with a role, except the person who caused it. */
function notify_role(string $role, string $title, string $body = '', string $link = ''): void
{
    $except = current_user()['id'] ?? 0;
    foreach (db_all('SELECT id FROM users WHERE role = ? AND id <> ?', [$role, $except]) as $u) {
        notify_user((int) $u['id'], $title, $body, $link);
    }
}

/** Notify the sign-in accounts linked to a person in the register. */
function notify_civilian(int $civilianId, string $title, string $body = '', string $link = ''): void
{
    foreach (db_all("SELECT id FROM users WHERE civilian_id = ? AND role = 'civilian'", [$civilianId]) as $u) {
        notify_user((int) $u['id'], $title, $body, $link);
    }
}

function unread_notifications(): int
{
    static $count = null;
    $user = current_user();
    if (!$user) {
        return 0;
    }
    return $count ??= (int) db_value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$user['id']]);
}
