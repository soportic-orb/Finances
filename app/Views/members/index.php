<?php
/** @var array<int,array<string,mixed>> $members */
/** @var ?string $error */
/** @var ?string $ok */
use App\Support\Auth;
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('nav.members')) ?></h1>

<?php if (!empty($ok)): ?><div class="alert alert--ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('members.list')) ?></h2>
        <table class="table">
            <thead><tr>
                <th><?= e(__('members.name')) ?></th>
                <th><?= e(__('members.email')) ?></th>
                <th><?= e(__('members.role')) ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= e($m['name']) ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td>
                        <span class="badge"><?= e(__('role.' . $m['role'])) ?></span>
                        <?php if (!empty($m['totp_secret'])): ?><span class="badge badge--ok">2FA</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($m['role'] !== 'owner' && (int) $m['id'] !== Auth::id()): ?>
                            <form method="post" action="<?= e(url('/members/' . $m['id'] . '/delete')) ?>"
                                  onsubmit="return confirm('<?= e(__('members.confirm_delete')) ?>')">
                                <?= csrf_field() ?>
                                <button class="linkbtn linkbtn--danger" type="submit"><?= e(__('members.delete')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('members.add')) ?></h2>
        <form method="post" action="<?= e(url('/members/create')) ?>">
            <?= csrf_field() ?>
            <label><?= e(__('members.name')) ?></label>
            <input name="name" required>
            <label><?= e(__('members.email')) ?></label>
            <input type="email" name="email" required>
            <label><?= e(__('members.password')) ?></label>
            <input type="password" name="password" required>
            <p class="muted"><?= e(__('members.password_hint')) ?></p>
            <button class="btn" type="submit"><?= e(__('members.add')) ?></button>
        </form>
    </section>
</div>
