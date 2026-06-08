<?php
/** @var array<int,array<string,mixed>> $tree */
/** @var array<int,string> $parents */
/** @var array<int,string> $kinds */
/** @var ?string $ok */
/** @var ?string $error */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('cat.title')) ?></h1>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('cat.title')) ?></h2>
        <?php foreach ($tree as $parent): ?>
            <div class="cat-parent">
                <span class="cat-name">
                    <?php if ($parent['icon']): ?><?= e($parent['icon']) ?> <?php endif; ?>
                    <strong><?= e($parent['name']) ?></strong>
                    <span class="badge"><?= e(__('cat.kind.' . $parent['kind'])) ?></span>
                </span>
                <span class="rowactions">
                    <a class="linkbtn" href="<?= e(url('/categories/' . $parent['id'] . '/edit')) ?>"><?= e(__('cat.edit')) ?></a>
                    <form method="post" action="<?= e(url('/categories/' . $parent['id'] . '/delete')) ?>"
                          onsubmit="return confirm('<?= e(__('cat.confirm_delete')) ?>')">
                        <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('cat.delete')) ?></button>
                    </form>
                </span>
            </div>
            <?php foreach ($parent['children'] as $child): ?>
                <div class="cat-child">
                    <span class="cat-name">
                        <?php if ($child['icon']): ?><?= e($child['icon']) ?> <?php endif; ?><?= e($child['name']) ?>
                    </span>
                    <span class="rowactions">
                        <a class="linkbtn" href="<?= e(url('/categories/' . $child['id'] . '/edit')) ?>"><?= e(__('cat.edit')) ?></a>
                        <form method="post" action="<?= e(url('/categories/' . $child['id'] . '/delete')) ?>"
                              onsubmit="return confirm('<?= e(__('cat.confirm_delete')) ?>')">
                            <?= csrf_field() ?><button class="linkbtn linkbtn--danger" type="submit"><?= e(__('cat.delete')) ?></button>
                        </form>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('cat.add')) ?></h2>
        <form method="post" action="<?= e(url('/categories')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('cat.name')) ?></label>
            <input name="name" required>
            <div class="row">
                <div>
                    <label><?= e(__('cat.kind')) ?></label>
                    <select name="kind">
                        <?php foreach ($kinds as $k): ?><option value="<?= e($k) ?>"><?= e(__('cat.kind.' . $k)) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><?= e(__('cat.parent')) ?></label>
                    <select name="parent_id">
                        <option value=""><?= e(__('cat.no_parent')) ?></option>
                        <?php foreach ($parents as $id => $name): ?><option value="<?= e((string) $id) ?>"><?= e($name) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div>
                    <label><?= e(__('cat.icon')) ?></label>
                    <input name="icon" maxlength="8" placeholder="🏠">
                </div>
                <div>
                    <label><?= e(__('cat.color')) ?></label>
                    <input type="color" name="color" value="#4f8cff">
                </div>
            </div>
            <button class="btn" type="submit"><?= e(__('cat.add')) ?></button>
        </form>
    </section>
</div>
