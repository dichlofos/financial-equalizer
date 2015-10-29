<?php
require_once('equalizer.php');

function fe_assert_equal($first, $second, $message) {
    if ($first != $second) {
        fe_print("Assertion failed: $first != $second [$message]");
    }
}

function fe_test_saveload() {

}

function fe_test_deltas() {
    $sheet_data = array(
        "members"=>array(
            "Вася",
            "Петя",
            "Костя",
        ),
        "transactions"=>array(
            array(
                "currency"=>"RUR",
                "charges"=>array(
                    "100",
                    "200",
                    "300",
                ),
                "spent"=>array(
                    "1",
                    "1",
                    "1",
                ),
            ),
            array(
                "currency"=>"RUR",
                "charges"=>array(
                    "50",
                    "200",
                    "350",
                ),
                "spent"=>array(
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

    fe_print("fe_test_deltas PASSED");
}

function fe_test_currency() {
    $sheet_data = array(
        "members"=>array(
            "Вася",
            "Петя",
            "Костя",
        ),
        "exchange_rates"=>array(
            "RUR"=>1,
            "EUR"=>70,
        ),
        "transactions"=>array(
            array(
                "currency"=>"RUR",
                "charges"=>array(
                    "100",
                    "200",
                    "300",
                ),
                "spent"=>array(
                    "1",
                    "1",
                    "1",
                ),
            ),
            array(
                "currency"=>"EUR",
                "charges"=>array(
                    "50",
                    "200",
                    "350",
                ),
                "spent"=>array(
                    "0",
                    "1",
                    "2.0",
                ),
            ),
            array(
                "currency"=>"USD",  // unknown currency
                "charges"=>array(
                    "50",
                    "200",
                    "350",
                ),
                "spent"=>array(
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

    fe_print("fe_test_currency PASSED");
}

function fe_test_avg_spendings() {
    $sheet_data = array(
        "members"=>array(
        ),
        "transactions"=>array(
        ),
    );
    $result = fe_calc_sheet($sheet_data);
    $avg_spendings = $result["avg_spendings"];
    fe_assert_equal($avg_spendings, 0, "Average spendings");

    fe_print("fe_test_avg_spendings PASSED");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Финансовый коммунизм :: Тесты</title>
</head>
<body>
<?php

fe_print("equalizer unittest STARTED");
fe_test_deltas();
fe_test_currency();
fe_test_avg_spendings();
fe_print("equalizer unittest FINISHED");
