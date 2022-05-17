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
  * New transaction adding action handler
  * @param sheet_id Sheet identifier
  * @param request: POST request data
  **/
function fe_action_add_transaction($sheet_id, $request) {
    $description = xcms_get_key_or($_REQUEST, "description");
    $sheet_data = fe_load_sheet($sheet_id);
    $timestamp_str = xcms_datetime();
    $members = xcms_get_key_or($sheet_data, "members");
    $spent = array();
    foreach ($members as $member_id => $member_name) {
        $spent[$member_id] = 1.0; // default spending is for all members
    }
    $charges = array();
    foreach ($members as $member_id => $member_name) {
        $charges[$member_id] = 0; // default charges is for all members
    }
    $sheet_data["transactions"][] = array(
        "description" => $description,
        "currency" => FE_DEFAULT_CURRENCY,
        "spent" => $spent,
        "charges" => $charges,
        FE_KEY_TIMESTAMP_MODIFIED => $timestamp_str,
    );
    $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = $timestamp_str;
    fe_save_sheet($sheet_id, $sheet_data);
}


/**
  * Transactions form update handler
  * @param sheet_id Sheet identifier
  **/
function fe_action_update_sheet($sheet_id, $request) {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_old_data = $sheet_data;

    $transactions = array();
    $members = array();
    $exchange_rates = array();
    foreach ($request as $key => $value) {
        $value = trim($value);
        if (xu_startswith($key, "tr")) {
            $amount_key = substr($key, 2);
            $amount_key = explode("_", $amount_key);
            $transaction_id = $amount_key[0];
            $member_id = $amount_key[1];
            $transactions[$transaction_id]["charges"][$member_id] = (integer)($value);
        } elseif (xu_startswith($key, "sp")) {
            $spent_key = substr($key, 2);
            $spent_key = explode("_", $spent_key);
            $transaction_id = $spent_key[0];
            $member_id = $spent_key[1];
            $transactions[$transaction_id]["spent"][$member_id] = (float)($value);
        } elseif (xu_startswith($key, "cur")) {
            $transaction_id = substr($key, 3);
            $transactions[$transaction_id]["currency"] = $value;
        } elseif (xu_startswith($key, "dtr")) {
            $transaction_id = substr($key, 3);
            $transactions[$transaction_id]["description"] = $value;
        } elseif (xu_startswith($key, "m")) {
            if (xu_empty($value)) {
                continue;  // skip empty members
            }
            $member_id = substr($key, 1);
            $members[$member_id] = $value;
        } elseif (xu_startswith($key, "e")) {
            if (xu_empty($value)) {
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
