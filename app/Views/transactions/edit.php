<?php
/** @var array<string,mixed> $tx */
/** @var array<int,string> $categories */
$isTransfer = !empty($tx['transfer_group_id']);
$absAmount = number_format(abs((float) $tx['amount']), 2, '.', '');
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('tx.edit')) ?></h1>

<section class="card" style="max-width:520px">
    <?php if ($isTransfer): ?>
        <div class="alert"><?= e(__('tx.edit_note_transfer')) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('/transactions/' . $tx['id'] . '/edit')) ?>">
        <?= csrf_field() ?>
        <?php if (!$isTransfer): ?>
            <label><?= e(__('tx.type')) ?></label>
            <select name="type">
                <option value="expense" <?= $tx['type'] === 'expense' ? 'selected' : '' ?>><?= e(__('tx.expense')) ?></option>
                <option value="income" <?= $tx['type'] === 'income' ? 'selected' : '' ?>><?= e(__('tx.income')) ?></option>
            </select>
        <?php endif; ?>
        <div class="row">
            <div>
                <label><?= e(__('tx.amount')) ?></label>
                <input name="amount" value="<?= e($absAmount) ?>" inputmode="decimal" required>
            </div>
            <div>
                <label><?= e(__('tx.date')) ?></label>
                <input type="date" name="occurred_on" value="<?= e($tx['occurred_on']) ?>" required>
            </div>
        </div>
        <label><?= e(__('tx.category')) ?></label>
        <select name="category_id">
            <option value=""><?= e(__('tx.uncategorized')) ?></option>
            <?php foreach ($categories as $id => $label): ?>
                <option value="<?= e((string) $id) ?>" <?= (int) ($tx['category_id'] ?? 0) === $id ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label><?= e(__('tx.merchant')) ?></label>
        <input name="merchant" value="<?= e($tx['merchant'] ?? '') ?>">
        <label><?= e(__('tx.description')) ?></label>
        <input name="description" value="<?= e($tx['description'] ?? '') ?>">
        <label><?= e(__('tx.notes')) ?></label>
        <input name="notes" value="<?= e($tx['notes'] ?? '') ?>">
        <div style="margin-top:1rem">
            <button class="btn" type="submit"><?= e(__('common.save')) ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/transactions')) ?>"><?= e(__('common.cancel')) ?></a>
        </div>
    </form>
</section>
