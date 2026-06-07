<?php
/** @var bool $enabled */
/** @var int $pending */
/** @var array<int,array<string,mixed>>|null $suggestions */
/** @var array<int,string> $categories */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'categorize';
?>
<h1 class="card__title" style="margin-bottom:.5rem"><?= e(__('ai.cat_title')) ?></h1>
<?php require BASE_PATH . '/app/Views/ai/_subnav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<?php if (!$enabled): ?>
    <div class="card"><p class="muted"><?= e(__('ai.not_configured')) ?></p>
        <a class="btn" href="<?= e(url('/ai/settings')) ?>"><?= e(__('ai.go_settings')) ?></a></div>
<?php else: ?>
    <section class="card">
        <p><strong><?= e((string) $pending) ?></strong> <?= e(__('ai.pending')) ?></p>
        <form method="post" action="<?= e(url('/ai/categorize/suggest')) ?>">
            <?= csrf_field() ?>
            <button class="btn" type="submit" <?= $pending === 0 ? 'disabled' : '' ?>><?= e(__('ai.suggest')) ?></button>
        </form>
    </section>

    <?php if (!empty($suggestions)): ?>
        <section class="card" style="margin-top:1rem">
            <h2 class="card__subtitle"><?= e(__('ai.review')) ?></h2>
            <form method="post" action="<?= e(url('/ai/categorize/apply')) ?>">
                <?= csrf_field() ?>
                <table class="table">
                    <thead><tr><th><?= e(__('ai.col_apply')) ?></th><th><?= e(__('ai.col_tx')) ?></th>
                        <th style="text-align:right"><?= e(__('tx.amount')) ?></th><th><?= e(__('ai.col_category')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($suggestions as $s): $amt = (float) $s['amount']; ?>
                        <tr>
                            <td><input type="checkbox" name="apply[]" value="<?= e((string) $s['id']) ?>" checked style="width:auto"></td>
                            <td><?= e($s['description'] ?: '—') ?></td>
                            <td style="text-align:right" class="<?= $amt < 0 ? 'neg' : 'pos' ?>"><?= e(money($amt)) ?></td>
                            <td>
                                <select name="cat[<?= e((string) $s['id']) ?>]">
                                    <?php foreach ($categories as $cid => $label): ?>
                                        <option value="<?= e((string) $cid) ?>" <?= (int) $s['category_id'] === (int) $cid ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <button class="btn" type="submit"><?= e(__('ai.apply')) ?></button>
            </form>
        </section>
    <?php endif; ?>
<?php endif; ?>
