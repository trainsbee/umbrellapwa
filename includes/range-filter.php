<?php
declare(strict_types=1);

/**
 * Date range filter shared by the list pages.
 * Variables before including: $from, $to, $action (page file), $hidden (extra fields).
 */
?>
<form class="range-filter" method="get" action="<?= e($action) ?>">
  <?php foreach ($hidden ?? [] as $name => $value): ?>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
  <?php endforeach; ?>
  <label class="range-field"><span>From</span><input class="input" type="date" name="from" value="<?= e($from) ?>" max="<?= e(today()) ?>"></label>
  <label class="range-field"><span>To</span><input class="input" type="date" name="to" value="<?= e($to) ?>" max="<?= e(today()) ?>"></label>
  <button type="submit" class="btn btn--primary"><?= icon('filter', 14) ?> Apply</button>
</form>
