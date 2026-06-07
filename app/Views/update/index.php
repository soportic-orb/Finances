<?php
/** @var string $version */
/** @var string $branch */
/** @var bool $maintenance */
/** @var ?string $check */
/** @var array<int,array{ts:string,line:string}> $log */
/** @var array<int,array{file:string,size:int,time:int}> $backups */
/** @var ?string $ok */
/** @var ?string $error */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('upd.title')) ?></h1>

<?php if ($maintenance): ?><div class="alert alert--err"><?= e(__('upd.maintenance_on')) ?></div><?php endif; ?>
<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($check)): ?><div class="alert"><?= e($check) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('upd.title')) ?></h2>
        <dl class="meta">
            <dt><?= e(__('upd.version')) ?></dt><dd><?= e($version) ?></dd>
            <dt><?= e(__('upd.branch')) ?></dt><dd><?= e($branch) ?></dd>
        </dl>
        <div class="rowactions" style="margin-top:1rem">
            <form method="post" action="<?= e(url('/update/check')) ?>">
                <?= csrf_field() ?><button class="btn btn--ghost" type="submit"><?= e(__('upd.check')) ?></button>
            </form>
            <form method="post" action="<?= e(url('/update/run')) ?>" onsubmit="return confirm('<?= e(__('upd.run_confirm')) ?>')">
                <?= csrf_field() ?><button class="btn" type="submit"><?= e(__('upd.run')) ?></button>
            </form>
            <form method="post" action="<?= e(url('/update/backup')) ?>">
                <?= csrf_field() ?><button class="btn btn--ghost" type="submit"><?= e(__('upd.backup_now')) ?></button>
            </form>
        </div>
        <p class="muted" style="margin-top:1rem;font-size:.85rem"><?= e(__('upd.safe_note')) ?></p>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('upd.backups')) ?></h2>
        <?php if ($backups === []): ?>
            <p class="muted"><?= e(__('upd.no_backups')) ?></p>
        <?php else: ?>
            <table class="table"><tbody>
            <?php foreach ($backups as $b): ?>
                <tr>
                    <td><?= e($b['file']) ?></td>
                    <td class="muted"><?= e(number_format($b['size'] / 1024, 1, ',', '.')) ?> KB</td>
                    <td class="muted"><?= e(date('Y-m-d H:i', $b['time'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </section>
</div>

<section class="card" style="margin-top:1rem">
    <h2 class="card__subtitle"><?= e(__('upd.log')) ?></h2>
    <?php if ($log === []): ?>
        <p class="muted"><?= e(__('upd.no_log')) ?></p>
    <?php else: ?>
        <pre class="logbox"><?php foreach ($log as $l) {
            echo e($l['line']) . "\n";
        } ?></pre>
    <?php endif; ?>
</section>
