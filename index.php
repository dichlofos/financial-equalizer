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


function fe_new_sheet() {
    global $PHP_SELF;
    $sheet_id = rand().'-'.rand().'-'.rand();
    ?>
    <form method="post" action="<?php echo $PHP_SELF; ?>?action=new_sheet">
        <input type="hidden" id="sheet_id" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <button type="submit" class="btn btn-warning">Создать новый лист</button>
    </form><?php
}


function fe_currency_selector($currency, $id, $exchange_rates) {
    echo "<select class=\"form-control input-sm currency-select\" name=\"$id\">";
    foreach ($exchange_rates as $curr => $rate) {
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


function fe_print_transaction_input($members, $transaction_id, $transaction, $transaction_deltas, $exchange_rates) {
    $currency = fe_get_currency($transaction);
    $description = fe_get_or($transaction, "description");

    echo "<tr>";
    echo "<td>";
    echo "<input class=\"form-control input-sm transaction-description\" name=\"dtr${transaction_id}\" value=\"$description\" type=\"text\"
        title=\"Товар или оказанная услуга\" placeholder=\"Трансфер из пункта А в пункт Б\" />";
    echo "<td>";
    fe_currency_selector($currency, "cur$transaction_id", $exchange_rates);
    echo "</td>\n ";
    $transaction_sum = 0;
    foreach ($members as $member_id => $member_name) {
        // TODO: fix at http://fe.local/?sheet_id=344552942-1662170856-73040037
        $delta = $transaction_deltas[$member_id];
        echo "<td>";
        $charge_int = fe_get_charge($transaction, $member_id);
        $transaction_sum += $charge_int;
        echo "<input class=\"form-control input-sm amount\" name=\"tr${transaction_id}_${member_id}\" value=\"$charge_int\" type=\"text\"
            title=\"Сколько потратил данный участник в указанной валюте\" />";
        $member_spent = fe_get_spent($transaction, $member_id);
        $spent_class = "no-use";
        if ($member_spent > 0.01 && $member_spent < 0.99) {
            $spent_class = "lower-use";
        } elseif ($member_spent >= 0.99 && $member_spent <= 1.01) {
            $spent_class = "normal-use";
        } elseif ($member_spent > 1.01) {
            $spent_class = "high-use";
        }
        echo "&nbsp;<input class=\"form-control input-sm spent $spent_class\" name=\"sp${transaction_id}_${member_id}\" value=\"$member_spent\" type=\"text\"
            title=\"Коэффициент пользования данной услугой для данного участника\" />";
        echo "</td>\n ";
    }
    echo "<td>$transaction_sum</td>\n ";
    echo "</tr>";
}

function fe_edit_sheet($sheet_id) {
    global $PHP_SELF;
    $sheet_data = fe_load_sheet($sheet_id);
    $members = fe_get_or($sheet_data, "members", array());
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $exchange_rates = fe_get_or($sheet_data, "exchange_rates", array());

    $deltas = array();
    $member_sums = array();
    foreach ($members as $member_id => $member_name) {
        $member_sums[$member_id] = 0;
    }
    $all_transactions_sum = 0;
    $norm_error = false;
    foreach ($transactions as $transaction_id => $transaction) {
        $transaction_currency = strtoupper(fe_get_currency($transaction));
        $rate = (integer)fe_get_or($exchange_rates, $transaction_currency, "1");
        // calc transaction sum and spenders count
        $transaction_sum = 0;
        $lambda_norm = 0.0;
        foreach ($members as $member_id => $member_name) {
            $transaction_sum += fe_get_charge($transaction, $member_id) * $rate;
            $lambda_norm += fe_get_spent($transaction, $member_id);
        }
        if ($lambda_norm < 0.01) {
            $norm_error = true;
            continue;
        }
        $all_transactions_sum += $transaction_sum;

        $deltas[$transaction_id] = array();

        // charge - average spending
        foreach ($members as $member_id => $member_name) {
            $own_good = $transaction_sum * fe_get_spent($transaction, $member_id) / $lambda_norm;
            $delta = fe_get_charge($transaction, $member_id) * $rate - $own_good;
            $deltas[$transaction_id][$member_id] = $delta;
            $member_sums[$member_id] += $delta;
        }
    }

    ?>

    <div class="row">
        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_member">
                <div class="form-group">
                    <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                    <label for="member_name">Новый участник:&nbsp;</label>
                    <input type="text" class="form-control" name="member_name" value="" placeholder="Иван Человеков" />
                    <button type="submit" class="btn btn-primary">Добавить участника</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_currency">
                <div class="form-group">
                <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                <label for="description">Новая валюта:&nbsp;</label>

                <select class="form-control" name="currency"><?php
                $all_currencies = array(
                    "EUR",
                    "KGS",
                    "RUR",
                    "USD",
                    "UZS",
                );
                foreach ($all_currencies as $curr) {
                    echo "<option value=\"$curr\">$curr</option>\n";
                }?>
                </select>
                <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">&nbsp;</div>
    </div>

    <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_transaction">
        <div class="form-group">
        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <label for="description">Новая статья расходов:&nbsp;</label>
        <input class="form-control" type="text" name="description" value="" placeholder="Скинулись на шаурму" />
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

        if ($norm_error) {?>
        <div class="warning">
            В ведомости присутствуют статьи расходов, которые ни на кого не были потрачены (выделены цветом)
        </div><?php
        }?>

        <table class="table table-condensed" style="margin-top: 10px">
        <tr>
        <th class="non-member">Статья расхода или сбора</th>
        <th class="non-member">Валюта</th><?php
        foreach ($members as $member_id => $member_name) {
            echo "<th>$member_name</th>\n";
        }
        ?>
        <th class="non-member">Сумма</th>
        </tr>
        <?php
        foreach ($transactions as $transaction_id => $transaction) {
            fe_print_transaction_input(
                $members,
                $transaction_id,
                $transaction,
                fe_get_or($deltas, $transaction_id, array()),
                $exchange_rates
            );
        }
        // Total
        ?>
        <tr class="info">
            <td>Итоговые расходы участника (RUR)</td>
            <td>&nbsp;</td>
        <?php
        foreach ($members as $member_id => $member_name) {
            $member_sum = $member_sums[$member_id];
            $member_sum_rounded = (integer)($member_sum * 100) / 100;
            echo "<td>$member_sum_rounded</td>\n ";
        }
        $avg_spendings = ((integer)(100.0 * $all_transactions_sum / count($members))) / 100;
        echo "<td><b>$all_transactions_sum</b> ($avg_spendings&nbsp;/&nbsp;<i>чел</i>)</td>\n";
        echo "</tr>";
        ?>
        </table>
        <div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
        </div>
    </form>

    <div class="tip">
    <?php
    $tip_rnd = rand(0, 1);
    echo "<b>Совет $tip_rnd</b>: ";
    if ($tip_rnd == 0) {?>
        Сборы в &laquo;кассу&raquo; удобно считать следующим образом. Всем, кто сдавал деньги, пишем их как обычные расходы,
        а кассиру пишем суммарное количество этих собранных денег со знаком <b>минус</b>. Таким образом, сумма по данной транзакции
        в правой колонке будет равна нулю. Точно так же можно учитывать, например, событие типа &laquo;Вася одолжил у Пети 500р на
        пиво и мороженое&raquo;: Васе пишем&nbsp;-500, а Пете&nbsp;&#8212;&nbsp;500.<?php
    } elseif ($tip_rnd == 1) {?>
        Чтобы удалить валюту, сотрите её курс обмена и нажмите Enter. Чтобы удалить участника, сотрите его имя и нажмите Enter.
        При удалении участника все суммы, связанные с ним, также будут удалены!
        <?php
    }?>
    </div>

    <div class="row">
        <div class="col-md-12">
            <form method="post" class="form-inline" action="/?action=clear_session">
                <div class="form-group">
                    <button type="submit" class="btn btn-warning">На главную</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

session_start();

$sheet_id = fe_get_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/[^0-9a-f-]/", "", $sheet_id);
if (fe_empty($sheet_id)) {
    // use sheet id from session if available
    $session_sheet_id = fe_get_or($_SESSION, "sheet_id");
    if (fe_not_empty($session_sheet_id)) {
        $sheet_id = $session_sheet_id;
    }
}

$action = fe_get_or($_REQUEST, "action");
$member_name = fe_get_or($_REQUEST, "member_name");
$description = fe_get_or($_REQUEST, "description");
$currency = fe_get_or($_REQUEST, "currency");
$currency = preg_replace("/[^A-Z]/", "", strtoupper($currency));

if ($action == "new_sheet") {
    $sheet_data = array();
    $members = array();
    $sheet_data["members"] = $members;
    $transactions = array();
    $sheet_data["transactions"] = $transactions;
    $sheet_data["exchange_rates"] = array(
        'USD'=>"67",
        'RUR'=>"1",
        'EUR'=>"77",
    );
    fe_save_sheet($sheet_id, $sheet_data);
    $_SESSION["sheet_id"] = $sheet_id;
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "update_sheet") {
    if (fe_empty($sheet_id)) {
        die("Invalid request: sheet_id is empty");
    }
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
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_member") {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_data["members"][] = $member_name;
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_currency") {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_data["exchange_rates"][$currency] = "1";
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "clear_session") {
    $_SESSION["sheet_id"] = "";
    header("Location: /");
    exit();
} elseif ($action == "add_transaction") {
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_data["transactions"][] = array(
        "description"=>$description,
        "currency"=>FE_DEFAULT_CURRENCY,
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
    <link rel="icon" sizes="128x128" type="image/png" href="/favicon.png">
    <link rel="icon" sizes="128x128" type="x-icon" href="/favicon.ico">
    <script src="/static/jquery/jquery-1.11.3.min.js"></script>
    <script src="/static/bootstrap/js/bootstrap.js"></script>
</head>
<body>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function (d, w, c) {
            (w[c] = w[c] || []).push(function() {
                try {
                    w.yaCounter32864640 = new Ya.Metrika({
                        id:32864640,
                        clickmap:true,
                        trackLinks:true,
                        accurateTrackBounce:true
                    });
                } catch(e) { }
            });

            var n = d.getElementsByTagName("script")[0],
                s = d.createElement("script"),
                f = function () { n.parentNode.insertBefore(s, n); };
            s.type = "text/javascript";
            s.async = true;
            s.src = "https://mc.yandex.ru/metrika/watch.js";

            if (w.opera == "[object Opera]") {
                d.addEventListener("DOMContentLoaded", f, false);
            } else { f(); }
        })(document, window, "yandex_metrika_callbacks");
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/32864640" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <div class="sheet-link">
        <?php
        if (fe_not_empty($sheet_id)) {?>
            Поделиться листом: <a href="/?sheet_id=<?php echo $sheet_id; ?>"><?php echo $sheet_id; ?></a><br /><?php
        }
        ?>
        С вопросами и предложениями обращаться <a href="mailto:dichlofos-mv@yandex.ru">к автору</a>.<br/>
        Исходный код <a href="https://bitbucket.org/dichlofos/financial-equalizer">на BitBucket</a>.
    </div><?php

if (fe_empty($sheet_id)) {
    fe_new_sheet();
} else {
    fe_edit_sheet($sheet_id);
}
$version = file_get_contents('version');
?>
    <div class="copyright">
        Financial Equalizer v<?php echo $version; ?> &copy; 2015&#8212;<?php echo date('Y'); ?>, Mikhail Veltishchev aka <a href="https://dichlofos.tumblr.com">DichlofoS</a>.
        All rights reversed. This software is provided AS IS, without any warranty about your data safety.
    </div>
</body>
</html>
