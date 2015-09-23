<?php

function fe_get_or($array, $key, $default_value) {
    if array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default_value;
}

function fe_empty($value) {
    return strlen($value) == 0;
}

function fe_new_sheet() {
}

function fe_edit_sheet($sheet_id) {

}

$sheet_id = fe_get_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/^[0-9a-f-]/", "", $sheet_id);


if (fe_empty($sheet_id)) {
    fe_new_sheet();
} else {
    fe_edit_sheet($sheet_id);
}

