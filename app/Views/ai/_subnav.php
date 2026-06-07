<?php /** @var string $active */ use App\Support\Auth; ?>
<div class="subnav">
    <a href="<?= e(url('/ai/analysis')) ?>" class="<?= $active === 'analysis' ? 'active' : '' ?>"><?= e(__('ai.subnav.analysis')) ?></a>
    <a href="<?= e(url('/ai/categorize')) ?>" class="<?= $active === 'categorize' ? 'active' : '' ?>"><?= e(__('ai.subnav.categorize')) ?></a>
    <a href="<?= e(url('/ai/chat')) ?>" class="<?= $active === 'chat' ? 'active' : '' ?>"><?= e(__('ai.subnav.chat')) ?></a>
    <?php if (Auth::isOwner()): ?>
        <a href="<?= e(url('/ai/settings')) ?>" class="<?= $active === 'settings' ? 'active' : '' ?>"><?= e(__('ai.subnav.settings')) ?></a>
    <?php endif; ?>
</div>
<p class="muted" style="font-size:.8rem"><?= e(__('ai.privacy_note')) ?></p>
