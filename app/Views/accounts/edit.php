<?php
/** @var array<string,mixed> $account */
/** @var array<int,array<string,mixed>> $members */
/** @var array<int,string> $types */
use App\Support\Auth;
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('acc.edit')) ?></h1>

<section class="card" style="max-width:520px">
    <form method="post" action="<?= e(url('/accounts/' . $account['id'] . '/edit')) ?>">
        <?= csrf_field() ?>
        <label><?= e(__('acc.name')) ?></label>
        <input name="name" value="<?= e($account['name']) ?>" required>
        <div class="row">
            <div>
                <label><?= e(__('acc.type')) ?></label>
                <select name="type">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>" <?= $account['type'] === $t ? 'selected' : '' ?>><?= e(__('acc.type.' . $t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= e(__('acc.currency')) ?></label>
                <select name="currency">
                    <?php foreach (['EUR','USD','GBP','CHF'] as $c): ?>
                        <option <?= $account['currency'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div>
                <label><?= e(__('acc.opening')) ?></label>
                <input name="opening_balance" value="<?= e(number_format((float) $account['opening_balance'], 2, '.', '')) ?>" inputmode="decimal">
            </div>
            <div>
                <label><?= e(__('acc.iban_last4')) ?></label>
                <input name="iban_last4" maxlength="4" value="<?= e($account['iban_last4'] ?? '') ?>">
            </div>
        </div>
        <?php if (Auth::isOwner()): ?>
            <label><?= e(__('acc.owner')) ?></label>
            <select name="owner_user_id">
                <option value=""><?= e(__('acc.unassigned')) ?></option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= e((string) $m['id']) ?>" <?= (int) $account['owner_user_id'] === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <div style="margin-top:1rem">
            <button class="btn" type="submit"><?= e(__('common.save')) ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/accounts')) ?>"><?= e(__('common.cancel')) ?></a>
        </div>
    </form>
</section>
