<?php

function fe_print($object) {
    echo "<pre>";
    print_r($object);
    echo "</pre>";
}


function fe_get_or($array, $key, $default_value = "") {
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default_value;
}


function fe_empty($value) {
    return strlen($value) == 0;
}


function fe_not_empty($value) {
    return strlen($value) > 0;
}


function fe_startswith($str, $prefix) {
    return (substr($str, 0, strlen($prefix)) == $prefix);
}


/**
  * YYYY-MM-DD HH:MM:SS
  * Human-readable timestamp
  **/
function fe_datetime($timestamp = false)
{
    if ($timestamp === false)
        return date("Y-m-d H:i:s");
    if (fe_empty($timestamp))
        return "";
    return date("Y-m-d H:i:s", $timestamp);
}

/**
  * YYYY-MM-DD.HH-MM-SS
  * Human-readable timestamp without spaces, suitable for filenames
  **/
function fe_datetime_ns($timestamp = false)
{
    if ($timestamp === false)
        return date("Y-m-d.H-i-s");
    if (fe_empty($timestamp))
        return "";
    return date("Y-m-d.H-i-s", $timestamp);
}


