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
    <a class="skip-link" href="#main"><?= e(__('a11y.skip')) ?></a>
    <header class="topbar">
        <div class="container topbar__inner">
            <a class="brand" href="<?= e(url('/dashboard')) ?>"><?= e(__('app.name')) ?></a>
            <?php if ($u): ?>
                <nav class="nav" aria-label="<?= e(__('a11y.nav')) ?>">
                    <a href="<?= e(url('/dashboard')) ?>"><?= e(__('nav.dashboard')) ?></a>
                    <a href="<?= e(url('/accounts')) ?>"><?= e(__('nav.accounts')) ?></a>
                    <a href="<?= e(url('/transactions')) ?>"><?= e(__('nav.transactions')) ?></a>
                    <a href="<?= e(url('/categories')) ?>"><?= e(__('nav.categories')) ?></a>
                    <a href="<?= e(url('/budgets')) ?>"><?= e(__('nav.planning')) ?></a>
                    <a href="<?= e(url('/reports/monthly')) ?>"><?= e(__('nav.reports')) ?></a>
                    <a href="<?= e(url('/ai/analysis')) ?>"><?= e(__('nav.ai')) ?></a>
                    <a href="<?= e(url('/rules')) ?>"><?= e(__('nav.rules')) ?></a>
                    <a href="<?= e(url('/import')) ?>"><?= e(__('nav.import')) ?></a>
                    <?php if (Auth::isOwner()): ?>
                        <a href="<?= e(url('/banking')) ?>"><?= e(__('nav.banking')) ?></a>
                        <a href="<?= e(url('/members')) ?>"><?= e(__('nav.members')) ?></a>
                        <a href="<?= e(url('/update')) ?>"><?= e(__('nav.system')) ?></a>
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

    <main class="container" id="main">
        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container footer__inner">
            <span><?= e(__('app.name')) ?> · <?= e(__('home.version')) ?> <?= e(app_version()) ?> · <span class="muted"><?= e(__('privacy.footer')) ?></span></span>
            <span class="langswitch">
                <a href="<?= e(url('/locale/ca')) ?>" <?= Lang::locale() === 'ca' ? 'aria-current="true"' : '' ?>>CA</a>
                <a href="<?= e(url('/locale/es')) ?>" <?= Lang::locale() === 'es' ? 'aria-current="true"' : '' ?>>ES</a>
            </span>
        </div>
    </footer>
</body>
</html>
