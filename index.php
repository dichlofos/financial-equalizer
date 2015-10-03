<?php

define('TR_EXPENSE', 'exp');
define('TR_INTERNAL', 'int');

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


function fe_delete_sheet($sheet_id) {
    rename("data/$sheet_id.json", "data/$sheet_id.deleted.json");
}


function fe_new_sheet() {
    global $PHP_SELF;
    $sheet_id = rand().'-'.rand().'-'.rand();
    ?>
    <form method="post" action="<?php echo $PHP_SELF; ?>?action=new_sheet">
        <input type="hidden" id="sheet_id" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <button type="submit" class="btn btn-default">Создать новый лист</button>
    </form><?php
}


function fe_currency_selector($currency, $id) {
    $currencies = array(
        'RUR',
        'USD',
        'EUR',
    );
    echo "<select name=\"$id\">";
    foreach ($currencies as $curr) {
        $selected = ($curr == $currency) ? ' selected="selected" ' : '';
        echo "<option value=\"$curr\"$selected>$curr</option>\n";
    }
    echo "</select>";
}


function fe_get_charge($transaction, $member_id) {
    $charges = fe_get_or($transaction, "charges", array());
    $charge = fe_get_or($charges, $member_id, "0");
    $charge_int = (integer)($charge);
    return $charge_int;
}


function fe_get_spent($transaction, $member_id) {
    $spent = fe_get_or($transaction, "spent", array());
    $member_spent = fe_get_or($spent, $member_id);
    return ($member_spent == "yes");
}


function fe_print_transaction_input($members, $transaction_id, $transaction, $transaction_deltas) {
    $currency = $transaction["currency"];
    $description = fe_get_or($transaction, "description");

    echo "<tr>";
    echo "<td>";
    echo "<input class=\"form-control input-sm transaction-description\" name=\"dtr${transaction_id}\" value=\"$description\" type=\"text\" />";
    echo "<td>";
    fe_currency_selector($currency, "cur$transaction_id");
    echo "</td>\n ";
    $transaction_sum = 0;
    foreach ($members as $member_id => $member_name) {
        $delta = $transaction_deltas[$member_id];
        echo "<td>";
        $charge_int = fe_get_charge($transaction, $member_id);
        $transaction_sum += $charge_int;
        echo "<input class=\"form-control input-sm amount\" name=\"tr${transaction_id}_${member_id}\" value=\"$charge_int\" type=\"text\" />";
        $spent_checked = fe_get_spent($transaction, $member_id) ? " checked=\"checked\" " : "";
        echo "&nbsp;<input class=\"spent\" name=\"sp${transaction_id}_${member_id}\" value=\"yes\" $spent_checked type=\"checkbox\" />";
        echo "</td>\n ";
    }
    echo "<td>$transaction_sum</td>\n ";
    echo "</tr>";
}

function fe_edit_sheet($sheet_id) {
    global $PHP_SELF; ?>
    <div class="sheet-link">
        Идентификатор листа: <a href="<?php echo $PHP_SELF; ?>"><?php echo $sheet_id; ?></a><br />
        С вопросами и предложениями обращаться <a href="mailto:dichlofos-mv@yandex.ru">к автору</a>.
        Исходники <a href="https://bitbucket.org/dichlofos/financial-equalizer">на Ведре</a>
    </div><?php
    $sheet_data = fe_load_sheet($sheet_id);
    $members = $sheet_data["members"];
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $exchange_rates = fe_get_or($sheet_data, "exchange_rates", array());

    $deltas = array();
    $member_sums = array();
    foreach ($members as $member_id => $member_name) {
        $member_sums[$member_id] = 0;
    }

    foreach ($transactions as $transaction_id => $transaction) {
        // calc transaction sum and spenders count
        $transaction_sum = 0;
        $spenders = 0;
        foreach ($members as $member_id => $member_name) {
            $transaction_sum += fe_get_charge($transaction, $member_id);
            if (fe_get_spent($transaction, $member_id)) {
                ++$spenders;
            }
        }
        $deltas[$transaction_id] = array();
        // charge - average spending
        foreach ($members as $member_id => $member_name) {
            $own_good = fe_get_spent($transaction, $member_id) ? ($transaction_sum / $spenders) : 0;
            $delta = fe_get_charge($transaction, $member_id) - $own_good;
            $deltas[$transaction_id][$member_id] = $delta;
            $member_sums[$member_id] += $delta;
        }
    }

    ?>

    <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_member">
        <div class="form-group">
            <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
            <label for="member_name">Новый участник:&nbsp;</label>
            <input type="text" class="form-control" name="member_name" value="" placeholder="Иван Человеков" />
            <button type="submit" class="btn btn-primary">Добавить участника</button>
        </div>
    </form>

    <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_transaction">
        <div class="form-group">
        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <label for="description">Новая статья расходов:&nbsp;</label>
        <input class="form-control" type="text" name="description" value="" />
        <button type="submit" class="btn btn-primary">Добавить</button>
        </div>
    </form>
    <form class="form-inline" method="post" action="<?php echo $PHP_SELF; ?>?action=update_sheet">
        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <label>Участники:</label><br/><?php
        foreach ($members as $member_id => $member_name) {
            echo "<div class=\"form-group member-list\"><label for=\"m$member_id\" style=\"width: 30px\">$member_id:&nbsp;</label>";
            echo "<input class=\"form-control\" type=\"text\" name=\"m$member_id\" value=\"$member_name\" /></div>\n";
        }
        ?><br/>
        <label>Курсы валют:&nbsp;</label><br/><?php
        foreach ($exchange_rates as $currency => $rate) {
            echo "<div class=\"form-group member-list\"><label for=\"e$currency\" style=\"width: 40px\">$currency:&nbsp;</label>";
            echo "<input class=\"form-control rate\" type=\"text\" name=\"e$currency\" value=\"$rate\" /></div>\n";
        }

        echo "<table class=\"table table-condensed\" style=\"margin-top: 10px\">";
        echo "<tr>";
        echo "<th>Статья расхода или сбора</th>";
        echo "<th>Валюта</th>";
        foreach ($members as $member_id => $member_name) {
            echo "<th>$member_name</th>\n ";
        }
        echo "<th class=\"sum\">Сумма</th>\n";
        echo "</tr>";

        foreach ($transactions as $transaction_id => $transaction) {
            fe_print_transaction_input($members, $transaction_id, $transaction, $deltas[$transaction_id]);
        }
        // Total
        echo "<tr class=\"info\">";
        echo "<td>Итого</td>";
        echo "<td>&nbsp;</td>";
        foreach ($members as $member_id => $member_name) {
            $member_sum = $member_sums[$member_id];
            $member_sum_rounded = (integer)($member_sum * 100) / 100;
            echo "<td>$member_sum_rounded</td>\n ";
        }
        echo "<td>&nbsp;</td>\n";
        echo "</tr>";
        ?>
        </table>
        <div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
        </div>
    </form>

    <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=delete_sheet">
        <div class="form-group">
            <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
            <button type="submit" class="btn btn-danger">Удалить лист</button>
        </div>
    </form>


    <?php
}

$sheet_id = fe_get_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/[^0-9a-f-]/", "", $sheet_id);
$action = fe_get_or($_REQUEST, "action");
$member_name = fe_get_or($_REQUEST, "member_name");
$description = fe_get_or($_REQUEST, "description");

if ($action == "new_sheet") {
    $sheet_data = array();
    $members = array();
    $members["1"] = "Вася";
    $members["2"] = "Петя";
    $members["3"] = "Макс";
    $sheet_data["members"] = $members;
    $transactions = array();
    $transactions["1"] = array(
        "type"=>TR_EXPENSE,
        "currency"=>"RUR",
        "description"=>"Трансфер из А в Б",
        "charges"=>array(
            "1"=>"1000",
            "2"=>"500",
        ),
        "spent"=>array(
            "3"=>"yes",
            "2"=>"yes",
        ),
    );
    $sheet_data["transactions"] = $transactions;
    $sheet_data["exchange_rates"] = array(
        'USD'=>"67",
        'RUR'=>"1",
        'EUR'=>"77",
    );
    fe_save_sheet($sheet_id, $sheet_data);
    echo $PHP_SELF;
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "update_sheet") {
    if (fe_empty($sheet_id)) {
        die("Invalid request: sheet_id is empty");
    }
    //fe_print($_REQUEST);
    $sheet_data = array();
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
        } elseif (fe_startswith($key, "m")) {
            if (fe_empty($value)) {
                continue;  // skip empty members
            }
            $member_id = substr($key, 1);
            $members[$member_id] = $value;
        } elseif (fe_startswith($key, "e")) {
            $currency = substr($key, 1);
            $exchange_rates[$currency] = $value;
        }
    }
    $sheet_data["transactions"] = $transactions;
    $sheet_data["members"] = $members;
    $sheet_data["exchange_rates"] = $exchange_rates;
    //fe_print($transactions);
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_member") {
    $sheet_data = fe_load_sheet($sheet_id);
    $members = $sheet_data["members"];
    $members[] = $member_name;
    $sheet_data["members"] = $members;
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "delete_sheet") {
    fe_delete_sheet($sheet_id);
    header("Location: /");
    exit();
} elseif ($action == "add_transaction") {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_data["transactions"][] = array(
        "description"=>$description,
        "currency"=>"RUR",
        "type"=>TR_EXPENSE,
    );
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif (fe_not_empty($action)) {
    die("Unknown action: '$action'");
}

?><!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Финансовый коммунизм</title>
        <link rel="stylesheet" href="/static/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="/static/communism/css/main.css" />
        <script src="/static/jquery/jquery-1.11.3.min.js"></script>
        <script src="/static/bootstrap/js/bootstrap.js"></script>
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