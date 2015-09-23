<?php

define('TR_EXPENSE', 'exp');
define('TR_INTERNAL', 'int');

define('CUR_RUR', 'rur');
define('CUR_USD', 'usd');


function fe_get_or($array, $key, $default_value = "") {
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default_value;
}

function fe_empty($value) {
    return strlen($value) == 0;
}

function fe_new_sheet() {
    global $PHP_SELF;
    $sheet_id = rand().'-'.rand().'-'.rand();
    ?>
    <form method="post" action="<?php echo $PHP_SELF; ?>?action=new_sheet">
        <input type="hidden" id="sheet_id" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <input type="submit" value="Создать новый лист" />
    </form><?php
}

function fe_print_transaction_input($members, $transaction_id, $transaction) {
    print("TID: $transaction_id\n");
    $currency = $transaction["currency"];
    echo "<tr>";
    echo "<td>$currency</td>\n ";
    foreach ($members as $member_id => $member_name) {
        $charges = fe_get_or($transaction, "charges", array());
        $value = fe_get_or($charges, $member_id, "0");
        echo "<td><input name=\"tr${transaction_id}_${member_id}\" value=\"$value\" type=\"text\" /></td>\n ";
    }
    echo "</tr>";
    //print_r($transaction);
}


function fe_edit_sheet($sheet_id) {
    global $PHP_SELF;
    echo "Идентификатор листа: <b>$sheet_id</b><br />";
    $sheet_data = file_get_contents("data/$sheet_id.json");
    $sheet_data = json_decode($sheet_data, true);
    $members = $sheet_data["members"];
    $transactions = fe_get_or($sheet_data, "transactions", array());
    ?>
    <form action="<?php echo $PHP_SELF; ?>?action=update_sheet"> <?php
    foreach ($members as $member_id => $member_name) {
        print("$member_id $member_name<br/>");
    }
    echo "<table>";

    echo "<tr>";
    echo "<th>&nbsp;</th>";
    foreach ($members as $member_id => $member_name) {
        echo "<th>$member_name</th>\n ";
    }
    echo "</tr>";

    foreach ($transactions as $transaction_id => $transaction) {
        fe_print_transaction_input($members, $transaction_id, $transaction);
    }
    echo "</table>";
    echo "</form>";

    print_r($sheet_data);
    print_r($transactions);
}

$sheet_id = fe_get_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/[^0-9a-f-]/", "", $sheet_id);
$action = fe_get_or($_REQUEST, "action");

if ($action == "new_sheet") {
    $sheet_f = fopen("data/$sheet_id.json", "w");
    $sheet_data = array();
    $members = array();
    $members["1"] = "one";
    $members["2"] = "Two";
    $members["3"] = "threE";
    $sheet_data["members"] = $members;
    $transactions = array();
    $transactions["1"] = array(
        "type"=>TR_EXPENSE,
        "currency"=>CUR_RUR,
        "charges"=>array(
            "1"=>"1000",
            "2"=>"500",
        ),
    );
    $transactions["2"] = array(
        "type"=>TR_EXPENSE,
        "currency"=>CUR_USD,
        "charges"=>array(
            "1"=>"50",
            "2"=>"100",
        ),
    );
    $sheet_data["transactions"] = $transactions;


    fwrite($sheet_f, json_encode($sheet_data));
    fclose($sheet_f);
    echo $PHP_SELF;
    header("Location: /?sheet_id=$sheet_id");
    exit();
}

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Financial Equalizer</title>
</head>
<body><?php

if (fe_empty($sheet_id)) {
    fe_new_sheet();
} else {
    fe_edit_sheet($sheet_id);
}
?>
</body>
</html>