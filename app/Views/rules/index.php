<?php
/** @var array<int,array<string,mixed>> $rules */
/** @var array<int,string> $categories */
/** @var array<int,string> $matchTypes */
/** @var array<int,string> $fields */
/** @var ?string $ok */
/** @var ?string $error */
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('rule.title')) ?></h1>
    <div class="rowactions">
        <form method="post" action="<?= e(url('/rules/apply')) ?>">
            <?= csrf_field() ?><input type="hidden" name="mode" value="uncategorized">
            <button class="btn btn--ghost" type="submit"><?= e(__('rule.apply_uncat')) ?></button>
        </form>
        <form method="post" action="<?= e(url('/rules/apply')) ?>"
              onsubmit="return confirm('<?= e(__('rule.apply_all')) ?>?')">
            <?= csrf_field() ?><input type="hidden" name="mode" value="all">
            <button class="btn" type="submit"><?= e(__('rule.apply_all')) ?></button>
        </form>
    </div>
</div>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('rule.title')) ?></h2>
        <?php if ($rules === []): ?>
            <p class="muted"><?= e(__('rule.none')) ?></p>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th><?= e(__('rule.priority')) ?></th><th><?= e(__('rule.field')) ?></th>
                    <th><?= e(__('rule.pattern')) ?></th><th><?= e(__('rule.category')) ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr class="<?= (int) $r['enabled'] === 0 ? 'muted' : '' ?>">
                        <td><?= e((string) $r['priority']) ?></td>
                        <td>
                            <span class="badge"><?= e(__('rule.field.' . $r['field'])) ?></span>
                            <span class="muted"><?= e(__('rule.match.' . $r['match_type'])) ?></span>
                        </td>
                        <td><code><?= e($r['pattern']) ?></code></td>
                        <td><?= e($r['category_name'] ?? '—') ?>
                            <?php if ((int) $r['enabled'] === 0): ?><span class="badge">off</span><?php endif; ?>
                        </td>
                        <td class="rowactions">
                            <a class="linkbtn" href="<?= e(url('/rules/' . $r['id'] . '/edit')) ?>"><?= e(__('rule.edit')) ?></a>
                            <form method="post" action="<?= e(url('/rules/' . $r['id'] . '/delete')) ?>"
                                  onsubmit="return confirm('<?= e(__('rule.confirm_delete')) ?>')">
                                <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('rule.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('rule.add')) ?></h2>
        <?php require __DIR__ . '/_form.php'; ?>
    </section>
</div>
