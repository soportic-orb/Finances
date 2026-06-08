<?php
/** @var string $application_id */
/** @var string $environment */
/** @var string $base_url */
/** @var string $redirect_url */
/** @var ?string $psu_ip */
/** @var ?string $psu_ua */
/** @var bool $has_key */
/** @var ?string $ok */
/** @var ?string $error */
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('eb.settings')) ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/banking')) ?>"><?= e(__('common.cancel')) ?></a>
</div>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<section class="card" style="max-width:620px">
    <form method="post" action="<?= e(url('/banking/settings')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label><?= e(__('eb.application_id')) ?></label>
        <input name="application_id" value="<?= e($application_id) ?>" required>

        <div class="row">
            <div>
                <label><?= e(__('eb.environment')) ?></label>
                <select name="environment">
                    <option value="production" <?= $environment === 'production' ? 'selected' : '' ?>>production</option>
                    <option value="sandbox" <?= $environment === 'sandbox' ? 'selected' : '' ?>>sandbox</option>
                </select>
            </div>
            <div>
                <label><?= e(__('eb.base_url')) ?></label>
                <input name="base_url" value="<?= e($base_url) ?>">
            </div>
        </div>

        <label><?= e(__('eb.redirect_url')) ?></label>
        <input name="redirect_url" value="<?= e($redirect_url) ?>" placeholder="https://el-meu-domini/banking/callback">

        <label><?= e(__('eb.pem')) ?>
            <?php if ($has_key): ?><span class="badge badge--ok"><?= e(__('eb.pem_uploaded')) ?></span><?php endif; ?>
        </label>
        <input type="file" name="pem" accept=".pem">
        <p class="muted"><?= e(__('eb.pem_help')) ?></p>

        <div class="row">
            <div>
                <label><?= e(__('eb.psu_ip')) ?></label>
                <input name="psu_ip" value="<?= e((string) $psu_ip) ?>">
            </div>
            <div>
                <label><?= e(__('eb.psu_ua')) ?></label>
                <input name="psu_ua" value="<?= e((string) $psu_ua) ?>">
            </div>
        </div>

        <button class="btn" type="submit"><?= e(__('common.save')) ?></button>
    </form>
</section>
