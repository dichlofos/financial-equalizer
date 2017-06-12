<?php
$xengine_dir = "xengine/";

require_once("${xengine_dir}sys/string.php");
require_once('equalizer.php');
require_once('action_handlers.php');

function fe_uprint($object) {
    print_r($object);
    print_r("\n");
}

function fe_assert_equal($first, $second, $message) {
    if ($first != $second) {
        fe_uprint("Assertion failed: $first != $second [$message]");
    }
}


function fe_assert_inequal($first, $second, $message) {
    if ($first == $second) {
        fe_uprint("Assertion failed: $first == $second [$message]");
    }
}


function fe_test_saveload() {
    $sheet_id = "test";
    $sheet_file = "data/$sheet_id.json";
    if (file_exists($sheet_file)) {
        unlink($sheet_file);
    }
    $sheet_data = fe_load_sheet($sheet_id);
    fe_assert_equal(count($sheet_data), 0, "Non-existent sheet must be empty when loading");

    $sheet_data = array(
        "members" => array(
            "Вася",
            "Петя",
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "200",
                    "300",
                ),
            ),
        ),
    );
    fe_save_sheet($sheet_id, $sheet_data);
    $sheet_data = fe_load_sheet($sheet_id);
    fe_assert_equal($sheet_data["transactions"][0]["charges"][1], "200", "Charges does not match");

    fe_uprint("fe_test_saveload PASSED");
}


function fe_test_deltas() {
    $sheet_data = array(
        "members" => array(
            "Вася",
            "Петя",
            "Костя",
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "200",
                    "300",
                ),
                "spent" => array(
                    "1",
                    "1",
                    "1",
                ),
            ),
            array(
                "currency" => "RUR",
                "charges" => array(
                    "50",
                    "200",
                    "350",
                ),
                "spent" => array(
                    "0",
                    "1",
                    "2.0",
                ),
            ),
        ),
    );
    $result = fe_calc_sheet($sheet_data);
    $deltas = $result["deltas"];

    fe_assert_equal(count($deltas), 2, "Deltas count = transactions count");
    fe_assert_equal($deltas[0][0], -100, "Tr 0 deltas check 0");
    fe_assert_equal($deltas[0][1], 0, "Tr 0 deltas check 1");
    fe_assert_equal($deltas[0][2], 100, "Tr 0 deltas check 2");

    fe_assert_equal($deltas[1][0], 50, "Tr 1 deltas check 0");
    fe_assert_equal($deltas[1][1], 0, "Tr 1 deltas check 1");
    fe_assert_equal($deltas[1][2], -50, "Tr 1 deltas check 2");

    $member_sums = $result["member_sums"];
    fe_assert_equal($member_sums[0], -50, "Member sums check 0");
    fe_assert_equal($member_sums[1], 0, "Member sums check 1");
    fe_assert_equal($member_sums[2], 50, "Member sums check 2");

    $transaction_sums = $result["transaction_sums"];
    fe_assert_equal($transaction_sums[0], 600, "Transaction sums check 1");

    fe_uprint("fe_test_deltas PASSED");
}


function fe_test_currency() {
    $sheet_data = array(
        "members" => array(
            "Вася",
            "Петя",
            "Костя",
        ),
        "exchange_rates" => array(
            "RUR" => 1,
            "EUR" => 70,
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "200",
                    "300",
                ),
                "spent" => array(
                    "1",
                    "1",
                    "1",
                ),
            ),
            array(
                "currency" => "EUR",
                "charges" => array(
                    "50",
                    "200",
                    "350",
                ),
                "spent" => array(
                    "0",
                    "1",
                    "2.0",
                ),
            ),
            array(
                "currency" => "USD",  // unknown currency
                "charges" => array(
                    "50",
                    "200",
                    "350",
                ),
                "spent" => array(
                    "0",
                    "1",
                    "2.0",
                ),
            ),
        ),
    );
    $result = fe_calc_sheet($sheet_data);
    $deltas = $result["deltas"];

    fe_assert_equal(count($deltas), 3, "Deltas count = transactions count");
    fe_assert_equal($deltas[0][0], -100, "Tr 0 deltas check 0");
    fe_assert_equal($deltas[0][1], 0, "Tr 0 deltas check 1");
    fe_assert_equal($deltas[0][2], 100, "Tr 0 deltas check 2");

    fe_assert_equal($deltas[1][0], 50 * 70, "Tr 1 deltas check 0");
    fe_assert_equal($deltas[1][1], 0, "Tr 1 deltas check 1");
    fe_assert_equal($deltas[1][2], -50 * 70, "Tr 1 deltas check 2");

    fe_assert_equal($deltas[2][0], 50, "Tr 2 deltas check 0");
    fe_assert_equal($deltas[2][1], 0, "Tr 2 deltas check 1");
    fe_assert_equal($deltas[2][2], -50, "Tr 2 deltas check 2");

    $member_sums = $result["member_sums"];
    fe_assert_equal($member_sums[0], -100 + 50*70 + 50, "Member sums check 0");
    fe_assert_equal($member_sums[1], 0, "Member sums check 1");
    fe_assert_equal($member_sums[2],  100 - 50*70 - 50, "Member sums check 2");

    $avg_spendings = $result["avg_spendings"];
    fe_assert_equal($avg_spendings, 14400, "Average spendings");

    fe_uprint("fe_test_currency PASSED");
}


/**
 * Check that depts are not affect other users with non-negative weights
 **/
function fe_test_depts() {
    $sheet_data = array(
        "members" => array(
            "Вася",
            "Петя",
            "Костя",
        ),
        "exchange_rates" => array(
            "RUR" => 1,
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "200",
                    "300",
                ),
                "spent" => array(
                    "1",
                    "1",
                    "1",
                ),
            ),
            array(
                "currency" => "RUR",
                "charges" => array(
                    "-50",
                    "50",
                    "0",
                ),
                "spent" => array(
                    "1",
                    "1",
                    "1",
                ),
            ),
        ),
    );
    $result = fe_calc_sheet($sheet_data);

    $member_sums = $result["member_sums"];
    fe_assert_equal($member_sums[0], -100 - 50, "Member sums check 0");
    fe_assert_equal($member_sums[1], 0 + 50, "Member sums check 1");
    fe_assert_equal($member_sums[2], 100 + 0, "Member sums check 2");

    fe_uprint("fe_test_depts PASSED");
}


function fe_test_avg_spendings() {
    $sheet_data = array(
        "members" => array(
        ),
        "transactions" => array(
        ),
    );
    $result = fe_calc_sheet($sheet_data);
    $avg_spendings = $result["avg_spendings"];
    fe_assert_equal($avg_spendings, 0, "Average spendings");

    fe_uprint("fe_test_avg_spendings PASSED");
}


function fe_test_calculate_sheet_diff() {
    $old_sheet_data = array(
        "members" => array(
            "Васян",
            "Петро",
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "2000",
                    "300",
                ),
            ),
            array(
                "currency" => "USD",
                "charges" => array(
                    "10",
                    "20",
                    "30",
                ),
            ),
        ),
    );
    $sheet_data = array(
        "members" => array(
            "Вася",
            "Петя",
        ),
        "transactions" => array(
            array(
                "currency" => "RUR",
                "charges" => array(
                    "100",
                    "200",
                    "300",
                ),
            ),
            array(
                "currency" => "USD",
                "charges" => array(
                    "10",
                    "20",
                    "30",
                ),
            ),
        ),
    );

    $timestamp = 1234567890;
    $timestamp_str = "2009-02-14 02:31:30";
    fe_calculate_sheet_diff($old_sheet_data, $sheet_data, $timestamp);
    fe_assert_equal($sheet_data["transactions"][0][FE_KEY_TIMESTAMP_MODIFIED], $timestamp_str, "Timestamp does not match");
    fe_assert_equal(array_key_exists(FE_KEY_TIMESTAMP_MODIFIED, $sheet_data["transactions"][1]), false, "Timestamp should not exist here");
    fe_assert_equal($sheet_data[FE_KEY_TIMESTAMP_MODIFIED], $timestamp_str, "Sheet timestamp does not match");

    $empty_sheet_data = array();
    fe_calculate_sheet_diff(array(), $empty_sheet_data, $timestamp);
    fe_assert_equal(array_key_exists(FE_KEY_TIMESTAMP_MODIFIED, $empty_sheet_data), false, "Empty sheet timestamp does not match");

    fe_uprint("fe_test_calculate_sheet_diff PASSED");
}


function fe_test_action_update_sheet() {
    $sheet_id = "test";
    fe_remove_sheet($sheet_id);

    // arbitrary fixed timestamps in the past
    $tr0_ts = "2017-01-01 11:20:00";
    $tr1_ts = "2017-01-01 12:30:00";

    $request = array(
        // tran 0
        "tr0_0" => "100",
        "tr0_1" => "200",
        "tr0_2" => "300",

        "sp0_0" => "1",
        "sp0_1" => "0.5",
        "sp0_2" => "1",

        "ts0" => $tr0_ts,

        // tran 1
        "tr1_0" => "0",
        "tr1_1" => "200",
        "tr1_2" => "300",

        "sp1_0" => "0",
        "sp1_1" => "1",
        "sp1_2" => "1",

        "ts0" => $tr1_ts,

    );

    fe_action_update_sheet($sheet_id, $request);
    $sheet_data = fe_load_sheet($sheet_id);
    $transactions = $sheet_data["transactions"];
    // submisson was done with empty sheet, so diff calcer should override timestamps
    // and they cannot be equal.
    $new_tr0_ts = $transactions[0][FE_KEY_TIMESTAMP_MODIFIED];
    $new_tr1_ts = $transactions[1][FE_KEY_TIMESTAMP_MODIFIED];
    fe_assert_inequal($new_tr0_ts, $tr0_ts, "Timestamps cannot match here (0th transaction)");
    fe_assert_inequal($new_tr1_ts, $tr1_ts, "Timestamps cannot match here (1st transaction)");

    // update sheet second time without modifications,
    // should get zero diff
    fe_action_update_sheet($sheet_id, $request);
    $sheet_data = fe_load_sheet($sheet_id);
    $transactions = $sheet_data["transactions"];

    $zerodiff_tr0_ts = $transactions[0][FE_KEY_TIMESTAMP_MODIFIED];
    $zerodiff_tr1_ts = $transactions[1][FE_KEY_TIMESTAMP_MODIFIED];

    fe_assert_equal($new_tr0_ts, $zerodiff_tr0_ts, "Timestamps should match here (0th transaction)");
    fe_assert_equal($new_tr1_ts, $zerodiff_tr1_ts, "Timestamps should match here (1st transaction)");

    fe_uprint("fe_test_action_update_sheet PASSED");
}


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Финансовый коммунизм :: Тесты</title>
</head>
<body>
<pre>
<?php

fe_uprint("equalizer unittest STARTED");
fe_test_saveload();
fe_test_deltas();
fe_test_currency();
fe_test_depts();
fe_test_avg_spendings();
fe_test_calculate_sheet_diff();
fe_test_action_update_sheet();
fe_uprint("equalizer unittest FINISHED");
?>
</pre>
</body>
</html>