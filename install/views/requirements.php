<?php
/** @var array<int,array{label:string,ok:bool,detail:string}> $checks */
/** @var bool $pass */
?>
<h2>1. Comprovació de requisits</h2>
<p class="muted">Cal que tots els ítems estiguin en verd per continuar.</p>

<?php foreach ($checks as $c): ?>
    <div class="check">
        <span><?= htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="<?= $c['ok'] ? 'ok' : 'err' ?>">
            <?= $c['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($c['detail'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>
<?php endforeach; ?>

<?php if ($pass): ?>
    <a class="btn" href="?step=2">Continua</a>
<?php else: ?>
    <div class="alert alert--err">Corregeix els requisits marcats i recarrega la pàgina.</div>
    <a class="btn btn--ghost" href="?step=1">Torna a comprovar</a>
<?php endif; ?>
