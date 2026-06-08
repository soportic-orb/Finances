<?php
/** @var array<string,mixed>|null $household */
/** @var array<string,mixed>|null $user */
/** @var bool $has2fa */
/** @var bool $isOwner */
/** @var ?string $ok */
/** @var ?string $error */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('nav.settings')) ?></h1>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('settings.household')) ?></h2>
        <?php if ($isOwner): ?>
            <form method="post" action="<?= e(url('/settings/household')) ?>">
                <?= csrf_field() ?>
                <label><?= e(__('settings.household_name')) ?></label>
                <input name="name" value="<?= e($household['name'] ?? '') ?>" required>
                <div class="row">
                    <div>
                        <label><?= e(__('settings.currency')) ?></label>
                        <select name="currency">
                            <?php foreach (['EUR','USD','GBP','CHF'] as $c): ?>
                                <option value="<?= $c ?>" <?= ($household['base_currency'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label><?= e(__('settings.locale')) ?></label>
                        <select name="locale">
                            <option value="ca" <?= ($household['locale'] ?? '') === 'ca' ? 'selected' : '' ?>>Català</option>
                            <option value="es" <?= ($household['locale'] ?? '') === 'es' ? 'selected' : '' ?>>Castellà</option>
                        </select>
                    </div>
                </div>
                <label><?= e(__('settings.timezone')) ?></label>
                <input name="timezone" value="<?= e($household['timezone'] ?? '') ?>" required>
                <button class="btn" type="submit"><?= e(__('common.save')) ?></button>
            </form>
        <?php else: ?>
            <p class="muted"><?= e(__('settings.owner_only')) ?></p>
            <dl class="meta">
                <dt><?= e(__('settings.household_name')) ?></dt><dd><?= e($household['name'] ?? '') ?></dd>
                <dt><?= e(__('settings.currency')) ?></dt><dd><?= e($household['base_currency'] ?? '') ?></dd>
            </dl>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('settings.security')) ?></h2>
        <p class="muted"><?= e(__('settings.account')) ?>: <strong><?= e($user['email'] ?? '') ?></strong></p>

        <h3 style="margin:1rem 0 .25rem"><?= e(__('settings.2fa')) ?></h3>
        <?php if ($has2fa): ?>
            <p class="status status--ok"><span class="dot"></span><?= e(__('settings.2fa_on')) ?></p>
            <form method="post" action="<?= e(url('/settings/2fa/disable')) ?>">
                <?= csrf_field() ?>
                <label><?= e(__('settings.confirm_password')) ?></label>
                <input type="password" name="password" required>
                <button class="btn btn--ghost" type="submit"><?= e(__('settings.2fa_disable')) ?></button>
            </form>
        <?php else: ?>
            <p class="muted"><?= e(__('settings.2fa_off')) ?></p>
            <a class="btn" href="<?= e(url('/settings/2fa')) ?>"><?= e(__('settings.2fa_enable')) ?></a>
        <?php endif; ?>
    </section>
</div>

<section class="card" style="margin-top:1rem">
    <h2 class="card__subtitle"><?= e(__('privacy.title')) ?></h2>
    <p class="muted"><?= e(__('privacy.body')) ?></p>
</section>
