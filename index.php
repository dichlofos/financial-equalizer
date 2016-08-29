<?php
require_once('utils.php');
require_once('equalizer.php');

function fe_new_sheet() {
    global $PHP_SELF;
    $sheet_id = rand().'-'.rand().'-'.rand();
    ?>
    <form method="post" action="<?php echo $PHP_SELF; ?>?action=new_sheet">
        <input type="hidden" id="sheet_id" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <button type="submit" class="btn btn-warning">Создать новый лист</button>
    </form>

    <h1>Что такое Финансовый коммунизм?</h1>
    <p>
        Это очень простой сервис учёта расходов и уравнивания количества потраченных денег
        в относительно небольшой группе людей (например, компании, которая идёт в&nbsp;поход).
    </p>
    <h2>Мотивировки</h2>
    <p>
        В&nbsp;походах (и прочих совместных поездках по просторам необъятного
        земного шарика) возникают расходы. Например, купили на Камчатке банку икры. И&nbsp;тут же,
        под кустом, в антисанитарных условиях, её разъели, даже без хлеба.
        Ясно дело, за эту банку заплатил кто-то один, а ели её почти все. Икра дорогая,
        и если плативший за неё&nbsp;&mdash; не&nbsp;Рокфеллер, по итогам похода захочется, чтобы
        евшие икру на неё скинулись (возможно, в неравных долях, т.к. у кого-то может быть
        аллергия на морепродукты).
    </p>
    <h2>Как в походах считают деньги</h2>
    <p>
        Чтобы не заниматься перераспределением денег на каждой покупке, обычно существует
        человек (<i>финансист</i>), который фиксирует все общественные расходы группы, а потом по этой табличке
        производится подсчёт, кто сколько потратил, и уравнивание расходов. Указываются следующие данные:
        <ul>
            <li>Кто потратил (может быть несколько человек)</li>
            <li>На что потратил</li>
            <li>Сколько потратил</li>
            <li>Кто потреблял данный товар или услугу (и в каких долях)</li>
        </ul>
    </p>
    <p>
        Зачем нужно указывать доли потребления? Допустим,
        есть относительно дорогая услуга,
        например, спутниковая связь. Допустим, Петя и Вася звонили домой и трепались
        с родственниками о том, как мимо них пролетал камень размером с телевизор,
        а всем остальным было вполне достаточно периодической отправки статусной SMS
        от руководителя группы в Центр в духе &laquo;<i>Прошли перевал Небалуй (2Б*,&nbsp;4670м),
        настроение отличное. Спускаемся к&nbsp;Аникейным озёрам</i>&raquo;.
        В этом случае при распределении потраченных $267 на Турайю можно примерно прикинуть
        стоимость голосового общения и распределить их на Петю и Васю, а оставшиеся $30
        раскидать на всю группу равномерно. А можно просто считать, что для Пети и Васи
        коэффициент пользования данной услугой составил, скажем, 10, а для всех остальных&nbsp;&mdash;&nbsp;1
        и&nbsp;сэкономить себе время на получение детализации счёта.
    </p>
    <p>
        Ещё в походах часто возникают ситуации вида &laquo;Вася одолжил Пете 1000&nbsp;рублей
        до конца маршрута&raquo;. Можно, конечно, такие ситуации не фиксировать в общественной ведомости,
        но тогда это надо держать в головах Васи или Пети, что неудобно. Гораздо проще
        указать, что Вася потратил <b>1000</b>&nbsp;рублей, а Петя потратил <b>-1000</b>&nbsp;рублей.
        Тогда возврат этого долга будет автоматически учтён в балансе.
    </p>
    <h2>Особенности реализации</h2>
    <p>
        Сервис был сделан исходя из учёта описанных выше потребностей. Для походов также часто
        бывает нужна мультивалютность расчётов, т.к. часть расходов идёт, скажем, в евро,
        на местности, а часть (скажем, закупка продуктов)&nbsp;&mdash; в&nbsp;рублях.
    </p>
    <p>
        Наконец, мы считаем, что все, кто ходят в походы одной компанией, честны
        друг к другу. То есть, они не будут корректировать
        втайне от финансиста финансовую ведомость, а наоборот, будут помогать вносить туда
        свои расходы. По&nbsp;этой причине в данном сервисе мы считаем, что ему не особо нужна
        регистрация, авторизация и прочая лабуда. Все, кто имеют ссылку на ведомость,
        имеют возможность её корректировать и контролировать возможные ошибки и&nbsp;неточности.
    </p>
    <p>
        Впрочем, если финансист никому не верит, он может не сообщать ссылку на ведомость
        никому и выдавать только итоговые результаты расчётов: кто сколько должен кому
        передать денег по результатам.
    </p>
    <p>Для полной прозрачности код сервиса открыт (см. ссылку в углу).</p>
    <h2>Как работать с сервисом</h2>
    <p>
        Перед походом заводите себе новую ведомость (<i>Создать новый лист</i>) и обязательно сохраняете
        себе ссылку на этот лист (копируете ссылку из адресной строки браузера и посылаете в&nbsp;групповую
        рассылку).
    </p>
    <p>
        Вбиваете туда предполагаемых участников. Если вдруг кто-то не пошёл в поход,
        но участвовал в подготовке (и, следовательно, мог нести какие-либо общественные расходы),
        достаточно будет обнулить его доли потребления походных благ, не зануляя его расходов.
    </p>
    <p>
        Начинаете вносить расходы (для подтверждения нажимайте либо <i>Enter</i>,
        либо кнопку <i>Сохранить</i>).
    </p>

    <h2>Альтернативы и слово о недоработках</h2>
    <p>
        Автор будет благодарен, если ему сообщат об имеющихся альтернативах.
        Понятно, что вместо всего этого можно использовать табличку в Excel или
        Google Docs, но непонятно, какие у этих способов преимущества по сравнению
        с данным. В наше время было бы интересно иметь мобильную версию с оффлайн-режимом,
        впрочем, ёмкость батарей смартфонов ещё не достигла уровня &laquo;работает 3 недели
        без подзарядки в условиях снега и дождя&raquo;, поэтому блокнот в гермомешке
        для записей в походе&nbsp;&mdash; это суровая и незаменимая реальность.
    </p>
    </div>
    <?php
}


function fe_currency_selector($currency, $id, $exchange_rates) {
    echo "<select class=\"form-control input-sm currency-select\" name=\"$id\">";
    foreach ($exchange_rates as $curr => $rate) {
        $selected = ($curr == $currency) ? ' selected="selected" ' : '';
        echo "<option value=\"$curr\"$selected>$curr</option>\n";
    }
    echo "</select>";
}


function fe_print_transaction_input(
    $members,
    $transaction_id,
    $transaction,
    $transaction_deltas,
    $exchange_rates,
    $bad_lambda_norm
) {
    $currency = fe_get_currency($transaction);
    $description = fe_get_or($transaction, "description");

    $bad_lambda_norm_class = $bad_lambda_norm ? "warning" : "";

    echo "<tr class=\"$bad_lambda_norm_class\">";
    echo "<td>";
    echo "<input class=\"form-control input-sm transaction-description\" name=\"dtr${transaction_id}\" value=\"$description\" type=\"text\"
        title=\"Товар или оказанная услуга\" placeholder=\"Трансфер из пункта А в пункт Б\" />";
    echo "<td>";
    fe_currency_selector($currency, "cur$transaction_id", $exchange_rates);
    echo "</td>\n ";
    $transaction_sum = 0;
    $transaction_member_count = 0;

    foreach ($members as $member_id => $member_name) {
        $delta = $transaction_deltas[$member_id];
        echo "<td>";
        $charge_int = fe_get_charge($transaction, $member_id);
        $transaction_sum += $charge_int;

        $amount_class = "";
        if ($charge_int > 0) {
            $amount_class = "positive";
        } elseif ($charge_int < 0) {
            $amount_class = "negative";
        }

        echo "<input class=\"form-control input-sm amount $amount_class\"
            name=\"tr${transaction_id}_${member_id}\"
            value=\"$charge_int\"
            type=\"text\"
            title=\"Сколько потратил данный участник в указанной валюте\"
        />";
        $member_spent = fe_get_spent($transaction, $member_id);
        $spent_class = "no-use";
        if ($member_spent > 0.01 && $member_spent < 0.99) {
            $spent_class = "lower-use";
        } elseif ($member_spent >= 0.99 && $member_spent <= 1.01) {
            $spent_class = "normal-use";
        } elseif ($member_spent > 1.01) {
            $spent_class = "high-use";
        }

        if ($member_spent > 0.01) {
            ++$transaction_member_count;
        }

        echo "&nbsp;<input class=\"form-control input-sm spent $spent_class\" name=\"sp${transaction_id}_${member_id}\" value=\"$member_spent\" type=\"text\"
            title=\"Коэффициент пользования данной услугой для данного участника\" />";
        echo "</td>\n ";
    }
    echo "<td>$transaction_sum / $transaction_member_count</td>\n ";
    echo "</tr>";
}


function fe_edit_sheet($sheet_id) {
    global $PHP_SELF;
    $sheet_data = fe_load_sheet($sheet_id);

    $members = fe_get_or($sheet_data, "members", array());
    $transactions = fe_get_or($sheet_data, "transactions", array());
    $exchange_rates = fe_get_or($sheet_data, "exchange_rates", array());

    $result = fe_calc_sheet($sheet_data);
    $deltas = $result["deltas"];
    $member_sums = $result["member_sums"];
    $all_transactions_sum = $result["all_transactions_sum"];
    $bad_lambda_norm = $result["bad_lambda_norm"];
    $avg_spendings = $result["avg_spendings"];

    ?>

    <div class="row">
        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_member">
                <div class="form-group">
                    <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                    <label for="member_name-input">Новый участник:&nbsp;</label>
                    <input type="text" class="form-control" name="member_name" id="member_name-input" value="" placeholder="Иван Человеков" />
                    <button type="submit" class="btn btn-primary">Добавить участника</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_currency">
                <div class="form-group">
                <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                <label for="currency-select">Новая валюта:&nbsp;</label>
                <select class="form-control" name="currency" id="currency-select"><?php
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
        <label for="description-input">Новая статья расходов:&nbsp;</label>
        <input class="form-control" type="text" name="description" id="description-input" value="" placeholder="Скинулись на шаурму" />
        <button type="submit" class="btn btn-primary">Добавить</button>
        </div>
    </form>
    <?php

    // See issue https://bitbucket.org/dichlofos/financial-equalizer/issues/13
    $max_input_vars = (integer)(ini_get('max_input_vars'));
    if (count($members) * count($transactions) * 4 > $max_input_vars) {
        ?>
        <div class="tip bg-warning">
            Есть вероятность превышения количества входных переменных <tt>max_input_vars</tt>.
            Пожалуйста, для корректной работы увеличьте лимит (по умолчанию значение равно&nbsp;1000).
            Детали можно уточнить
            в&nbsp;<a href="http://php.net/manual/en/info.configuration.php#ini.max-input-vars">документации</a>.
        </div><?php
    }
    ?>

    <form class="form-inline" method="post" action="<?php echo $PHP_SELF; ?>?action=update_sheet">
        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <label>Участники:</label><br/><?php
        foreach ($members as $member_id => $member_name) {
            echo "<div class=\"form-group member-list\"><label for=\"m$member_id-input\" style=\"width: 30px\">$member_id:&nbsp;</label>";
            echo "<input class=\"form-control\" type=\"text\" name=\"m$member_id\" id=\"m$member_id-input\" value=\"$member_name\" /></div>\n";
        }
        ?><br/>
        <label>Курсы валют:&nbsp;</label><br/><?php
        foreach ($exchange_rates as $currency => $rate) {
            echo "<div class=\"form-group member-list\"><label for=\"e$currency-input\" style=\"width: 40px\">$currency:&nbsp;</label>";
            echo "<input class=\"form-control rate\" type=\"text\" name=\"e$currency\" id=\"e$currency-input\" value=\"$rate\" /></div>\n";
        }

        if (count($bad_lambda_norm)) {?>
        <div class="tip bg-warning">
            В ведомости присутствуют статьи расходов, которые ни на кого не были потрачены (выделены цветом)
        </div><?php
        }?>

        <div id="transactions" class="transactions">
        <table class="table table-condensed transactions">
        <tr>
        <th class="non-member">Статья расхода или сбора</th>
        <th class="non-member">Валюта</th><?php
        foreach ($members as $member_id => $member_name) {
            echo "<th>$member_name</th>\n";
        }
        ?>
        <th class="non-member">Сумма / Чел</th>
        </tr>
        <?php
        foreach ($transactions as $transaction_id => $transaction) {
            fe_print_transaction_input(
                $members,
                $transaction_id,
                $transaction,
                fe_get_or($deltas, $transaction_id, array()),
                $exchange_rates,
                fe_get_or($bad_lambda_norm, $transaction_id)
            );
        }
        // Total
        ?>
        <tr>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        <?php
        foreach ($members as $member_id => $member_name) {
            echo "<th>$member_name</th>\n";
        }
        ?>
        <th>&nbsp;</th>
        </tr>
        <tr class="info">
            <td>Итоговые расходы участника (RUR)</td>
            <td>&nbsp;</td>
        <?php
        foreach ($members as $member_id => $member_name) {
            $member_sum = $member_sums[$member_id];
            $member_sum_rounded = (integer)($member_sum * 100) / 100;
            echo "<td>$member_sum_rounded</td>\n ";
        }
        echo "<td><b>$all_transactions_sum</b> ($avg_spendings&nbsp;/&nbsp;<i>чел</i>)</td>\n";
        ?>
        </tr>
        </table>
        </div><!-- transactions scroller -->
        <div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
            Транзакций: <?php echo count($transactions); ?> шт.
        </div>
    </form>
    <script>
        $(function() {
            var transactions = document.getElementById("transactions");
            transactions.scrollTop = transactions.scrollHeight;
        });
    </script>

    <div class="tip bg-info">
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
