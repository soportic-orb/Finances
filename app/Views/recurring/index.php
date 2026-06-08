<?php
/** @var array<int,array<string,mixed>> $items */
/** @var ?string $ok */
/** @var ?string $error */
$active = 'recurring';
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('rec.title')) ?></h1>
    <form method="post" action="<?= e(url('/recurring/detect')) ?>">
        <?= csrf_field() ?><button class="btn" type="submit"><?= e(__('rec.detect')) ?></button>
    </form>
</div>
<?php require BASE_PATH . '/app/Views/_plannav.php'; ?>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<section class="card">
    <p class="muted"><?= e(__('rec.detect_help')) ?></p>
    <?php if ($items === []): ?>
        <p class="muted"><?= e(__('rec.none')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr>
                <th><?= e(__('rec.label')) ?></th><th style="text-align:right"><?= e(__('rec.amount')) ?></th>
                <th><?= e(__('rec.cadence')) ?></th><th><?= e(__('rec.next')) ?></th>
                <th><?= e(__('rec.occurrences')) ?></th><th><?= e(__('rec.status')) ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['label']) ?>
                        <?php if ((int) $it['is_subscription'] === 1): ?><span class="badge badge--ok"><?= e(__('rec.subscription')) ?></span><?php endif; ?>
                    </td>
                    <td style="text-align:right" class="neg"><?= e(money(-(float) $it['amount_est'])) ?></td>
                    <td><span class="badge"><?= e($it['cadence']) ?></span></td>
                    <td class="muted"><?= e($it['next_expected_on'] ?? '—') ?></td>
                    <td class="muted"><?= e((string) $it['occurrences']) ?></td>
                    <td>
                        <?php if ($it['status'] === 'inactive'): ?>
                            <span class="badge" style="color:#f59e0b" title="<?= e(__('rec.inactive_hint')) ?>"><?= e(__('rec.inactive')) ?> ⚠</span>
                        <?php else: ?>
                            <span class="badge badge--ok"><?= e(__('rec.active')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="rowactions">
                        <form method="post" action="<?= e(url('/recurring/' . $it['id'] . '/delete')) ?>">
                            <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('rec.delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
