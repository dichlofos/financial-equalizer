<?php
require_once('utils.php');
require_once('static_config.php');
require_once('equalizer.php');

require_once('new_sheet.php');
require_once('edit_sheet.php');

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
    $sheet_data["exchange_rates"] = $FE_DEFAULT_EXCHANGE_RATES;
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
        if (fe_not_empty($sheet_id)) {?>
            Поделиться листом: <a href="/?sheet_id=<?php echo $sheet_id; ?>"><?php echo $sheet_id; ?></a><br /><?php
        }
        ?>
        С вопросами и предложениями обращаться <a href="mailto:dichlofos-mv@yandex.ru">к автору</a>.<br/>
        Исходный код <a href="https://bitbucket.org/dichlofos/financial-equalizer">на BitBucket</a>,
        список <a href="https://bitbucket.org/dichlofos/financial-equalizer/issues?status=new&amp;status=open">известных багов</a>.
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
