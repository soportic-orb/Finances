<?php
/** @var array<string,mixed> $rule */
/** @var array<int,string> $categories */
/** @var array<int,string> $matchTypes */
/** @var array<int,string> $fields */
?>
<h1 class="card__title" style="margin-bottom:1rem"><?= e(__('rule.edit')) ?></h1>

<section class="card" style="max-width:480px">
    <?php require __DIR__ . '/_form.php'; ?>
</section>
