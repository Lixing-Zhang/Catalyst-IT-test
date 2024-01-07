<?php

function generate(int $number, int $end): void
{
    if ($number > $end) {
        return;
    }

    if ($number % 15 == 0) {
        echo "foobar,";
    } elseif ($number % 3 == 0) {
        echo "foo,";
    } elseif ($number % 5 == 0) {
        echo "bar,";
    } else {
        echo $number. ",";
    }

    generate($number + 1, $end);
}

generate(1, 100);