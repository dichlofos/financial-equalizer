<?php
require_once('equalizer.php');

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
        ),
    );
    $result = fe_calc_sheet($sheet_data);
    fe_print("fe_test_deltas PASSED");
}

fe_print("equalizer unittest STARTED");
fe_test_deltas();
fe_print("equalizer unittest FINISHED");
