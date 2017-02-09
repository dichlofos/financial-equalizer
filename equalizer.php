<?php
require_once('utils.php');


define('FE_DEFAULT_CURRENCY', 'RUR');

function fe_save_sheet($sheet_id, $sheet_data) {
    $sheet_f = fopen("data/$sheet_id.json", "w");
    fwrite($sheet_f, json_encode($sheet_data));
    fclose($sheet_f);
}


function fe_load_sheet($sheet_id) {
    $name = "data/$sheet_id.json";
    if (!file_exists($name)) {
        return array();
    }
    $sheet_data = file_get_contents($name);
    return json_decode($sheet_data, true);
}

function fe_get_charge($transaction, $member_id) {
    $charges = fe_get_or($transaction, "charges", array());
    $charge = fe_get_or($charges, $member_id, "0");
    $charge_int = (integer)($charge);
    return $charge_int;
}


function fe_get_spent($transaction, $member_id) {
    $spent = fe_get_or($transaction, "spent", array());
    $member_spent = fe_get_or($spent, $member_id, "1.0");
    if ($member_spent == "yes") {
        // backwards compatibility
        $member_spent = "1.0";
    } elseif (fe_empty($member_spent)) {
        $member_spent = "0.0";
    }
    return (float)$member_spent;
}


function fe_get_currency($transaction) {
    return fe_get_or($transaction, "currency", FE_DEFAULT_CURRENCY);
}


/**
 * @param [in] old_sheet_data: Old sheet data
 * @param [in/out] sheet_data: New sheet data (will be updated with timestamps)
 * @param [in] timestamp: Timestamp to set (useful for mocks)
 **/
function fe_calculate_sheet_diff($old_sheet_data, &$sheet_data, $timestamp = false) {
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $old_transactions = fe_get_or($old_sheet_data, "transactions", array());
    foreach ($transactions as $transaction_id => $transaction) {
        if (array_key_exists($transaction_id, $old_transactions)) {
            // transaction exists in both sheets
            $transaction_str = json_encode($transaction);
            $old_transaction = $old_transactions[$transaction_id];
            $old_transaction_str = json_encode($old_transaction);
            if ($old_transaction_str != $transaction_str) {
                $sheet_data["transactions"][$transaction_id]["timestamp"] = fe_datetime($timestamp);
            }
        } else {
            // new transaction: just set timestamp
            // This should actually never happen
            $sheet_data["transactions"][$transaction_id]["timestamp"] = fe_datetime($timestamp);
        }
    }
}


function fe_calc_sheet($sheet_data) {
    $members = fe_get_or($sheet_data, "members", array());
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $exchange_rates = fe_get_or($sheet_data, "exchange_rates", array());

    $deltas = array();
    $member_sums = array();
    $transaction_sums = array();
    foreach ($members as $member_id => $member_name) {
        $member_sums[$member_id] = 0;
    }

    $spent_min = 0;
    $spent_max = 0;

    $all_transactions_sum = 0;
    $bad_lambda_norm = array();
    foreach ($transactions as $transaction_id => $transaction) {
        $transaction_currency = strtoupper(fe_get_currency($transaction));
        $rate = (integer)fe_get_or($exchange_rates, $transaction_currency, "1");
        // calc transaction sum and spenders count
        $transaction_sum = 0;
        $lambda_norm = 0.0;
        foreach ($members as $member_id => $member_name) {
            $member_charge = fe_get_charge($transaction, $member_id);
            $transaction_sum += $member_charge * $rate;
            $member_spent = fe_get_spent($transaction, $member_id);

            // get min/max
            if ($spent_min > $member_charge)
                $spent_min = $member_charge;
            if ($spent_max < $member_charge)
                $spent_max = $member_charge;

            $lambda_norm += $member_spent;
        }
        if ($lambda_norm < 0.01) {
            $bad_lambda_norm[$transaction_id] = true;
        }
        $transaction_sums[$transaction_id] = $transaction_sum;
        $all_transactions_sum += $transaction_sum;

        $deltas[$transaction_id] = array();

        // charge - average spending
        foreach ($members as $member_id => $member_name) {
            $own_good = 0;
            if ($lambda_norm >= 0.01) {
                $own_good = $transaction_sum * fe_get_spent($transaction, $member_id) / $lambda_norm;
            }
            $delta = fe_get_charge($transaction, $member_id) * $rate - $own_good;
            $deltas[$transaction_id][$member_id] = $delta;
            $member_sums[$member_id] += $delta;
        }
    }
    $avg_spendings = 0.0;
    if (count($members) > 0) {
        $avg_spendings = ((integer)(100.0 * $all_transactions_sum / count($members))) / 100;
    }
    return array(
        "deltas" => $deltas,
        "member_sums" => $member_sums,
        "transaction_sums" => $transaction_sums,
        "all_transactions_sum" => $all_transactions_sum,
        "bad_lambda_norm" => $bad_lambda_norm,
        "avg_spendings" => $avg_spendings,
        "spent_min" => $spent_min,
        "spent_max" => $spent_max,
    );
}

