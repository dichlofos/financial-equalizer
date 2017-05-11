<?php
$engine_dir = "engine/";

require_once("${engine_dir}sys/string.php");
require_once('utils.php');
require_once('static_config.php');
require_once('equalizer.php');
require_once('action_handlers.php');
require_once('new_sheet.php');
require_once('edit_sheet.php');
require_once('export.php');

session_start();

// Parse some global variables
$sheet_id = xcms_get_key_or($_REQUEST, "sheet_id");
$sheet_id = preg_replace("/[^0-9a-f-]/", "", $sheet_id);
if (xu_empty($sheet_id)) {
    // use sheet id from session if available
    $session_sheet_id = xcms_get_key_or($_SESSION, "sheet_id");
    if (xu_not_empty($session_sheet_id)) {
        $sheet_id = $session_sheet_id;
    }
}

$action = xcms_get_key_or($_REQUEST, "action");

// TODO: wrap into PSS (or refactor out PSS from lesh repo)
$member_id_filter = xcms_get_key_or($_REQUEST, "member_id_filter");
if (xu_empty($member_id_filter)) {
    // use member filter from session if available
    $session_member_filter_id = xcms_get_key_or($_SESSION, "member_id_filter");
    if (xu_not_empty($session_member_filter_id)) {
        $member_id_filter = $session_member_filter_id;
    }
}

if ($action == "new_sheet") {
    fe_action_new_sheet($sheet_id);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "update_sheet") {
    if (xu_empty($sheet_id)) {
        die("Invalid request: sheet_id is empty");
    }
    fe_action_update_sheet($sheet_id, $_REQUEST);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_member") {
    $member_name = xcms_get_key_or($_REQUEST, "member_name");
    $member_name = trim($member_name);
    if (xu_not_empty($member_name)) {
        // allow non-empty member names only
        $sheet_data = fe_load_sheet($sheet_id);
        $sheet_data["members"][] = $member_name;
        $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = fe_datetime();
        fe_save_sheet($sheet_id, $sheet_data);
    }
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "add_currency") {
    $currency = xcms_get_key_or($_REQUEST, "currency");
    $currency = trim($currency);
    if (xu_not_empty($currency)) {
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
    $description = xcms_get_key_or($_REQUEST, "description");
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
} elseif ($action == "set_sheet_title") {
    $title = xcms_get_key_or($_REQUEST, "title");
    $title = trim($title);
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_data["title"] = $title;
    $sheet_data[FE_KEY_TIMESTAMP_MODIFIED] = fe_datetime();
    fe_save_sheet($sheet_id, $sheet_data);
    header("Location: /?sheet_id=$sheet_id");
    exit();
} elseif ($action == "export") {
    $format = xcms_get_key_or($_REQUEST, "format");
    $sheet_data = fe_load_sheet($sheet_id);
    if ($format == "csv") {
        fe_export_sheet_to_csv($sheet_data);
    } else {
        die("Invalid format: $format");
    }
    exit();

} elseif (xu_not_empty($action)) {
    die("Unknown action: '$action'");
}

$sheet_title_ht_title = "";
if (xu_not_empty($sheet_id)) {
    // FIXME(mvel): Multiple read per page
    $sheet_data = fe_load_sheet($sheet_id);
    $sheet_title = xcms_get_key_or($sheet_data, "title");
    if (xu_not_empty($sheet_title)) {
        $sheet_title_ht_title = htmlspecialchars($sheet_title)." &mdash; ";
    }
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $sheet_title_ht_title; ?>Финансовый коммунизм</title>
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

$host = xcms_get_key_or($_SERVER, "HTTP_HOST");
if (strpos($host, "communism.dmvn.net") !== false) {?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" src="/static/communism/js/metrika.js"></script>
    <noscript><div><img src="https://mc.yandex.ru/watch/32864640" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
<?php
}?>
    <div class="sheet-link">
        <?php
        if (xu_not_empty($sheet_id)) {
            $modified = xcms_get_key_or($sheet_data, FE_KEY_TIMESTAMP_MODIFIED);
            if (xu_empty($modified)) {
                $modified = "&mdash;";
            }
        ?>
            Поделиться листом: <a href="/?sheet_id=<?php echo $sheet_id; ?>"><?php echo $sheet_id; ?></a><br />
            Последняя модификация: <?php echo $modified; ?><br />

            Экспорт <a href="/?action=export&amp;format=csv&amp;sheet_id=<?php echo $sheet_id; ?>">в CSV</a><br />
            <?php
        }
        ?>
    </div><?php

if (xu_empty($sheet_id)) {
    fe_new_sheet();
} else {
    fe_edit_sheet($sheet_id, $member_id_filter);
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
