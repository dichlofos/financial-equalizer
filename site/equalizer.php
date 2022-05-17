<?php
require_once('utils.php');
require_once("${xengine_dir}sys/util.php");


function fe_save_sheet($sheet_id, $sheet_data) {
    if (xu_empty($sheet_id)) {
        // FIXME(mvel) Add logging here
        die("[fe_save_sheet] Sheet identifier cannot be empty. Please report a bug to developers");
        return;
    }
    $sheet_f = fopen("data/$sheet_id.json", "w");
    fwrite($sheet_f, json_encode($sheet_data));
    fclose($sheet_f);
}


function fe_load_sheet($sheet_id) {
    if (xu_empty($sheet_id)) {
        // FIXME(mvel) Add logging here
        die("[fe_load_sheet] Sheet id cannot be empty. Please report a bug to developers");
        return;
    }

    $name = "data/$sheet_id.json";
    if (!file_exists($name)) {
        return array();
    }
    $sheet_data = file_get_contents($name);
    return json_decode($sheet_data, true);
}


function fe_remove_sheet($sheet_id) {
    if (xu_empty($sheet_id)) {
        // FIXME(mvel) Add logging here
        die("[fe_remove_sheet] Sheet id cannot be empty. Please report a bug to developers");
        return;
    }

    $name = "data/$sheet_id.json";
    if (!file_exists($name)) {
        return false;
    }
    unlink($name);
    return true;
}


function fe_get_charge($transaction, $member_id) {
    $charges = xcms_get_key_or($transaction, "charges", array());
    $charge = xcms_get_key_or($charges, $member_id, "0");
    $charge_int = (integer)($charge);
    return $charge_int;
}


function fe_get_spent($transaction, $member_id) {
    $spent = xcms_get_key_or($transaction, "spent", array());
    $member_spent = xcms_get_key_or($spent, $member_id, "1.0");
    if ($member_spent == "yes") {
        // backwards compatibility
        $member_spent = "1.0";
    } elseif (xu_empty($member_spent)) {
        $member_spent = "0.0";
    }
    return (float)$member_spent;
}


function fe_get_currency($transaction) {
    $currency = xcms_get_key_or($transaction, "currency", FE_DEFAULT_CURRENCY);

    if ($currency == "RUR") {
        $currency = FE_DEFAULT_CURRENCY;  // RUR and "Рубли" are equal
    }
    return $currency;
}


function _fe_compare_arrays($arr_a, $arr_b) {
    return json_encode($arr_a) == json_encode($arr_b);
}

/**
 * Compare two transactions.
 * We cannot use `json_decode` (bad) function for whole transaction comparison
 * by two reasons:
 * - keys are not sorted in json
 * - new transaction has no timestamp, so it always will have diff with old.
 *
 * @param new_transaction: New (updated) transaction data
 * @param old_transaction: Old (previous) transaction data
 * @return true if no significant changes detected
 **/
function fe_is_transactions_equal($new_transaction, $old_transaction) {
    if (
        xcms_get_key_or($new_transaction, "description") !==
        xcms_get_key_or($old_transaction, "description")
    ) {
        return false;
    }

    if (
        xcms_get_key_or($new_transaction, "currency") !==
        xcms_get_key_or($old_transaction, "currency")
    ) {
        return false;
    }

    if (!_fe_compare_arrays(
        xcms_get_key_or($new_transaction, "charges", array()),
        xcms_get_key_or($old_transaction, "charges", array())
    )) {
        return false;
    }

    if (!_fe_compare_arrays(
        xcms_get_key_or($new_transaction, "spent", array()),
        xcms_get_key_or($old_transaction, "spent", array())
    )) {
        return false;
    }

    return true;
}


/**
 * @param [in] old_sheet_data: Old sheet data
 * @param [in/out] sheet_data: New sheet data (will be updated with timestamps)
 * @param [in] timestamp: Timestamp to set (useful for mocks)
 **/
function fe_calculate_sheet_diff($old_sheet_data, &$sheet_data, $timestamp = false) {
    $transactions = xcms_get_key_or($sheet_data, "transactions", array());
    $old_transactions = xcms_get_key_or($old_sheet_data, "transactions", array());
    $timestamp_str = xcms_datetime($timestamp);
    $modified = false;

    foreach ($transactions as $transaction_id => $transaction) {
        if (array_key_exists($transaction_id, $old_transactions)) {
            // transaction exists in both sheets
            $old_transaction = $old_transactions[$transaction_id];
            if (fe_is_transactions_equal($transaction, $old_transaction)) {
                // new transaction does not have modification time, so use old one
                $sheet_data["transactions"][$transaction_id] = $old_transaction;
            } else {
                $sheet_data["transactions"][$transaction_id][FE_KEY_TIMESTAMP_MODIFIED] = $timestamp_str;
                $modified = true;
            }
        } else {
            // new transaction: just set timestamp
            // This should actually never happen
            $sheet_data["transactions"][$transaction_id][FE_KEY_TIMESTAMP_MODIFIED] = $timestamp_str;
            $modified = true;
        }
    }

    if ($modified) {
        $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = $timestamp_str;
    }
}


function fe_calc_sheet($sheet_data) {
    $members = xcms_get_key_or($sheet_data, "members", array());
    $transactions = xcms_get_key_or($sheet_data, "transactions", array());
    $exchange_rates = xcms_get_key_or($sheet_data, "exchange_rates", array());

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
        $transaction_currency = fe_get_currency($transaction);
        $rate = (float)xcms_get_key_or($exchange_rates, $transaction_currency, "1");
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

