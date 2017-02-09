<?php

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

    header("Content-Type: text/csv, charset=utf-8");
    header("Content-Disposition: attachment; filename=$sheet_id.csv");
    $row = array();
    foreach ($members as $member_id => $member_name) {
        $row[] = $member_name;
        $row[] = "%";
    }
    fe_print_csv_line($row);

    foreach ($transactions as $transaction_id => $transaction) {
        $transaction_currency = strtoupper(fe_get_currency($transaction));
        $rate = (integer)fe_get_or($exchange_rates, $transaction_currency, "1");
        // calc transaction sum and spenders count
        $transaction_sum = 0;
        $lambda_norm = 0.0;
        $row = array();
        foreach ($members as $member_id => $member_name) {
            $row[] = fe_get_charge($transaction, $member_id);
            $row[] = fe_get_spent($transaction, $member_id);
        }
        fe_print_csv_line($row);
    }

    echo "\r\n";

}
