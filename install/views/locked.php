<h2>Instal·lador bloquejat</h2>
<div class="alert alert--err">
    L'aplicació ja està instal·lada. L'instal·lador està desactivat per seguretat.
</div>
<p class="muted">
    Si necessites reinstal·lar, elimina <code>/config/config.php</code> i
    <code>/install/.lock</code> manualment des del servidor.
</p>
<a class="btn" href="<?= htmlspecialchars(rtrim((string) \App\Support\Config::get('app.url', ''), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">Vés a l'aplicació</a>
