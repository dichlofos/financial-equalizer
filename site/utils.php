<?php

function fe_print($object) {
    echo "<pre>";
    print_r($object);
    echo "</pre>";
}

/*
function xu_startswith($str, $prefix) {
    return (substr($str, 0, strlen($prefix)) == $prefix);
}
*/