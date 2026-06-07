<?php
/** Formulari compartit per crear/editar una regla. */
/** @var array<int,string> $categories */
/** @var array<int,string> $matchTypes */
/** @var array<int,string> $fields */
$rule = $rule ?? null;
$action = $rule ? url('/rules/' . $rule['id'] . '/edit') : url('/rules');
$cur = static fn (string $k, string $def = '') => $rule[$k] ?? $def;
?>
<form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label><?= e(__('rule.if_field')) ?></label>
    <select name="field">
        <?php foreach ($fields as $f): ?>
            <option value="<?= e($f) ?>" <?= $cur('field') === $f ? 'selected' : '' ?>><?= e(__('rule.field.' . $f)) ?></option>
        <?php endforeach; ?>
    </select>
    <label><?= e(__('rule.matchtype')) ?></label>
    <select name="match_type">
        <?php foreach ($matchTypes as $m): ?>
            <option value="<?= e($m) ?>" <?= $cur('match_type') === $m ? 'selected' : '' ?>><?= e(__('rule.match.' . $m)) ?></option>
        <?php endforeach; ?>
    </select>
    <label><?= e(__('rule.pattern')) ?></label>
    <input name="pattern" value="<?= e((string) $cur('pattern')) ?>" required>
    <label><?= e(__('rule.then')) ?></label>
    <select name="set_category_id" required>
        <?php foreach ($categories as $id => $label): ?>
            <option value="<?= e((string) $id) ?>" <?= (int) $cur('set_category_id', '0') === (int) $id ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="row">
        <div>
            <label><?= e(__('rule.priority')) ?></label>
            <input type="number" name="priority" value="<?= e((string) $cur('priority', '100')) ?>">
        </div>
        <div style="display:flex;align-items:flex-end;padding-bottom:.4rem">
            <label style="margin:0">
                <input type="checkbox" name="enabled" value="1" style="width:auto" <?= $rule === null || (int) $cur('enabled', '1') === 1 ? 'checked' : '' ?>>
                <?= e(__('rule.enabled')) ?>
            </label>
        </div>
    </div>
    <button class="btn" type="submit"><?= e($rule ? __('common.save') : __('rule.add')) ?></button>
    <?php if ($rule): ?><a class="btn btn--ghost" href="<?= e(url('/rules')) ?>"><?= e(__('common.cancel')) ?></a><?php endif; ?>
</form>
