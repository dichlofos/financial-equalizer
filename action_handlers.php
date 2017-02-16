<?php

require_once('static_config.php');

/**
  * New sheet creation action handler
  * @param sheet_id Sheet identifier (it does not exists yet)
  **/
function fe_action_new_sheet($sheet_id) {
    global $FE_DEFAULT_EXCHANGE_RATES;

    $sheet_data = array();
    $members = array();
    $sheet_data["members"] = $members;
    $transactions = array();
    $sheet_data["transactions"] = $transactions;
    $sheet_data["exchange_rates"] =  $FE_DEFAULT_EXCHANGE_RATES;
    fe_save_sheet($sheet_id, $sheet_data);
    // TODO(mvel): add creation date
    $_SESSION["sheet_id"] = $sheet_id;
}


/**
  * Transactions form update handler
  * @param sheet_id Sheet identifier
  **/
function fe_action_update_sheet($sheet_id) {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_old_data = $sheet_data;

    $transactions = array();
    $members = array();
    $exchange_rates = array();
    foreach ($_REQUEST as $key => $value) {
        $value = trim($value);
        if (fe_startswith($key, "tr")) {
            $amount_key = substr($key, 2);
            $amount_key = explode("_", $amount_key);
            $transaction_id = $amount_key[0];
            $member_id = $amount_key[1];
            $transactions[$transaction_id]["charges"][$member_id] = $value;
        } elseif (fe_startswith($key, "sp")) {
            $spent_key = substr($key, 2);
            $spent_key = explode("_", $spent_key);
            $transaction_id = $spent_key[0];
            $member_id = $spent_key[1];
            $transactions[$transaction_id]["spent"][$member_id] = $value;
        } elseif (fe_startswith($key, "cur")) {
            $transaction_id = substr($key, 3);
            $transactions[$transaction_id]["currency"] = $value;
        } elseif (fe_startswith($key, "dtr")) {
            $transaction_id = substr($key, 3);
            $transactions[$transaction_id]["description"] = $value;
        } elseif (fe_startswith($key, "ts")) {
            $transaction_id = substr($key, 4);
            $transactions[$transaction_id][FE_KEY_TIMESTAMP_MODIFIED] = $value;
        } elseif (fe_startswith($key, "m")) {
            if (fe_empty($value)) {
                continue;  // skip empty members
            }
            $member_id = substr($key, 1);
            $members[$member_id] = $value;
        } elseif (fe_startswith($key, "e")) {
            if (fe_empty($value)) {
                continue;  // skip empty currencies
            }
            $currency = substr($key, 1);
            $exchange_rates[$currency] = $value;
        }
    }
    $sheet_data["transactions"] = $transactions;
    $sheet_data["members"] = $members;
    $sheet_data["exchange_rates"] = $exchange_rates;

    # issue #22: calculate transaction update times
    fe_calculate_sheet_diff($sheet_old_data, $sheet_data);

    fe_save_sheet($sheet_id, $sheet_data);
}
