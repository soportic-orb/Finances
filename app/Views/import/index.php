<?php
/** @var array<int,string> $accounts */
/** @var array<int,array<string,mixed>> $templates */
/** @var ?string $ok */
/** @var ?string $error */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('imp.title')) ?></h1>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<?php if ($accounts === []): ?>
    <div class="card"><p class="muted"><?= e(__('acc.none')) ?></p></div>
<?php else: ?>
<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('imp.n43_title')) ?></h2>
        <p class="muted"><?= e(__('imp.n43_help')) ?></p>
        <form method="post" action="<?= e(url('/import/norma43')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label><?= e(__('imp.account')) ?></label>
            <select name="account_id" required>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <label><?= e(__('imp.file')) ?></label>
            <input type="file" name="file" accept=".txt,.q43,.n43,.043,.aeb" required>
            <button class="btn" type="submit"><?= e(__('imp.preview')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('imp.csv_title')) ?></h2>
        <p class="muted"><?= e(__('imp.csv_help')) ?></p>
        <form method="post" action="<?= e(url('/import/csv')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label><?= e(__('imp.account')) ?></label>
            <select name="account_id" required>
                <?php foreach ($accounts as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
            </select>
            <label><?= e(__('imp.file')) ?></label>
            <input type="file" name="file" accept=".csv,.txt" required>

            <?php if ($templates !== []): ?>
                <label><?= e(__('imp.use_template')) ?></label>
                <select name="template_id">
                    <option value="0"><?= e(__('imp.no_template')) ?></option>
                    <?php foreach ($templates as $t): ?><option value="<?= e((string) $t['id']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                </select>
            <?php endif; ?>

            <details style="margin-top:.75rem" open>
                <summary class="muted"><?= e(__('imp.csv_title')) ?></summary>
                <div class="row">
                    <div>
                        <label><?= e(__('imp.delimiter')) ?></label>
                        <select name="delimiter"><option value=",">,</option><option value=";">;</option><option value="tab">Tab</option></select>
                    </div>
                    <div>
                        <label><?= e(__('imp.decimal')) ?></label>
                        <select name="decimal"><option value=",">,</option><option value=".">.</option></select>
                    </div>
                </div>
                <label style="margin-top:.5rem"><input type="checkbox" name="has_header" value="1" style="width:auto" checked> <?= e(__('imp.has_header')) ?></label>
                <label><?= e(__('imp.date_format')) ?></label>
                <input name="date_format" value="d/m/Y">
                <div class="row">
                    <div><label><?= e(__('imp.date_col')) ?></label><input type="number" name="date_col" value="0" min="0"></div>
                    <div><label><?= e(__('imp.value_col')) ?></label><input type="number" name="value_col" min="0" placeholder=""></div>
                </div>
                <label><?= e(__('imp.amount_mode')) ?></label>
                <select name="amount_mode">
                    <option value="single"><?= e(__('imp.amount_single')) ?></option>
                    <option value="debit_credit"><?= e(__('imp.amount_dc')) ?></option>
                </select>
                <div class="row">
                    <div><label><?= e(__('imp.amount_col')) ?></label><input type="number" name="amount_col" value="1" min="0"></div>
                    <div><label><?= e(__('imp.desc_col')) ?></label><input type="number" name="desc_col" value="2" min="0"></div>
                </div>
                <div class="row">
                    <div><label><?= e(__('imp.debit_col')) ?></label><input type="number" name="debit_col" min="0" placeholder=""></div>
                    <div><label><?= e(__('imp.credit_col')) ?></label><input type="number" name="credit_col" min="0" placeholder=""></div>
                </div>
                <label><?= e(__('imp.merchant_col')) ?></label>
                <input type="number" name="merchant_col" min="0" placeholder="">
                <label><?= e(__('imp.save_template')) ?></label>
                <input name="save_template" placeholder="">
            </details>

            <button class="btn" type="submit"><?= e(__('imp.preview')) ?></button>
        </form>
    </section>
</div>

<?php if ($templates !== []): ?>
    <section class="card" style="margin-top:1rem">
        <h2 class="card__subtitle"><?= e(__('imp.templates')) ?></h2>
        <table class="table"><tbody>
        <?php foreach ($templates as $t): ?>
            <tr>
                <td><?= e($t['name']) ?></td>
                <td class="rowactions">
                    <form method="post" action="<?= e(url('/import/templates/' . $t['id'] . '/delete')) ?>">
                        <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('imp.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </section>
<?php endif; ?>
<?php endif; ?>
