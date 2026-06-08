<?php
/** Informe mensual integrat a la interfície de l'app. */
/** @var array<string,mixed>|null $household */
/** @var int $year */
/** @var int $month */
/** @var string $periodLabel */
/** @var array<string,mixed> $summary */
/** @var array<int,array<string,mixed>> $breakdown */
/** @var array<int,array<string,mixed>> $byMember */
/** @var array<int,array<string,mixed>> $evolution */
/** @var array<int,array<string,mixed>> $netWorthSeries */
use App\Support\ChartSvg;
$palette = ChartSvg::palette();
?>
<div class="pagehead">
    <h1 class="card__title"><?= e(__('rep.title')) ?> · <?= e($periodLabel) ?></h1>
    <form method="get" action="<?= e(url('/reports/monthly')) ?>" class="filters">
        <input type="number" name="year" value="<?= e((string) $year) ?>" style="flex:0 0 90px">
        <input type="number" name="month" value="<?= e((string) $month) ?>" min="1" max="12" style="flex:0 0 70px">
        <button class="btn btn--ghost" type="submit"><?= e(__('rep.view')) ?></button>
        <a class="btn" href="<?= e(url('/reports/monthly.pdf?year=' . $year . '&month=' . $month)) ?>"><?= e(__('rep.download_pdf')) ?></a>
    </form>
</div>

<div class="kpis">
    <div class="kpi"><span class="muted"><?= e(__('dash.income')) ?></span><strong class="pos"><?= e(money($summary['income'])) ?></strong></div>
    <div class="kpi"><span class="muted"><?= e(__('dash.expense')) ?></span><strong class="neg"><?= e(money($summary['expense'])) ?></strong></div>
    <div class="kpi"><span class="muted"><?= e(__('dash.net')) ?></span><strong class="<?= $summary['net'] < 0 ? 'neg' : 'pos' ?>"><?= e(money($summary['net'])) ?></strong></div>
    <div class="kpi"><span class="muted"><?= e(__('dash.savings_rate')) ?></span><strong><?= e((string) $summary['savings_rate']) ?>%</strong></div>
</div>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('dash.by_category')) ?></h2>
        <?php if ($breakdown === []): ?>
            <p class="muted"><?= e(__('dash.no_data')) ?></p>
        <?php else: ?>
            <div class="chartrow">
                <?= ChartSvg::donut($breakdown) ?>
                <ul class="legend">
                    <?php foreach ($breakdown as $i => $b): ?>
                        <li><span class="dot2" style="background:<?= e($b['color'] ?: $palette[$i % count($palette)]) ?>"></span>
                            <?= e($b['label']) ?><span class="muted"> · <?= e(money((float) $b['value'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('dash.by_member')) ?></h2>
        <?php if ($byMember === []): ?>
            <p class="muted"><?= e(__('dash.no_data')) ?></p>
        <?php else: ?>
            <table class="table"><tbody>
            <?php foreach ($byMember as $m): ?>
                <tr><td><?= e($m['label']) ?></td><td style="text-align:right" class="neg"><?= e(money((float) $m['value'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </section>
</div>

<div class="grid2">
    <section class="card">
        <h2 class="card__subtitle"><?= e(__('dash.evolution')) ?>
            <span class="legend-inline"><span class="dot2" style="background:#10b981"></span><?= e(__('dash.legend_income')) ?>
            <span class="dot2" style="background:#ef4444"></span><?= e(__('dash.legend_expense')) ?></span>
        </h2>
        <?= ChartSvg::bars($evolution) ?>
    </section>

    <section class="card">
        <h2 class="card__subtitle"><?= e(__('dash.networth_time')) ?></h2>
        <?= ChartSvg::line($netWorthSeries) ?>
    </section>
</div>
