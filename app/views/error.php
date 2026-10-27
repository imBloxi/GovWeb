<?php /** @var int $status @var string $title @var string $message */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(config('app_name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body data-dept="home">
<main class="main main-bare" id="main">
    <div class="auth-card">
        <p class="error-code"><?= (int) $status ?></p>
        <h1><?= e($title) ?></h1>
        <p class="page-subtitle"><?= e($message) ?></p>
        <p><a class="btn btn-secondary" href="<?= e(url('index.php')) ?>">Go to overview</a></p>
    </div>
</main>
</body>
</html>
