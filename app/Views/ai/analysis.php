<?php
/** @var bool $enabled */
/** @var array<string,mixed>|null $insight */
/** @var array<int,string> $recs */
/** @var array<int,string> $anomalies */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'analysis';
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('ai.analysis_title')) ?></h1>
    <?php if ($enabled): ?>
        <form method="post" action="<?= e(url('/ai/analysis/generate')) ?>">
            <?= csrf_field() ?><button class="btn" type="submit"><?= e(__('ai.generate')) ?></button>
        </form>
    <?php endif; ?>
</div>
<?php require BASE_PATH . '/app/Views/ai/_subnav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<?php if (!$enabled): ?>
    <div class="card"><p class="muted"><?= e(__('ai.not_configured')) ?></p>
        <a class="btn" href="<?= e(url('/ai/settings')) ?>"><?= e(__('ai.go_settings')) ?></a></div>
<?php elseif ($insight === null): ?>
    <div class="card"><p class="muted"><?= e(__('ai.no_insight')) ?></p></div>
<?php else: ?>
    <section class="card">
        <p class="muted"><?= e(__('ai.generated_on')) ?> <?= e($insight['created_at']) ?> · <?= e($insight['period']) ?></p>
        <h2 class="card__subtitle"><?= e(__('ai.summary')) ?></h2>
        <p><?= nl2br(e($insight['summary'])) ?></p>

        <?php if ($recs !== []): ?>
            <h2 class="card__subtitle"><?= e(__('ai.recommendations')) ?></h2>
            <ul><?php foreach ($recs as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>

        <?php if ($anomalies !== []): ?>
            <h2 class="card__subtitle"><?= e(__('ai.anomalies')) ?></h2>
            <ul><?php foreach ($anomalies as $a): ?><li class="neg"><?= e($a) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>
<?php endif; ?>
