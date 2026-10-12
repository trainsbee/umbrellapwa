<?php
declare(strict_types=1);

/**
 * Reads ?from= and ?to= (defaults to today), keeps them in order and caps the
 * range at 31 days so a single request never pulls too much data.
 *
 * @return array{0: string, 1: string}
 */
function date_range_params(): array
{
    $to = min(date_param('to', today()), today());
    $from = min(date_param('from', $to), $to);
    $earliest = (new DateTimeImmutable($to))->modify('-30 days')->format('Y-m-d');
    return [max($from, $earliest), $to];
}
