<?php
/** @var ?string $error */
?>
<h1 class="card__title"><?= e(__('auth.2fa_title')) ?></h1>
<p class="muted"><?= e(__('auth.2fa_help')) ?></p>

<?php if (!empty($error)): ?>
    <div class="alert alert--err"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/2fa')) ?>">
    <?= csrf_field() ?>
    <label><?= e(__('auth.2fa_code')) ?></label>
    <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
           autocomplete="one-time-code" required autofocus>
    <button class="btn" type="submit"><?= e(__('auth.2fa_verify')) ?></button>
</form>
