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
                        <span class="nav__ico">📊</span> <?= e(__('nav.dashboard')) ?></a>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/accounts', '/transactions', '/import') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="nav__ico">💼</span> <?= e(__('nav.finances')) ?> <span class="nav__caret">▾</span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/accounts')) ?>"><span class="nav__ico">🏦</span> <?= e(__('nav.accounts')) ?></a>
                            <a href="<?= e(url('/transactions')) ?>"><span class="nav__ico">🔁</span> <?= e(__('nav.transactions')) ?></a>
                            <a href="<?= e(url('/import')) ?>"><span class="nav__ico">📥</span> <?= e(__('nav.import')) ?></a>
                        </div>
                    </div>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/categories', '/rules') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="nav__ico">🏷️</span> <?= e(__('nav.classification')) ?> <span class="nav__caret">▾</span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/categories')) ?>"><span class="nav__ico">🗂️</span> <?= e(__('nav.categories')) ?></a>
                            <a href="<?= e(url('/rules')) ?>"><span class="nav__ico">⚖️</span> <?= e(__('nav.rules')) ?></a>
                        </div>
                    </div>

                    <a class="nav__link<?= $navActive('/budgets', '/goals', '/recurring') ?>" href="<?= e(url('/budgets')) ?>">
                        <span class="nav__ico">🎯</span> <?= e(__('nav.planning')) ?></a>

                    <div class="nav__group">
                        <button class="nav__btn<?= $navActive('/reports', '/ai') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                            <span class="nav__ico">📈</span> <?= e(__('nav.analysis')) ?> <span class="nav__caret">▾</span></button>
                        <div class="nav__menu">
                            <a href="<?= e(url('/reports/monthly')) ?>"><span class="nav__ico">📄</span> <?= e(__('nav.reports')) ?></a>
                            <a href="<?= e(url('/ai/analysis')) ?>"><span class="nav__ico">🤖</span> <?= e(__('nav.ai')) ?></a>
                        </div>
                    </div>

                    <?php if (Auth::isOwner()): ?>
                        <a class="nav__link<?= $navActive('/banking') ?>" href="<?= e(url('/banking')) ?>">
                            <span class="nav__ico">🏛️</span> <?= e(__('nav.banking')) ?></a>
                        <div class="nav__group">
                            <button class="nav__btn<?= $navActive('/members', '/update') ?>" type="button" aria-haspopup="true" aria-expanded="false">
                                <span class="nav__ico">🛠️</span> <?= e(__('nav.system')) ?> <span class="nav__caret">▾</span></button>
                            <div class="nav__menu">
                                <a href="<?= e(url('/members')) ?>"><span class="nav__ico">👥</span> <?= e(__('nav.members')) ?></a>
                                <a href="<?= e(url('/update')) ?>"><span class="nav__ico">🔄</span> <?= e(__('nav.updates')) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="nav__group nav__group--right">
                        <button class="nav__btn nav__user-btn<?= $navActive('/settings') ?>" type="button" aria-haspopup="true" aria-expanded="false" aria-label="<?= e(__('nav.user_menu')) ?>">
                            <span class="nav__ico">👤</span> <?= e($u['name']) ?> <span class="nav__caret">▾</span></button>
                        <div class="nav__menu nav__menu--right">
                            <span class="nav__meta"><?= e(__('role.' . $u['role'])) ?></span>
                            <a href="<?= e(url('/settings')) ?>"><span class="nav__ico">⚙️</span> <?= e(__('nav.settings')) ?></a>
                            <form method="post" action="<?= e(url('/logout')) ?>">
                                <?= csrf_field() ?>
                                <button class="nav__logout" type="submit"><span class="nav__ico">🚪</span> <?= e(__('nav.logout')) ?></button>
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
                    aria-expanded="false" aria-controls="copilotPanel">
                <svg class="copilot__toggle-icon" viewBox="0 0 24 24" width="24" height="24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="8" width="16" height="11" rx="2"></rect>
                    <path d="M12 8V4"></path><circle cx="12" cy="3" r="1"></circle>
                    <path d="M2 13v2"></path><path d="M22 13v2"></path>
                    <circle cx="9" cy="13" r="1"></circle><circle cx="15" cy="13" r="1"></circle>
                    <path d="M9.5 16.5h5"></path>
                </svg>
            </button>
            <section class="copilot__panel" id="copilotPanel" hidden aria-label="<?= e(__('copilot.title')) ?>">
                <header class="copilot__head">
                    <div>
                        <h2 class="copilot__title">🤖 <?= e(__('copilot.title')) ?></h2>
                        <p class="copilot__subtitle"><?= e(__('copilot.subtitle')) ?></p>
                    </div>
                    <div class="copilot__head-actions">
                        <button type="button" class="copilot__icon-btn" id="copilotClear" title="<?= e(__('copilot.clear')) ?>" aria-label="<?= e(__('copilot.clear')) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>
                            </svg>
                        </button>
                        <button type="button" class="copilot__icon-btn" id="copilotExpand" title="<?= e(__('copilot.expand')) ?>" aria-label="<?= e(__('copilot.expand')) ?>" aria-pressed="false">
                            <svg class="copilot__icon-expand" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                            <svg class="copilot__icon-collapse" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                        </button>
                        <button type="button" class="copilot__icon-btn" id="copilotClose" title="<?= e(__('copilot.close')) ?>" aria-label="<?= e(__('copilot.close')) ?>">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="6" y1="6" x2="18" y2="18"></line><line x1="6" y1="18" x2="18" y2="6"></line>
                            </svg>
                        </button>
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
