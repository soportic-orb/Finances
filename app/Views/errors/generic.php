<?php
/** @var int $code */
/** @var string $message */
?>
<section class="card">
    <h1 class="card__title"><?= e((string) $code) ?></h1>
    <p class="muted"><?= e($message !== '' ? $message : __('error.generic')) ?></p>
    <a class="btn" href="<?= e(url('/')) ?>"><?= e(__('common.back_home')) ?></a>
</section>
