<?php
/** @var string $content */
use App\Support\Csrf;
use App\Support\Lang;
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(__('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body class="auth">
    <div class="auth__box">
        <div class="auth__brand"><?= e(__('app.name')) ?></div>
        <p class="muted auth__tag"><?= e(__('app.tagline')) ?></p>
        <div class="card">
            <?= $content ?>
        </div>
    </div>
</body>
</html>
