<?php
/** @var array<int,array<string,mixed>> $goals */
/** @var array<int,string> $accounts */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'goals';

/** Mesos sencers (mínim 1) fins a una data. */
$monthsTo = static function (?string $date): int {
    if (!$date) {
        return 0;
    }
    $diff = (strtotime($date) - time()) / (86400 * 30.4);
    return max(1, (int) ceil($diff));
};
?>
<h1 class="card__title" style="margin-bottom:.5rem"><?= e(__('goal.title')) ?></h1>
<?php require BASE_PATH . '/app/Views/_plannav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('goal.title')) ?></h2>
        <?php if ($goals === []): ?>
            <p class="muted"><?= e(__('goal.none')) ?></p>
        <?php else: ?>
            <?php foreach ($goals as $g):
                $current = $g['account_id'] ? (float) $g['current_balance'] : (float) $g['current_amount'];
                $target = (float) $g['target_amount'];
                $pct = $target > 0 ? round($current / $target * 100, 1) : 0;
                $remaining = max(0, $target - $current);
                $months = $monthsTo($g['target_date']);
                $monthly = $months > 0 && $remaining > 0 ? $remaining / $months : 0;
                $reached = $current >= $target;
            ?>
                <div class="budget">
                    <div class="budget__head">
                        <span><strong><?= e($g['name']) ?></strong>
                            <?php if ($g['account_name']): ?><span class="badge"><?= e($g['account_name']) ?></span><?php endif; ?>
                        </span>
                        <span class="rowactions">
                            <form method="post" action="<?= e(url('/goals/' . $g['id'] . '/delete')) ?>" onsubmit="return confirm('<?= e(__('common.confirm_delete')) ?>')">
                                <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('goal.delete')) ?></button>
                            </form>
                        </span>
                    </div>
                    <div class="bar"><div class="bar__fill <?= $reached ? 'bar__fill--ok2' : '' ?>" style="width:<?= e((string) min(100, $pct)) ?>%"></div></div>
                    <div class="budget__meta muted">
                        <?= e(money($current)) ?> / <?= e(money($target)) ?> (<?= e((string) $pct) ?>%)
                        · <?= e($g['target_date'] ?? __('goal.no_date')) ?>
                        <?php if ($reached): ?><span class="pos"> · <?= e(__('goal.reached')) ?></span>
                        <?php elseif ($monthly > 0): ?> · <?= e(__('goal.monthly')) ?>: <strong><?= e(money($monthly)) ?></strong><?php endif; ?>
                    </div>
                    <?php if (!$reached): ?>
                        <form method="post" action="<?= e(url('/goals/' . $g['id'] . '/contribute')) ?>" class="inline-add">
                            <?= csrf_field() ?>
                            <input name="amount" inputmode="decimal" placeholder="0,00">
                            <button class="btn btn--ghost" type="submit"><?= e(__('goal.contribute')) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('goal.add')) ?></h2>
        <form method="post" action="<?= e(url('/goals')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('goal.name')) ?></label>
            <input name="name" required>
            <div class="row">
                <div><label><?= e(__('goal.target')) ?></label><input name="target_amount" inputmode="decimal" required></div>
                <div><label><?= e(__('goal.current')) ?></label><input name="current_amount" inputmode="decimal" value="0"></div>
            </div>
            <label><?= e(__('goal.target_date')) ?></label>
            <input type="date" name="target_date">
            <label><?= e(__('goal.account')) ?></label>
            <select name="account_id">
                <option value=""><?= e(__('goal.no_account')) ?></option>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <button class="btn" type="submit"><?= e(__('goal.add')) ?></button>
        </form>
    </section>
</div>
