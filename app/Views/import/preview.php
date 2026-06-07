<?php
/** @var array<string,mixed>|null $account */
/** @var string $source */
/** @var array<int,array<string,mixed>> $movements */
/** @var int $total */
/** @var int $newCount */
/** @var int $dupCount */
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('imp.preview_title')) ?> · <?= e($source) ?></h1>
    <span class="muted"><?= e($account['name'] ?? '') ?></span>
</div>

<?php if ($total === 0): ?>
    <div class="card">
        <p class="muted"><?= e(__('imp.none_parsed')) ?></p>
        <a class="btn btn--ghost" href="<?= e(url('/import')) ?>"><?= e(__('imp.back')) ?></a>
    </div>
<?php else: ?>
    <div class="alert"><?= e(__('imp.summary', ['total' => $total, 'new' => $newCount, 'dup' => $dupCount])) ?></div>

    <section class="card">
        <table class="table">
            <thead><tr>
                <th><?= e(__('tx.date')) ?></th><th><?= e(__('tx.description')) ?></th>
                <th style="text-align:right"><?= e(__('tx.amount')) ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($movements as $m): $amt = (float) $m['amount']; ?>
                <tr>
                    <td class="muted"><?= e((string) ($m['occurred_on'] ?? '—')) ?></td>
                    <td><?= e($m['description'] ?? ($m['merchant'] ?? '—')) ?></td>
                    <td style="text-align:right" class="<?= $amt < 0 ? 'neg' : 'pos' ?>"><?= e(money($amt, $account['currency'] ?? 'EUR')) ?></td>
                    <td>
                        <?php
                        $isDup = \App\Models\Transaction::existsSimilar((int) $account['id'], (string) $m['occurred_on'], $amt);
                        ?>
                        <span class="badge <?= $isDup ? '' : 'badge--ok' ?>"><?= e($isDup ? __('imp.dup_badge') : __('imp.new_badge')) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($total > count($movements)): ?>
            <p class="muted">… (<?= e((string) $total) ?> en total)</p>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/import/confirm')) ?>" style="margin-top:1rem">
            <?= csrf_field() ?>
            <button class="btn" type="submit"><?= e(__('imp.confirm')) ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/import')) ?>"><?= e(__('imp.back')) ?></a>
        </form>
    </section>
<?php endif; ?>
