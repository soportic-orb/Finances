<?php
/** @var ?string $error */
?>
<h1 class="card__title"><?= e(__('auth.login_title')) ?></h1>

<?php if (!empty($error)): ?>
    <div class="alert alert--err"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <label><?= e(__('auth.email')) ?></label>
    <input type="email" name="email" autocomplete="username" required autofocus>
    <label><?= e(__('auth.password')) ?></label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn" type="submit"><?= e(__('auth.login_btn')) ?></button>
</form>
