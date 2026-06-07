<?php
/** @var array<string,mixed> $cat */
/** @var array<int,string> $parents */
/** @var array<int,string> $kinds */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('cat.edit')) ?></h1>

<section class="card" style="max-width:480px">
    <form method="post" action="<?= e(url('/categories/' . $cat['id'] . '/edit')) ?>">
        <?= csrf_field() ?>
        <label><?= e(__('cat.name')) ?></label>
        <input name="name" value="<?= e($cat['name']) ?>" required>
        <div class="row">
            <div>
                <label><?= e(__('cat.kind')) ?></label>
                <select name="kind">
                    <?php foreach ($kinds as $k): ?><option value="<?= e($k) ?>" <?= $cat['kind'] === $k ? 'selected' : '' ?>><?= e(__('cat.kind.' . $k)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><?= e(__('cat.parent')) ?></label>
                <select name="parent_id">
                    <option value=""><?= e(__('cat.no_parent')) ?></option>
                    <?php foreach ($parents as $id => $name): if ((int) $id === (int) $cat['id']) continue; ?>
                        <option value="<?= e((string) $id) ?>" <?= (int) ($cat['parent_id'] ?? 0) === (int) $id ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row">
            <div>
                <label><?= e(__('cat.icon')) ?></label>
                <input name="icon" maxlength="8" value="<?= e($cat['icon'] ?? '') ?>">
            </div>
            <div>
                <label><?= e(__('cat.color')) ?></label>
                <input type="color" name="color" value="<?= e($cat['color'] ?? '#4f8cff') ?>">
            </div>
        </div>
        <div style="margin-top:1rem">
            <button class="btn" type="submit"><?= e(__('common.save')) ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/categories')) ?>"><?= e(__('common.cancel')) ?></a>
        </div>
    </form>
</section>
