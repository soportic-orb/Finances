<?php
/** @var string $csrf */
/** @var array<string,string> $old */
/** @var ?string $error */
?>
<h2>2. Base de dades</h2>
<p class="muted">Dades de connexió a MySQL/MariaDB. La base de dades ha d'existir.</p>

<?php if (!empty($error)): ?>
    <div class="alert alert--err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="?step=2">
    <?= $csrf ?>
    <div class="row">
        <div>
            <label>Host</label>
            <input name="host" value="<?= htmlspecialchars($old['host'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div>
            <label>Port</label>
            <input name="port" value="<?= htmlspecialchars($old['port'] ?? '3306', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
    </div>
    <label>Nom de la base de dades</label>
    <input name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
    <label>Usuari</label>
    <input name="user" value="<?= htmlspecialchars($old['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
    <label>Contrasenya</label>
    <input type="password" name="pass" value="">
    <button class="btn" type="submit">Prova i continua</button>
</form>
