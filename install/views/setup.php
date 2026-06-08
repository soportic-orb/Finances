<?php
/** @var string $csrf */
/** @var array<string,string> $old */
/** @var ?string $error */
$tz = $old['timezone'] ?? 'Europe/Madrid';
$locale = $old['locale'] ?? 'ca';
$currency = $old['currency'] ?? 'EUR';
?>
<h2>3. La teva llar i el compte propietari</h2>
<p class="muted">No es demanen claus d'API ara: es configuren després al panell.</p>

<?php if (!empty($error)): ?>
    <div class="alert alert--err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="?step=3">
    <?= $csrf ?>

    <label>Nom de la llar</label>
    <input name="household_name" value="<?= htmlspecialchars($old['household_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

    <div class="row">
        <div>
            <label>Moneda</label>
            <select name="currency">
                <?php foreach (['EUR', 'USD', 'GBP', 'CHF'] as $c): ?>
                    <option value="<?= $c ?>" <?= $currency === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Idioma</label>
            <select name="locale">
                <option value="ca" <?= $locale === 'ca' ? 'selected' : '' ?>>Català</option>
                <option value="es" <?= $locale === 'es' ? 'selected' : '' ?>>Castellà</option>
            </select>
        </div>
    </div>

    <label>Zona horària</label>
    <input name="timezone" value="<?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>" required>

    <label>URL de l'aplicació (opcional)</label>
    <input name="url" value="<?= htmlspecialchars($old['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="https://finances.example.com">

    <hr style="border:none;border-top:1px solid var(--border);margin:1.25rem 0">

    <label>El teu nom (propietari)</label>
    <input name="owner_name" value="<?= htmlspecialchars($old['owner_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
    <label>Correu electrònic</label>
    <input type="email" name="owner_email" value="<?= htmlspecialchars($old['owner_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
    <div class="row">
        <div>
            <label>Contrasenya</label>
            <input type="password" name="owner_password" required>
        </div>
        <div>
            <label>Repeteix la contrasenya</label>
            <input type="password" name="owner_password2" required>
        </div>
    </div>
    <p class="muted">Mínim 10 caràcters.</p>

    <button class="btn" type="submit">Instal·la</button>
</form>

<hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0">

<details>
    <summary class="muted">Restaura des d'un paquet de migració d'un altre servidor</summary>
    <p class="muted">Puja el paquet <code>.fin</code> per restaurar tota la instància (BD, claus i APP_KEY).</p>
    <form method="post" action="?step=import" enctype="multipart/form-data">
        <?= $csrf ?>
        <label>Paquet de migració (.fin)</label>
        <input type="file" name="bundle" accept=".fin" required>
        <label>Passphrase</label>
        <input type="password" name="passphrase" required>
        <button class="btn btn--ghost" type="submit">Restaura la instància</button>
    </form>
</details>
