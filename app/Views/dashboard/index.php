<?php
/** @var array<string,mixed>|null $household */
/** @var int $memberCount */
/** @var array<int,array<string,mixed>> $recent */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('nav.dashboard')) ?></h1>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e($household['name'] ?? '') ?></h2>
        <dl class="meta">
            <dt><?= e(__('dash.currency')) ?></dt><dd><?= e($household['base_currency'] ?? 'EUR') ?></dd>
            <dt><?= e(__('dash.members')) ?></dt><dd><?= e((string) $memberCount) ?></dd>
            <dt><?= e(__('dash.timezone')) ?></dt><dd><?= e($household['timezone'] ?? '') ?></dd>
        </dl>
        <p class="muted"><?= e(__('dash.next_phase')) ?></p>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('dash.recent_activity')) ?></h2>
        <?php if ($recent === []): ?>
            <p class="muted"><?= e(__('dash.no_activity')) ?></p>
        <?php else: ?>
            <ul class="loglist">
                <?php foreach ($recent as $r): ?>
                    <li>
                        <span class="badge"><?= e($r['action']) ?></span>
                        <span class="muted"><?= e($r['user_name'] ?? '—') ?></span>
                        <time class="muted"><?= e($r['created_at']) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
