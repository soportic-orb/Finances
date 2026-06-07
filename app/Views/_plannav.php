<?php /** @var string $active */ ?>
<div class="subnav">
    <a href="<?= e(url('/budgets')) ?>" class="<?= $active === 'budgets' ? 'active' : '' ?>"><?= e(__('plan.budgets')) ?></a>
    <a href="<?= e(url('/goals')) ?>" class="<?= $active === 'goals' ? 'active' : '' ?>"><?= e(__('plan.goals')) ?></a>
    <a href="<?= e(url('/recurring')) ?>" class="<?= $active === 'recurring' ? 'active' : '' ?>"><?= e(__('plan.recurring')) ?></a>
</div>
