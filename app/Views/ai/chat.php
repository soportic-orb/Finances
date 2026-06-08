<?php
/** @var bool $enabled */
/** @var array<int,array{q:string,a:string}> $history */
/** @var ?string $error */
$active = 'chat';
?>
<h1 class="card__title" style="margin-bottom:.5rem"><?= e(__('ai.chat_title')) ?></h1>
<?php require BASE_PATH . '/app/Views/ai/_subnav.php'; ?>

<?php if (!empty($error)): ?><div class="alert alert--err"><?= e($error) ?></div><?php endif; ?>

<?php if (!$enabled): ?>
    <div class="card"><p class="muted"><?= e(__('ai.not_configured')) ?></p>
        <a class="btn" href="<?= e(url('/ai/settings')) ?>"><?= e(__('ai.go_settings')) ?></a></div>
<?php else: ?>
    <section class="card">
        <?php if ($history === []): ?>
            <p class="muted"><?= e(__('ai.chat_empty')) ?></p>
        <?php else: ?>
            <div class="chat">
                <?php foreach ($history as $turn): ?>
                    <div class="chat__q"><strong><?= e(__('ai.you')) ?>:</strong> <?= e($turn['q']) ?></div>
                    <div class="chat__a md"><strong><?= e(__('ai.assistant')) ?>:</strong> <?= \App\Support\Markdown::render($turn['a']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/ai/chat')) ?>" style="margin-top:1rem">
            <?= csrf_field() ?>
            <div class="inline-add">
                <input name="question" placeholder="<?= e(__('ai.chat_ph')) ?>" autofocus>
                <button class="btn" type="submit"><?= e(__('ai.send')) ?></button>
            </div>
        </form>
        <?php if ($history !== []): ?>
            <form method="post" action="<?= e(url('/ai/chat/clear')) ?>" style="margin-top:.5rem">
                <?= csrf_field() ?><button class="linkbtn" type="submit"><?= e(__('ai.clear')) ?></button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
