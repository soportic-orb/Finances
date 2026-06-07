<?php
/** @var array<int,array{budget:array<string,mixed>,progress:array<string,mixed>}> $rows */
/** @var array<int,string> $categories */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'budgets';
?>
<h1 class="card__title" style="margin-bottom:.5rem"><?= e(__('bud.title')) ?></h1>
<?php require BASE_PATH . '/app/Views/_plannav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('bud.title')) ?></h2>
        <?php if ($rows === []): ?>
            <p class="muted"><?= e(__('bud.none')) ?></p>
        <?php else: ?>
            <?php foreach ($rows as $r): $b = $r['budget']; $p = $r['progress']; ?>
                <div class="budget">
                    <div class="budget__head">
                        <span><strong><?= e($b['category_name']) ?></strong>
                            <span class="badge"><?= e(__('bud.period.' . $b['period'])) ?></span>
                            <?php if ((int) $b['rollover'] === 1 && $p['rollover_in'] > 0): ?>
                                <span class="badge">+<?= e(money($p['rollover_in'])) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="rowactions">
                            <form method="post" action="<?= e(url('/budgets/' . $b['id'] . '/delete')) ?>" onsubmit="return confirm('?')">
                                <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('bud.delete')) ?></button>
                            </form>
                        </span>
                    </div>
                    <div class="bar"><div class="bar__fill bar__fill--<?= e($p['status']) ?>" style="width:<?= e((string) min(100, $p['pct'])) ?>%"></div></div>
                    <div class="budget__meta muted">
                        <?= e(money($p['spent'])) ?> / <?= e(money($p['amount'])) ?> (<?= e((string) $p['pct']) ?>%)
                        <?php if ($p['status'] === 'over'): ?><span class="neg"> · <?= e(__('bud.over')) ?></span>
                        <?php elseif ($p['status'] === 'warn'): ?><span style="color:#f59e0b"> · <?= e(__('bud.warn')) ?></span><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('bud.add')) ?></h2>
        <form method="post" action="<?= e(url('/budgets')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('bud.category')) ?></label>
            <select name="category_id" required>
                <?php foreach ($categories as $id => $label): ?><option value="<?= e((string) $id) ?>"><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <div class="row">
                <div>
                    <label><?= e(__('bud.period')) ?></label>
                    <select name="period"><option value="mensual"><?= e(__('bud.period.mensual')) ?></option><option value="anual"><?= e(__('bud.period.anual')) ?></option></select>
                </div>
                <div>
                    <label><?= e(__('bud.amount')) ?></label>
                    <input name="amount" inputmode="decimal" required>
                </div>
            </div>
            <label><?= e(__('bud.start_on')) ?></label>
            <input type="date" name="start_on">
            <label style="margin-top:.5rem"><input type="checkbox" name="rollover" value="1" style="width:auto"> <?= e(__('bud.rollover')) ?></label>
            <button class="btn" type="submit"><?= e(__('bud.add')) ?></button>
        </form>
    </section>
</div>
