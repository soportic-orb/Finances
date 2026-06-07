<?php
/** @var string $content */
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Lang;

$u = Auth::user();
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e(__('app.name')) ?> · <?= e(__('app.tagline')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('vendor/alpine.min.js')) ?>"></script>
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body>
    <header class="topbar">
        <div class="container topbar__inner">
            <a class="brand" href="<?= e(url('/dashboard')) ?>"><?= e(__('app.name')) ?></a>
            <?php if ($u): ?>
                <nav class="nav">
                    <a href="<?= e(url('/dashboard')) ?>"><?= e(__('nav.dashboard')) ?></a>
                    <a href="<?= e(url('/accounts')) ?>"><?= e(__('nav.accounts')) ?></a>
                    <a href="<?= e(url('/transactions')) ?>"><?= e(__('nav.transactions')) ?></a>
                    <?php if (Auth::isOwner()): ?>
                        <a href="<?= e(url('/members')) ?>"><?= e(__('nav.members')) ?></a>
                    <?php endif; ?>
                    <a href="<?= e(url('/settings')) ?>"><?= e(__('nav.settings')) ?></a>
                    <span class="nav__user"><?= e($u['name']) ?> · <?= e(__('role.' . $u['role'])) ?></span>
                    <form method="post" action="<?= e(url('/logout')) ?>" style="display:inline">
                        <?= csrf_field() ?>
                        <button class="linkbtn" type="submit"><?= e(__('nav.logout')) ?></button>
                    </form>
                </nav>
            <?php endif; ?>
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
