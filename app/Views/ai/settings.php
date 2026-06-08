<?php
/** @var bool $configured */
/** @var array<string,string> $models */
/** @var array<string,bool> $features */
/** @var int $tokenLimit */
/** @var int $tokensUsed */
/** @var array<int,array<string,mixed>> $recent */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'settings';
?>
<h1 class="card__title" style="margin-bottom:.5rem"><?= e(__('ai.title')) ?></h1>
<?php require BASE_PATH . '/app/Views/ai/_subnav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('ai.subnav.settings')) ?></h2>
        <form method="post" action="<?= e(url('/ai/settings')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('ai.api_key')) ?>
                <?php if ($configured): ?><span class="badge badge--ok"><?= e(__('ai.api_key_set')) ?></span><?php endif; ?>
            </label>
            <input type="password" name="api_key" placeholder="<?= e(__('ai.api_key_ph')) ?>" autocomplete="off">
            <?php if ($configured): ?>
                <label style="margin-top:.5rem"><input type="checkbox" name="remove_key" value="1" style="width:auto"> <?= e(__('ai.remove_key')) ?></label>
            <?php endif; ?>

            <h3 style="margin:1rem 0 .25rem"><?= e(__('ai.features')) ?></h3>
            <?php foreach ($features as $f => $on): ?>
                <label><input type="checkbox" name="enable_<?= e($f) ?>" value="1" style="width:auto" <?= $on ? 'checked' : '' ?>> <?= e(__('ai.feat.' . $f)) ?></label>
            <?php endforeach; ?>

            <h3 style="margin:1rem 0 .25rem"><?= e(__('ai.models')) ?></h3>
            <?php foreach ($models as $task => $m): ?>
                <label><?= e(__('ai.task.' . $task)) ?></label>
                <input name="model_<?= e($task) ?>" value="<?= e($m) ?>">
            <?php endforeach; ?>

            <label style="margin-top:.75rem"><?= e(__('ai.token_limit')) ?></label>
            <input type="number" name="token_limit" value="<?= e((string) $tokenLimit) ?>" min="0">

            <button class="btn" type="submit" style="margin-top:1rem"><?= e(__('common.save')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('ai.usage')) ?></h2>
        <p class="muted"><?= e(__('ai.tokens_used')) ?>: <strong><?= e(number_format($tokensUsed, 0, ',', '.')) ?></strong></p>
        <?php if ($recent === []): ?>
            <p class="muted">—</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>tipus</th><th>model</th><th>in/out</th><th>estat</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $j): ?>
                    <tr>
                        <td><?= e($j['type']) ?></td>
                        <td class="muted" style="font-size:.8rem"><?= e($j['model']) ?></td>
                        <td class="muted"><?= e($j['tokens_in']) ?>/<?= e($j['tokens_out']) ?></td>
                        <td><span class="badge <?= $j['status'] === 'ok' ? 'badge--ok' : '' ?>"><?= e($j['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
