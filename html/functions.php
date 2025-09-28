<?php

function inc_value(&$array, $key, $inc)
{
    if (isset($array[$key])) {
        $array[$key] += $inc;
    } else {
        $array[$key] = $inc;
    }
}
function array_min($array)
{
    $min = array_pop($array);
    foreach ($array as $val) {
        if ($val < $min) {
            $min = $val;
        }
    }
    return $min;
}
