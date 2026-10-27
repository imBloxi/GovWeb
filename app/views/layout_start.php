<?php
/** @var string $title @var string $nav @var string $dept @var ?array $user @var array $flashes */
$appName = config('app_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,400..700;1,400&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body data-dept="<?= e($dept) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php if ($user): ?>
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <a class="brand" href="<?= e(url('index.php')) ?>">
                <span class="brand-mark" aria-hidden="true"></span>
                <span><?= e($appName) ?></span>
            </a>
            <button class="btn btn-ghost nav-toggle" type="button" aria-controls="sidebar-nav" aria-expanded="false">
                <?= icon('menu') ?><span class="sr-only">Menu</span>
            </button>
        </div>
        <nav class="sidebar-nav" id="sidebar-nav" aria-label="Main">
            <?php foreach (navigation() as $group):
                $items = array_filter($group['items'], fn($i) => $i['can'] === null || can($i['can']));
                if (!$items) continue; ?>
                <div class="nav-group" data-dept="<?= e($group['dept']) ?>">
                    <?php if ($group['label']): ?><p class="nav-group-label"><?= e($group['label']) ?></p><?php endif; ?>
                    <ul>
                        <?php foreach ($items as $item): ?>
                            <li><a href="<?= e(url($item['href'])) ?>"<?= $item['key'] === $nav ? ' aria-current="page"' : '' ?>><?= icon($item['icon']) ?><span><?= e($item['label']) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            <div class="sidebar-user">
                <a class="user-chip" href="<?= e(url('account.php')) ?>"<?= $nav === 'account' ? ' aria-current="page"' : '' ?>>
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    <span class="user-chip-text">
                        <span class="user-chip-name"><?= e($user['full_name']) ?></span>
                        <span class="user-chip-role"><?= e(role_label($user['role'])) ?></span>
                    </span>
                </a>
                <form method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost btn-icon" type="submit" title="Sign out"><?= icon('logout') ?><span class="sr-only">Sign out</span></button>
                </form>
            </div>
        </nav>
    </aside>
    <main class="main" id="main">
<?php else: ?>
    <main class="main main-bare" id="main">
<?php endif; ?>
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
