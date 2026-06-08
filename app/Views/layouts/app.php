<?php
/** @var string $content */
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Lang;

$u = Auth::user();
$cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
/** Retorna ' active' si la ruta actual coincideix amb alguna de les donades. */
$navActive = static function (string ...$paths) use ($cur): string {
    foreach ($paths as $p) {
        if ($cur === $p || str_starts_with($cur, rtrim($p, '/') . '/')) {
            return ' active';
        }
    }
    return '';
};
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
                    <a class="nav__link<?= $navActive('/dashboard') ?>" href="<?= e(url('/dashboard')) ?>">
                        <?= icon('dashboard') ?> <span><?= e(__('nav.dashboard')) ?></span></a>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/accounts', '/transactions', '/import') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <?= icon('coins') ?> <span><?= e(__('nav.finances')) ?></span> <span class="nav__caret"><?= icon('chevron', 14) ?></span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/accounts')) ?>"><?= icon('wallet') ?> <span><?= e(__('nav.accounts')) ?></span></a>
                            <a href="<?= e(url('/transactions')) ?>"><?= icon('transfer') ?> <span><?= e(__('nav.transactions')) ?></span></a>
                            <a href="<?= e(url('/import')) ?>"><?= icon('import') ?> <span><?= e(__('nav.import')) ?></span></a>
                        </div>
                    </div>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/categories', '/rules') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <?= icon('tags') ?> <span><?= e(__('nav.classification')) ?></span> <span class="nav__caret"><?= icon('chevron', 14) ?></span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/categories')) ?>"><?= icon('category') ?> <span><?= e(__('nav.categories')) ?></span></a>
                            <a href="<?= e(url('/rules')) ?>"><?= icon('adjustments') ?> <span><?= e(__('nav.rules')) ?></span></a>
                        </div>
                    </div>

                    <a class="nav__link<?= $navActive('/budgets', '/goals', '/recurring') ?>" href="<?= e(url('/budgets')) ?>">
                        <?= icon('target') ?> <span><?= e(__('nav.planning')) ?></span></a>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/reports', '/ai') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <?= icon('chart') ?> <span><?= e(__('nav.analysis')) ?></span> <span class="nav__caret"><?= icon('chevron', 14) ?></span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/reports/monthly')) ?>"><?= icon('report') ?> <span><?= e(__('nav.reports')) ?></span></a>
                            <a href="<?= e(url('/ai/analysis')) ?>"><?= icon('robot') ?> <span><?= e(__('nav.ai')) ?></span></a>
                        </div>
                    </div>

                    <?php if (Auth::isOwner()): ?>
                        <a class="nav__link<?= $navActive('/banking') ?>" href="<?= e(url('/banking')) ?>">
                            <?= icon('bank') ?> <span><?= e(__('nav.banking')) ?></span></a>
                        <div class="nav__group">
                            <button class="nav__btn<?= $navActive('/members', '/update') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                                <?= icon('tool') ?> <span><?= e(__('nav.system')) ?></span> <span class="nav__caret"><?= icon('chevron', 14) ?></span></button>
                            <div class="nav__menu">
                                <a href="<?= e(url('/members')) ?>"><?= icon('users') ?> <span><?= e(__('nav.members')) ?></span></a>
                                <a href="<?= e(url('/update')) ?>"><?= icon('refresh') ?> <span><?= e(__('nav.updates')) ?></span></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="nav__group nav__group--right">
                        <button class="nav__btn nav__user-btn<?= $navActive('/settings') ?>" type="button" aria-haspopup="true" aria-expanded="false" aria-label="<?= e(__('nav.user_menu')) ?>">
                            <?= icon('user') ?> <span><?= e($u['name']) ?></span> <span class="nav__caret"><?= icon('chevron', 14) ?></span></button>
                        <div class="nav__menu nav__menu--right">
                            <span class="nav__meta"><?= e(__('role.' . $u['role'])) ?></span>
                            <a href="<?= e(url('/settings')) ?>"><?= icon('settings') ?> <span><?= e(__('nav.settings')) ?></span></a>
                            <form method="post" action="<?= e(url('/logout')) ?>">
                                <?= csrf_field() ?>
                                <button class="nav__logout" type="submit"><?= icon('logout') ?> <span><?= e(__('nav.logout')) ?></span></button>
                            </form>
                        </div>
                    </div>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container" id="main">
        <?= $content ?>
    </main>

    <?php if ($u): ?>
        <!-- Copilot financer: xat d'IA flotant disponible a totes les pàgines -->
        <div class="copilot" id="copilot"
             data-ask="<?= e(url('/ai/chat/ask')) ?>"
             data-history="<?= e(url('/ai/chat/history')) ?>"
             data-clear="<?= e(url('/ai/chat/clear')) ?>"
             data-expand-key="finances.copilot.expanded"
             data-you="<?= e(__('ai.you')) ?>"
             data-assistant="<?= e(__('ai.assistant')) ?>"
             data-error="<?= e(__('copilot.error')) ?>"
             data-disabled="<?= e(__('ai.disabled')) ?>"
             data-confirm="<?= e(__('copilot.clear_confirm')) ?>">
            <button class="copilot__toggle" id="copilotToggle" type="button"
                    aria-label="<?= e(__('copilot.toggle')) ?>" title="<?= e(__('copilot.toggle')) ?>"
                    aria-expanded="false" aria-controls="copilotPanel"><?= icon('chat', 24) ?></button>
            <section class="copilot__panel" id="copilotPanel" hidden aria-label="<?= e(__('copilot.title')) ?>">
                <header class="copilot__head">
                    <div>
                        <h2 class="copilot__title"><?= icon('robot', 16) ?> <?= e(__('copilot.title')) ?></h2>
                        <p class="copilot__subtitle"><?= e(__('copilot.subtitle')) ?></p>
                    </div>
                    <div class="copilot__head-actions">
                        <button type="button" class="copilot__icon-btn" id="copilotClear" title="<?= e(__('copilot.clear')) ?>" aria-label="<?= e(__('copilot.clear')) ?>"><?= icon('trash', 16) ?></button>
                        <button type="button" class="copilot__icon-btn" id="copilotExpand" title="<?= e(__('copilot.expand')) ?>" aria-label="<?= e(__('copilot.expand')) ?>" aria-pressed="false">
                            <span class="copilot__icon-expand"><?= icon('maximize', 16) ?></span>
                            <span class="copilot__icon-collapse"><?= icon('minimize', 16) ?></span>
                        </button>
                        <button type="button" class="copilot__icon-btn" id="copilotClose" title="<?= e(__('copilot.close')) ?>" aria-label="<?= e(__('copilot.close')) ?>"><?= icon('x', 16) ?></button>
                    </div>
                </header>
                <div class="copilot__messages" id="copilotMessages" aria-live="polite">
                    <div class="copilot__greeting" id="copilotGreeting"><p><?= e(__('copilot.greeting')) ?></p></div>
                </div>
                <form class="copilot__form" id="copilotForm">
                    <textarea class="copilot__input" id="copilotInput" name="question" rows="2"
                              placeholder="<?= e(__('copilot.placeholder')) ?>" required></textarea>
                    <button class="btn" type="submit"><?= e(__('copilot.send')) ?></button>
                </form>
            </section>
        </div>
    <?php endif; ?>

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
