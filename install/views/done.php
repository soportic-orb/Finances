<?php
/** @var string $appUrl */
?>
<h2>4. Instal·lació completada 🎉</h2>
<div class="alert alert--ok">
    La teva instància de Finances ja està llesta. L'instal·lador ha quedat
    <strong>bloquejat</strong> (s'ha creat <code>/install/.lock</code>).
</div>

<p class="muted">Per seguretat, pots eliminar el directori <code>/install</code> del servidor.</p>

<p>Properes passes (al panell, ja iniciada sessió):</p>
<ul class="muted">
    <li>Afegir membres de la llar i activar 2FA (Fase 3).</li>
    <li>Crear comptes i registrar moviments (Fase 4).</li>
    <li>Connectar el banc amb Enable Banking i configurar la clau d'Anthropic.</li>
</ul>

<a class="btn" href="<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>">Vés al panell</a>
