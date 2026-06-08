<?php
/** @var string $body */
/** @var int $step */
/** @var int $total */
$steps = ['Requisits', 'Base de dades', 'Configuració', 'Fet'];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instal·lador · Finances</title>
    <style>
        /* CSS inline: instal·lador completament aïllat, sense fitxers ni CDN externs. */
        :root{--bg:#0f1115;--surface:#171a21;--s2:#1f242e;--text:#e6e9ef;--muted:#9aa3b2;
              --primary:#4f8cff;--ok:#34d399;--err:#f87171;--border:#2a2f3a;--radius:12px;}
        *{box-sizing:border-box}
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
             background:var(--bg);color:var(--text);line-height:1.5}
        .wrap{max-width:640px;margin:3rem auto;padding:0 1rem}
        h1{font-size:1.5rem;margin:0 0 .25rem}
        .lead{color:var(--muted);margin:0 0 1.5rem}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem}
        .steps{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
        .steps .s{flex:1;min-width:120px;text-align:center;font-size:.8rem;color:var(--muted);
                  padding:.4rem;border-radius:8px;background:var(--s2);border:1px solid var(--border)}
        .steps .s.active{color:#fff;border-color:var(--primary)}
        .steps .s.done{color:var(--ok)}
        label{display:block;margin:.75rem 0 .25rem;font-size:.9rem}
        input,select{width:100%;padding:.55rem .65rem;background:var(--bg);color:var(--text);
                     border:1px solid var(--border);border-radius:8px;font-size:.95rem}
        .row{display:flex;gap:1rem}.row>div{flex:1}
        .btn{display:inline-block;margin-top:1.25rem;background:var(--primary);color:#fff;border:0;
             border-radius:8px;padding:.6rem 1.1rem;font-size:.95rem;cursor:pointer;text-decoration:none}
        .btn:hover{filter:brightness(1.08)}
        .btn--ghost{background:var(--s2);border:1px solid var(--border)}
        .check{display:flex;justify-content:space-between;padding:.5rem .25rem;border-bottom:1px solid var(--border)}
        .ok{color:var(--ok)}.err{color:var(--err)}
        .alert{padding:.75rem 1rem;border-radius:8px;margin:1rem 0;font-size:.9rem}
        .alert--err{background:rgba(248,113,113,.12);border:1px solid var(--err);color:#fecaca}
        .alert--ok{background:rgba(52,211,153,.12);border:1px solid var(--ok);color:#bbf7d0}
        .muted{color:var(--muted);font-size:.85rem}
        code{background:var(--s2);padding:.1rem .35rem;border-radius:5px}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Finances · Instal·lador</h1>
    <p class="lead">Configuració inicial de la teva instància privada.</p>
    <div class="steps">
        <?php foreach ($steps as $i => $name): ?>
            <div class="s <?= $i + 1 === $step ? 'active' : ($i + 1 < $step ? 'done' : '') ?>">
                <?= $i + 1 ?>. <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card"><?= $body ?></div>
</div>
</body>
</html>
