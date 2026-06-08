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
        <p class="langswitch langswitch--center">
            <a href="<?= e(url('/locale/ca')) ?>" <?= Lang::locale() === 'ca' ? 'aria-current="true"' : '' ?>>CA</a>
            <a href="<?= e(url('/locale/es')) ?>" <?= Lang::locale() === 'es' ? 'aria-current="true"' : '' ?>>ES</a>
        </p>
    </div>
</body>
</html>
