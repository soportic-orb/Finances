<?php
/** @var string $secret */
/** @var string $uri */
/** @var ?string $error */
?>
<h1 class="card__title"><?= e(__('settings.2fa_setup_title')) ?></h1>
<p class="muted"><?= e(__('settings.2fa_setup_help')) ?></p>

<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="card" style="background:var(--surface-2);margin:1rem 0">
    <p class="muted"><?= e(__('settings.2fa_manual_key')) ?></p>
    <code class="codeblock"><?= e(chunk_split($secret, 4, ' ')) ?></code>
    <p class="muted" style="margin-top:.75rem"><?= e(__('settings.2fa_uri')) ?></p>
    <code class="codeblock codeblock--sm"><?= e($uri) ?></code>
</div>

<form method="post" action="<?= e(url('/settings/2fa/enable')) ?>">
    <?= csrf_field() ?>
    <label><?= e(__('settings.2fa_enter_code')) ?></label>
    <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
    <div style="margin-top:1rem">
        <button class="btn" type="submit"><?= e(__('settings.2fa_activate')) ?></button>
        <a class="btn btn--ghost" href="<?= e(url('/settings')) ?>"><?= e(__('common.cancel')) ?></a>
    </div>
</form>
