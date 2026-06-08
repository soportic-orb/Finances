<?php
/** @var bool $configured */
/** @var string $environment */
/** @var array<int,array<string,mixed>> $links */
/** @var array<int,array<string,mixed>> $syncLog */
/** @var array<int,mixed> $aspsps */
/** @var ?string $aspspError */
/** @var ?string $ok */
/** @var ?string $error */
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('eb.title')) ?></h1>
    <a class="btn btn--ghost" href="<?= e(url('/banking/settings')) ?>"><?= e(__('eb.settings')) ?></a>
</div>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<?php if (!$configured): ?>
    <div class="card">
        <p class="muted"><?= e(__('eb.not_configured')) ?></p>
        <a class="btn" href="<?= e(url('/banking/settings')) ?>"><?= e(__('eb.configure')) ?></a>
    </div>
<?php else: ?>
    <div class="grid2">
        <section class="card">
            <h2 class="card__subtitle"><?= e(__('eb.linked_accounts')) ?> <span class="badge"><?= e($environment) ?></span></h2>
            <?php if ($links === []): ?>
                <p class="muted"><?= e(__('eb.no_links')) ?></p>
            <?php else: ?>
                <table class="table">
                    <thead><tr>
                        <th><?= e(__('eb.account')) ?></th><th><?= e(__('eb.consent')) ?></th>
                        <th><?= e(__('eb.last_sync')) ?></th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($links as $l): $days = days_until($l['valid_until']); ?>
                        <tr>
                            <td><?= e($l['account_name']) ?></td>
                            <td>
                                <?php if ($days === null): ?>
                                    <span class="muted">—</span>
                                <?php elseif ($days < 0): ?>
                                    <span class="badge" style="color:#f87171"><?= e(__('eb.expired')) ?></span>
                                <?php elseif ($days <= 7): ?>
                                    <span class="badge" style="color:#f59e0b"><?= e(__('eb.days_left', ['d' => $days])) ?> ⚠</span>
                                <?php else: ?>
                                    <span class="badge badge--ok"><?= e(__('eb.days_left', ['d' => $days])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= e($l['last_synced_at'] ?? __('eb.never')) ?></td>
                            <td class="rowactions">
                                <form method="post" action="<?= e(url('/banking/links/' . $l['id'] . '/sync')) ?>">
                                    <?= csrf_field() ?><button class="linkbtn" type="submit"><?= e(__('eb.sync')) ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="card__subtitle"><?= e(__('eb.link_bank')) ?></h2>
            <?php if ($aspspError): ?>
                <div class="alert alert--err"><?= e($aspspError) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/banking/link')) ?>">
                <?= csrf_field() ?>
                <label><?= e(__('eb.choose_bank')) ?></label>
                <select name="aspsp_name" required>
                    <?php foreach ($aspsps as $a): $n = is_array($a) ? ($a['name'] ?? '') : $a; ?>
                        <option value="<?= e((string) $n) ?>"><?= e((string) $n) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit" <?= $aspsps === [] ? 'disabled' : '' ?>><?= e(__('eb.link')) ?></button>
            </form>
            <p class="muted" style="margin-top:1rem"><?= e(__('eb.payments_note')) ?></p>
        </section>
    </div>

    <section class="card" style="margin-top:1rem">
        <h2 class="card__subtitle"><?= e(__('eb.sync_history')) ?></h2>
        <?php if ($syncLog === []): ?>
            <p class="muted">—</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th><?= e(__('eb.account')) ?></th><th>nous</th><th>dup</th><th>estat</th><th><?= e(__('tx.date')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($syncLog as $g): ?>
                    <tr>
                        <td><?= e($g['account_name']) ?></td>
                        <td class="pos"><?= e((string) $g['transactions_new']) ?></td>
                        <td class="muted"><?= e((string) $g['transactions_dup']) ?></td>
                        <td><span class="badge <?= $g['status'] === 'ok' ? 'badge--ok' : '' ?>"><?= e($g['status']) ?></span>
                            <?php if (!empty($g['error'])): ?><span class="muted" title="<?= e($g['error']) ?>">⚠</span><?php endif; ?>
                        </td>
                        <td class="muted"><?= e($g['started_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endif; ?>
