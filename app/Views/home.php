<?php
/** @var string $version */
/** @var bool $installed */
?>
<section class="card" x-data="{ ping: null }">
    <h1 class="card__title"><?= e(__('home.title')) ?></h1>
    <p class="muted"><?= e(__('home.scaffold')) ?></p>

    <div class="status status--ok">
        <span class="dot"></span>
        <?= e(__('home.status_ok')) ?>
    </div>

    <dl class="meta">
        <dt><?= e(__('home.version')) ?></dt>
        <dd><?= e($version) ?></dd>
        <dt><?= e(__('home.phase')) ?></dt>
        <dd>1 — Bastida</dd>
    </dl>

    <?php if (!$installed): ?>
        <p class="notice"><?= e(__('home.not_installed')) ?></p>
    <?php endif; ?>

    <button class="btn" @click="fetch('<?= e(url('/health')) ?>').then(r => r.json()).then(d => ping = d.status)">
        health-check
    </button>
    <span class="muted" x-show="ping" x-text="ping"></span>
</section>
