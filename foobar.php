<?php

function generate(int $number, int $end): void
{
    $output = '';

    if ($number % 3 === 0) {
        $output .= 'foo';
    }

    if ($number % 5 === 0) {
        $output .= 'bar';
    }

    echo $output ?: $number;

    if ($number < $end) {
        echo ', ';
        generate($number + 1, $end);
    }
}

generate(1, 10);