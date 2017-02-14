<?php
require_once('utils.php');
require_once('static_config.php');
require_once('equalizer.php');

require_once('new_sheet.php');
require_once('edit_sheet.php');
require_once('export.php');

session_start();

// Parse some global variables
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

if ($action == "new_sheet") {
    $sheet_data = array();
    $members = array();
    $sheet_data["members"] = $members;
    $transactions = array();
    $sheet_data["transactions"] = $transactions;
    $sheet_data["exchange_rates"] = $FE_DEFAULT_EXCHANGE_RATES;
    fe_save_sheet($sheet_id, $sheet_data);
    // TODO(mvel): add creation date
    $_SESSION["sheet_id"] = $sheet_id;
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "update_sheet") {
    if (fe_empty($sheet_id)) {
        die("Invalid request: sheet_id is empty");
    }
    // FIXME(mvel): При сохранении листа теряется timestamp
    // (и вообще любые поля, которые не присутствуют в формах).
    // Выглядит так, что надо уметь накатывать update на существующий лист,
    // а не воссоздавать его с нуля.
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
    $sheet_old_data = fe_load_sheet($sheet_id);
    # issue #22: calculate transaction update times
    fe_calculate_sheet_diff($sheet_old_data, $sheet_data);

    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_member") {
    $member_name = fe_get_or($_REQUEST, "member_name");
    $member_name = trim($member_name);
    if (fe_not_empty($member_name)) {
        // allow non-empty member names only
        $sheet_data = fe_load_sheet($sheet_id);
        $sheet_data["members"][] = $member_name;
        $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = fe_datetime();
        fe_save_sheet($sheet_id, $sheet_data);
    }
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_currency") {
    $currency = fe_get_or($_REQUEST, "currency");
    $currency = trim($currency);
    if (fe_not_empty($currency)) {
        // allow non-empty currencies only
        $sheet_data = fe_load_sheet($sheet_id);
        $sheet_data["exchange_rates"][$currency] = "1";
        $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = fe_datetime();
        fe_save_sheet($sheet_id, $sheet_data);
    }
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "clear_session") {
    $_SESSION["sheet_id"] = "";
    header("Location: /");
    exit();
} elseif ($action == "add_transaction") {
    $description = fe_get_or($_REQUEST, "description");
    $sheet_data = fe_load_sheet($sheet_id);
    $timestamp_str = fe_datetime();
    $sheet_data["transactions"][] = array(
        "description" => $description,
        "currency" => FE_DEFAULT_CURRENCY,
        FE_KEY_TIMESTAMP_MODIFIED => $timestamp_str,
    );
    $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = $timestamp_str;
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "export") {
    $format = fe_get_or($_REQUEST, "format");
    $sheet_data = fe_load_sheet($sheet_id);
    if ($format == "csv") {
        fe_export_sheet_to_csv($sheet_data);
    } else {
        die("Invalid format: $format");
    }
    exit();

} elseif (fe_not_empty($action)) {
    die("Unknown action: '$action'");
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Финансовый коммунизм</title>
    <link rel="stylesheet" href="/static/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="/static/communism/css/main.css" />
    <link rel="icon" sizes="128x128" type="image/png" href="/favicon.png">
    <link rel="icon" sizes="128x128" type="image/x-icon" href="/favicon.ico">
    <script src="/static/jquery/jquery-1.11.3.min.js"></script>
    <script src="/static/bootstrap/js/bootstrap.js"></script>
    <script src="/static/communism/js/communism.js"></script>
</head>
<body>
<?php

$host = fe_get_or($_SERVER, "HTTP_HOST");
if (strpos($host, "communism.dmvn.net") !== false) {?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" src="/static/communism/js/metrika.js"></script>
    <noscript><div><img src="https://mc.yandex.ru/watch/32864640" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
<?php
}?>
    <div class="sheet-link">
        <?php
        if (fe_not_empty($sheet_id)) {
            // FIXME(mvel): Multiple read per page
            $sheet_data = fe_load_sheet($sheet_id);
            $modified = fe_get_or($sheet_data, FE_KEY_TIMESTAMP_MODIFIED);
            if (fe_empty($modified)) {
                $modified = "&ndash;";
            }
        ?>
            Поделиться листом: <a href="/?sheet_id=<?php echo $sheet_id; ?>"><?php echo $sheet_id; ?></a><br />
            Последняя модификация: <?php echo $modified; ?><br />

            Экспорт <a href="/?action=export&amp;format=csv&amp;sheet_id=<?php echo $sheet_id; ?>">в CSV</a><br />
            <?php
        }
        ?>
    </div><?php

if (fe_empty($sheet_id)) {
    fe_new_sheet();
} else {
    fe_edit_sheet($sheet_id);
}
$version = file_get_contents('version');
?>
    <div class="copyright">
        Financial Equalizer v<?php echo $version; ?> &copy; 2015&#8212;<?php echo date('Y'); ?>, <a href="https://vk.com/dichlofos">Mikhail Veltishchev</a>.
        С вопросами и предложениями обращаться <a href="mailto:dichlofos-mv@yandex.ru">к автору</a>.
        Исходный <a href="https://bitbucket.org/dichlofos/financial-equalizer">код</a>,
        список <a href="https://bitbucket.org/dichlofos/financial-equalizer/issues?status=new&amp;status=open">известных багов</a>.
        <br/>
        All rights reversed. This software is provided AS IS, without any warranty about your data safety.
    </div>
</body>
</html>
