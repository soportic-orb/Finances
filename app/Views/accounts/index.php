<?php
/** @var array<int,array<string,mixed>> $accounts */
/** @var float $netWorth */
/** @var array<int,array<string,mixed>> $members */
/** @var array<int,string> $types */
/** @var ?string $ok */
/** @var ?string $error */
use App\Support\Auth;

$active = array_filter($accounts, static fn ($a) => (int) $a['archived'] === 0);
$archived = array_filter($accounts, static fn ($a) => (int) $a['archived'] === 1);
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('acc.title')) ?></h1>
    <div class="networth">
        <span class="muted"><?= e(__('acc.net_worth')) ?></span>
        <strong class="<?= $netWorth < 0 ? 'neg' : 'pos' ?>"><?= e(money($netWorth)) ?></strong>
    </div>
</div>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('acc.title')) ?></h2>
        <?php if ($active === []): ?>
            <p class="muted"><?= e(__('acc.none')) ?></p>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th><?= e(__('acc.name')) ?></th><th><?= e(__('acc.type')) ?></th>
                    <th><?= e(__('acc.owner')) ?></th><th style="text-align:right"><?= e(__('acc.balance')) ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($active as $a): ?>
                    <tr>
                        <td><?= e($a['name']) ?><?php if ($a['iban_last4']): ?> <span class="muted">··<?= e($a['iban_last4']) ?></span><?php endif; ?></td>
                        <td><span class="badge"><?= e(__('acc.type.' . $a['type'])) ?></span></td>
                        <td class="muted"><?= e($a['owner_name'] ?? __('acc.unassigned')) ?></td>
                        <td style="text-align:right" class="<?= (float) $a['current_balance'] < 0 ? 'neg' : '' ?>">
                            <?= e(money((float) $a['current_balance'], $a['currency'])) ?>
                        </td>
                        <td class="rowactions">
                            <a class="linkbtn" href="<?= e(url('/accounts/' . $a['id'] . '/edit')) ?>"><?= e(__('acc.edit')) ?></a>
                            <form method="post" action="<?= e(url('/accounts/' . $a['id'] . '/archive')) ?>">
                                <?= csrf_field() ?><button class="linkbtn" type="submit"><?= e(__('acc.archive')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($archived !== []): ?>
            <h3 style="margin:1.25rem 0 .5rem"><?= e(__('acc.archived')) ?></h3>
            <table class="table">
                <tbody>
                <?php foreach ($archived as $a): ?>
                    <tr class="muted">
                        <td><?= e($a['name']) ?></td>
                        <td style="text-align:right"><?= e(money((float) $a['current_balance'], $a['currency'])) ?></td>
                        <td class="rowactions">
                            <form method="post" action="<?= e(url('/accounts/' . $a['id'] . '/archive')) ?>">
                                <?= csrf_field() ?><button class="linkbtn" type="submit"><?= e(__('acc.unarchive')) ?></button>
                            </form>
                            <form method="post" action="<?= e(url('/accounts/' . $a['id'] . '/delete')) ?>"
                                  onsubmit="return confirm('<?= e(__('acc.confirm_delete')) ?>')">
                                <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('acc.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('acc.add')) ?></h2>
        <form method="post" action="<?= e(url('/accounts')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('acc.name')) ?></label>
            <input name="name" required>
            <div class="row">
                <div>
                    <label><?= e(__('acc.type')) ?></label>
                    <select name="type">
                        <?php foreach ($types as $t): ?>
                            <option value="<?= e($t) ?>"><?= e(__('acc.type.' . $t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?= e(__('acc.currency')) ?></label>
                    <select name="currency">
                        <?php foreach (['EUR','USD','GBP','CHF'] as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div>
                    <label><?= e(__('acc.opening')) ?></label>
                    <input name="opening_balance" value="0" inputmode="decimal">
                </div>
                <div>
                    <label><?= e(__('acc.iban_last4')) ?></label>
                    <input name="iban_last4" maxlength="4" inputmode="numeric">
                </div>
            </div>
            <?php if (Auth::isOwner()): ?>
                <label><?= e(__('acc.owner')) ?></label>
                <select name="owner_user_id">
                    <option value=""><?= e(__('acc.unassigned')) ?></option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= e((string) $m['id']) ?>" <?= (int) $m['id'] === Auth::id() ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button class="btn" type="submit"><?= e(__('acc.add')) ?></button>
        </form>
    </section>
</div>
