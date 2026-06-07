<?php
/** @var string $content */
use App\Support\Lang;
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Support\Csrf::token()) ?>">
    <title><?= e(__('app.name')) ?> · <?= e(__('app.tagline')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <!-- Llibreries vendoritzades localment (sense CDN). Vegeu public/assets/vendor/README.md -->
    <script defer src="<?= e(asset('vendor/alpine.min.js')) ?>"></script>
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body>
    <header class="topbar">
        <div class="container topbar__inner">
            <a class="brand" href="<?= e(url('/')) ?>"><?= e(__('app.name')) ?></a>
            <nav class="nav">
                <a href="<?= e(url('/')) ?>"><?= e(__('nav.dashboard')) ?></a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container">
            <?= e(__('app.name')) ?> · <?= e(__('home.version')) ?> <?= e(app_version()) ?>
        </div>
    </footer>
</body>
</html>
