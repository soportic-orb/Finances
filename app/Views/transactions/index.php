<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var int $total */
/** @var float $sum */
/** @var int $page */
/** @var int $pages */
/** @var array<string,mixed> $filters */
/** @var array<int,string> $accounts */
/** @var array<int,string> $categories */
/** @var array<int,array<string,mixed>> $members */
/** @var ?string $ok */
/** @var ?string $error */

$today = date('Y-m-d');
$qsBase = array_filter([
    'account' => $filters['account'], 'category' => $filters['category'], 'member' => $filters['member'],
    'type' => $filters['type'], 'q' => $filters['q'], 'from' => $filters['from'], 'to' => $filters['to'],
    'min' => $filters['min'], 'max' => $filters['max'],
], static fn ($v) => $v !== null && $v !== '');
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('tx.title')) ?></h1>
    <div class="networth">
        <span class="muted"><?= e($total) ?> <?= e(__('tx.results')) ?> · <?= e(__('tx.total')) ?></span>
        <strong class="<?= $sum < 0 ? 'neg' : 'pos' ?>"><?= e(money($sum)) ?></strong>
    </div>
</div>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<section class="card" style="margin-bottom:1rem">
    <form method="get" action="<?= e(url('/transactions')) ?>" class="filters">
        <select name="account">
            <option value=""><?= e(__('tx.account')) ?></option>
            <?php foreach ($accounts as $id => $name): ?>
                <option value="<?= e((string) $id) ?>" <?= (int) ($filters['account'] ?? 0) === $id ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="category">
            <option value=""><?= e(__('tx.category')) ?></option>
            <?php foreach ($categories as $id => $label): ?>
                <option value="<?= e((string) $id) ?>" <?= (int) ($filters['category'] ?? 0) === $id ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="member">
            <option value=""><?= e(__('tx.member')) ?></option>
            <?php foreach ($members as $m): ?>
                <option value="<?= e((string) $m['id']) ?>" <?= (int) ($filters['member'] ?? 0) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type">
            <option value=""><?= e(__('tx.type')) ?></option>
            <option value="income" <?= ($filters['type'] ?? '') === 'income' ? 'selected' : '' ?>><?= e(__('tx.income')) ?></option>
            <option value="expense" <?= ($filters['type'] ?? '') === 'expense' ? 'selected' : '' ?>><?= e(__('tx.expense')) ?></option>
            <option value="transfer" <?= ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' ?>><?= e(__('tx.transfer')) ?></option>
        </select>
        <input type="date" name="from" value="<?= e((string) ($filters['from'] ?? '')) ?>">
        <input type="date" name="to" value="<?= e((string) ($filters['to'] ?? '')) ?>">
        <input type="text" name="q" placeholder="<?= e(__('tx.search')) ?>" value="<?= e((string) ($filters['q'] ?? '')) ?>">
        <input type="text" name="min" placeholder="<?= e(__('tx.amount_min')) ?>" value="<?= e((string) ($filters['min'] ?? '')) ?>" inputmode="decimal">
        <input type="text" name="max" placeholder="<?= e(__('tx.amount_max')) ?>" value="<?= e((string) ($filters['max'] ?? '')) ?>" inputmode="decimal">
        <button class="btn" type="submit"><?= e(__('tx.filter')) ?></button>
        <a class="btn btn--ghost" href="<?= e(url('/transactions')) ?>"><?= e(__('tx.clear')) ?></a>
        <a class="btn btn--ghost" href="<?= e(url('/export/transactions.csv?' . http_build_query($qsBase))) ?>"><?= e(__('export.csv')) ?></a>
        <a class="btn btn--ghost" href="<?= e(url('/export/transactions.xls?' . http_build_query($qsBase))) ?>"><?= e(__('export.xls')) ?></a>
    </form>
</section>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('tx.add')) ?></h2>
        <form method="post" action="<?= e(url('/transactions')) ?>">
            <?= csrf_field() ?>
            <div class="row">
                <div>
                    <label><?= e(__('tx.type')) ?></label>
                    <select name="type">
                        <option value="expense"><?= e(__('tx.expense')) ?></option>
                        <option value="income"><?= e(__('tx.income')) ?></option>
                    </select>
                </div>
                <div>
                    <label><?= e(__('tx.amount')) ?></label>
                    <input name="amount" inputmode="decimal" required>
                </div>
            </div>
            <label><?= e(__('tx.account')) ?></label>
            <select name="account_id" required>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <label><?= e(__('tx.category')) ?></label>
            <select name="category_id">
                <option value=""><?= e(__('tx.uncategorized')) ?></option>
                <?php foreach ($categories as $id => $label): ?><option value="<?= e((string) $id) ?>"><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <div class="row">
                <div>
                    <label><?= e(__('tx.date')) ?></label>
                    <input type="date" name="occurred_on" value="<?= e($today) ?>" required>
                </div>
                <div>
                    <label><?= e(__('tx.merchant')) ?></label>
                    <input name="merchant">
                </div>
            </div>
            <label><?= e(__('tx.description')) ?></label>
            <input name="description">
            <button class="btn" type="submit"><?= e(__('tx.add')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('tx.new_transfer')) ?></h2>
        <form method="post" action="<?= e(url('/transfers')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('tx.from')) ?></label>
            <select name="from_account" required>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <label><?= e(__('tx.to')) ?></label>
            <select name="to_account" required>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <div class="row">
                <div>
                    <label><?= e(__('tx.amount')) ?></label>
                    <input name="amount" inputmode="decimal" required>
                </div>
                <div>
                    <label><?= e(__('tx.date')) ?></label>
                    <input type="date" name="occurred_on" value="<?= e($today) ?>" required>
                </div>
            </div>
            <label><?= e(__('tx.description')) ?></label>
            <input name="description">
            <button class="btn" type="submit"><?= e(__('tx.transfer')) ?></button>
        </form>
    </section>
</div>

<section class="card" style="margin-top:1rem">
    <?php if ($rows === []): ?>
        <p class="muted"><?= e(__('tx.none')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr>
                <th><?= e(__('tx.date')) ?></th><th><?= e(__('tx.description')) ?></th>
                <th><?= e(__('tx.account')) ?></th><th><?= e(__('tx.category')) ?></th>
                <th style="text-align:right"><?= e(__('tx.amount')) ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $t): $amt = (float) $t['amount']; ?>
                <tr>
                    <td class="muted"><?= e($t['occurred_on']) ?></td>
                    <td>
                        <?= e($t['description'] ?? ($t['merchant'] ?? '—')) ?>
                        <?php if (!empty($t['transfer_group_id'])): ?><span class="badge">⇄</span><?php endif; ?>
                    </td>
                    <td class="muted"><?= e($t['account_name']) ?></td>
                    <td class="muted"><?= e($t['category_name'] ?? __('tx.uncategorized')) ?></td>
                    <td style="text-align:right" class="<?= $amt < 0 ? 'neg' : 'pos' ?>">
                        <?= e(money($amt, $t['account_currency'])) ?>
                    </td>
                    <td class="rowactions">
                        <a class="linkbtn" href="<?= e(url('/transactions/' . $t['id'] . '/edit')) ?>"><?= e(__('tx.edit')) ?></a>
                        <form method="post" action="<?= e(url('/transactions/' . $t['id'] . '/delete')) ?>"
                              onsubmit="return confirm('<?= e(__('tx.confirm_delete')) ?>')">
                            <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('tx.delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a class="btn btn--ghost" href="?<?= e(http_build_query($qsBase + ['page' => $page - 1])) ?>"><?= e(__('common.prev')) ?></a>
                <?php endif; ?>
                <span class="muted"><?= e((string) $page) ?> / <?= e((string) $pages) ?></span>
                <?php if ($page < $pages): ?>
                    <a class="btn btn--ghost" href="?<?= e(http_build_query($qsBase + ['page' => $page + 1])) ?>"><?= e(__('common.next')) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
