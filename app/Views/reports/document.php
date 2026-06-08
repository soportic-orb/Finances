<?php
/**
 * Document HTML autònom de l'informe mensual (per a PDF/impressió).
 * No usa el layout de l'app: tema clar i CSS inline perquè dompdf el renderitzi.
 */
/** @var array<string,mixed>|null $household */
/** @var int $year */
/** @var int $month */
/** @var string $periodLabel */
/** @var array<string,mixed> $summary */
/** @var array<int,array<string,mixed>> $breakdown */
/** @var array<int,array<string,mixed>> $byMember */
/** @var array<int,array<string,mixed>> $evolution */
/** @var array<int,array<string,mixed>> $netWorthSeries */
/** @var bool $embedded */
use App\Support\ChartSvg;
$palette = ChartSvg::palette();
?>
<!DOCTYPE html>
<html lang="<?= e(\App\Support\Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<title><?= e(__('rep.title')) ?> · <?= e($periodLabel) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 24px; }
    h1 { font-size: 20px; margin: 0 0 2px; }
    h2 { font-size: 14px; margin: 18px 0 8px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
    .muted { color: #777; }
    .head { display: flex; justify-content: space-between; align-items: flex-start; }
    .kpis { display: flex; gap: 10px; margin: 14px 0; }
    .kpi { flex: 1; border: 1px solid #e2e2e2; border-radius: 8px; padding: 10px; }
    .kpi span { display: block; font-size: 11px; color: #777; }
    .kpi strong { font-size: 16px; }
    .pos { color: #0a7d3b; } .neg { color: #c0392b; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    th, td { text-align: left; padding: 5px 4px; border-bottom: 1px solid #eee; }
    th { color: #777; }
    .cols { display: flex; gap: 24px; }
    .cols > div { flex: 1; }
    .chart { max-width: 100%; height: auto; background: #fff; }
    .legend { list-style: none; padding: 0; margin: 0; font-size: 12px; }
    .legend li { margin: 2px 0; }
    .dot2 { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 5px; }
    .actions { margin-bottom: 16px; }
    .btn { display: inline-block; background: #4f8cff; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; border: 0; cursor: pointer; }
    .btn--ghost { background: #eee; color: #333; }
    @media print { .actions, .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
<?php if ($embedded): ?>
    <div class="actions no-print">
        <a class="btn" href="<?= e(url('/reports/monthly.pdf?year=' . $year . '&month=' . $month)) ?>"><?= e(__('rep.download_pdf')) ?></a>
        <button class="btn btn--ghost" onclick="window.print()"><?= e(__('rep.print')) ?></button>
    </div>
<?php endif; ?>

<div class="head">
    <div>
        <h1><?= e(__('rep.title')) ?> — <?= e($periodLabel) ?></h1>
        <div class="muted"><?= e(__('rep.household')) ?>: <?= e($household['name'] ?? '') ?></div>
    </div>
    <div class="muted"><?= e(__('rep.generated')) ?> <?= e(date('d/m/Y H:i')) ?></div>
</div>

<div class="kpis">
    <div class="kpi"><span><?= e(__('dash.income')) ?></span><strong class="pos"><?= e(money($summary['income'])) ?></strong></div>
    <div class="kpi"><span><?= e(__('dash.expense')) ?></span><strong class="neg"><?= e(money($summary['expense'])) ?></strong></div>
    <div class="kpi"><span><?= e(__('dash.net')) ?></span><strong class="<?= $summary['net'] < 0 ? 'neg' : 'pos' ?>"><?= e(money($summary['net'])) ?></strong></div>
    <div class="kpi"><span><?= e(__('dash.savings_rate')) ?></span><strong><?= e((string) $summary['savings_rate']) ?>%</strong></div>
</div>

<div class="cols">
    <div>
        <h2><?= e(__('dash.by_category')) ?></h2>
        <?php if ($breakdown === []): ?><p class="muted"><?= e(__('dash.no_data')) ?></p><?php else: ?>
            <?= ChartSvg::donut($breakdown, 170) ?>
            <ul class="legend">
                <?php foreach ($breakdown as $i => $b): ?>
                    <li><span class="dot2" style="background:<?= e($b['color'] ?: $palette[$i % count($palette)]) ?>"></span>
                        <?= e($b['label']) ?> — <?= e(money((float) $b['value'])) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div>
        <h2><?= e(__('dash.by_member')) ?></h2>
        <table><tbody>
        <?php foreach ($byMember as $m): ?>
            <tr><td><?= e($m['label']) ?></td><td style="text-align:right" class="neg"><?= e(money((float) $m['value'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if ($byMember === []): ?><tr><td class="muted"><?= e(__('dash.no_data')) ?></td></tr><?php endif; ?>
        </tbody></table>
    </div>
</div>

<h2><?= e(__('dash.evolution')) ?></h2>
<?= ChartSvg::bars($evolution, 720, 200) ?>

<h2><?= e(__('dash.networth_time')) ?></h2>
<?= ChartSvg::line($netWorthSeries, 720, 200) ?>
</body>
</html>
