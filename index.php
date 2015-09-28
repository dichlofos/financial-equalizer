<?php

define('TR_EXPENSE', 'exp');
define('TR_INTERNAL', 'int');

define('CUR_RUR', 'rur');
define('CUR_USD', 'usd');


function fe_print($object) {
    echo "<pre>";
    print_r($object);
    echo "</pre>";
}

function fe_get_or($array, $key, $default_value = "") {
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default_value;
}

function fe_empty($value) {
    return strlen($value) == 0;
}

function fe_not_empty($value) {
    return strlen($value) > 0;
}

function fe_startswith($str, $prefix) {
    return (substr($str, 0, strlen($prefix)) == $prefix);
}

function fe_save_sheet($sheet_id, $sheet_data) {
    $sheet_f = fopen("data/$sheet_id.json", "w");
    fwrite($sheet_f, json_encode($sheet_data));
    fclose($sheet_f);
}

function fe_load_sheet($sheet_id) {
    $sheet_data = file_get_contents("data/$sheet_id.json");
    return json_decode($sheet_data, true);
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


function fe_currency_selector($currency, $id) {
    echo "<select name=\"$id\">";
    $currencies = array(
        CUR_RUR,
        CUR_USD,
    );
    foreach ($currencies as $curr) {
        $selected = ($curr == $currency) ? ' selected="selected" ' : '';
        echo "<option value=\"$curr\"$selected>$curr</option>\n";
    }
    echo "</select>";
}

function fe_print_transaction_input($members, $transaction_id, $transaction) {
    //print_r($transaction);
    $currency = $transaction["currency"];
    echo "<tr>";
    echo "<td>";
    fe_currency_selector($currency, "cur$transaction_id");
    echo "</td>\n ";
    foreach ($members as $member_id => $member_name) {
        $charges = fe_get_or($transaction, "charges", array());
        $value = fe_get_or($charges, $member_id, "0");
        echo "<td><input class=\"amount\" name=\"tr${transaction_id}_${member_id}\" value=\"$value\" type=\"text\" /></td>\n ";
    }
    echo "</tr>";
}


function fe_edit_sheet($sheet_id) {
    global $PHP_SELF;
    echo "Идентификатор листа: <b>$sheet_id</b><br />";
    $sheet_data = fe_load_sheet($sheet_id);
    $members = $sheet_data["members"];
    $transactions = fe_get_or($sheet_data, "transactions", array());
    ?>
    <form method="post" action="<?php echo $PHP_SELF; ?>?action=update_sheet">
    <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" /><?php
    foreach ($members as $member_id => $member_name) {
        echo "<div><b>$member_id:</b> <input type=\"text\" name=\"m$member_id\" value=\"$member_name\" /></div>\n";
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
    }?>
    </table>
    <div>
        <input type="submit" value="Сохранить" />
    </div>
    </form><?php

    fe_print($sheet_data);
}

$sheet_id = fe_get_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/[^0-9a-f-]/", "", $sheet_id);
$action = fe_get_or($_REQUEST, "action");

if ($action == "new_sheet") {
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
    fe_save_sheet($sheet_id, $sheet_data);
    echo $PHP_SELF;
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "update_sheet") {
    if (fe_empty($sheet_id)) {
        die("Invalid request: sheet_id is empty");
    }
    fe_print($_REQUEST);
    $sheet_data = array();
    $transactions = array();
    $members = array();
    foreach ($_REQUEST as $key => $value) {
        if (fe_startswith($key, "tr")) {
            $amount_key = substr($key, 2);
            $amount_key = explode("_", $amount_key);
            $transaction_id = $amount_key[0];
            $member_id = $amount_key[1];
            $transactions[$transaction_id]["charges"][$member_id] = $value;
        } elseif (fe_startswith($key, "cur")) {
            $transaction_id = substr($key, 3);
            $transactions[$transaction_id]["currency"] = $value;
        } elseif (fe_startswith($key, "m")) {
            $member_id = substr($key, 1);
            $members[$member_id] = $value;
        }
    }
    $sheet_data["transactions"] = $transactions;
    $sheet_data["members"] = $members;
    fe_print($transactions);
    fe_save_sheet($sheet_id, $sheet_data);
    echo $PHP_SELF;
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif (fe_not_empty($action)) {
    die("Unknown action: '$action'");
}

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Financial Equalizer</title>
<style>
input.amount {
    width: 80px;
}
</style>
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