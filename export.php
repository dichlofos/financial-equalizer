<?php

require_once('equalizer.php');


function fe_print_csv_line($row) {
    $result = "";
    foreach ($row as $value) {
        $value = str_replace("\"", "'", $value);
        $result .= "\"$value\",";
    }
    echo substr($result, 0, strlen($result) - 1)."\r\n";
}

function fe_export_sheet_to_csv($sheet_data) {
    $members = fe_get_or($sheet_data, "members", array());
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $exchange_rates = fe_get_or($sheet_data, "exchange_rates", array());

    global $sheet_id;
    $calc_result = fe_calc_sheet($sheet_data);
    $transaction_sums = $calc_result["transaction_sums"];

    header("Content-Type: text/csv, charset=utf-8");
    header("Content-Disposition: attachment; filename=$sheet_id.csv");
    $row = array("Статья расходов", "Валюта");
    foreach ($members as $member_id => $member_name) {
        $row[] = $member_name;
        $row[] = "%";
    }
    $row[] = "Сумма";
    fe_print_csv_line($row);

    foreach ($transactions as $transaction_id => $transaction) {
        $transaction_currency = fe_get_currency($transaction);
        $transaction_description = fe_get_or($transaction, "description");
        $row = array($transaction_description, $transaction_currency);
        foreach ($members as $member_id => $member_name) {
            $charge = fe_get_charge($transaction, $member_id);
            $row[] = $charge;
            $row[] = fe_get_spent($transaction, $member_id);
        }
        $row[] = $transaction_sums[$transaction_id];

        fe_print_csv_line($row);
    }
    echo "\r\n";

    fe_print_csv_line(array("Валюта", "Курс"));
    foreach ($exchange_rates as $currency => $rate) {
        $row = array($currency, $rate);
        fe_print_csv_line($row);
    }
    echo "\r\n";

    fe_print_csv_line(array("Идентификатор листа"));
    fe_print_csv_line(array("$sheet_id"));

}
