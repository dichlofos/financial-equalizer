<?php

require_once('transaction_input.php');


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
    $width_percent = count($members) ? (integer)(68.0 / count($members)) : "68";

    ?>

    <div class="container-fluid">
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
                global $FE_ALL_CURRENCIES;
                foreach ($FE_ALL_CURRENCIES as $curr) {
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


        <table class="table table-condensed transactions">
        <tr>
        <th class="non-member transaction-description">Статья расхода или сбора</th>
        <th class="non-member transaction-currency">Валюта</th><?php
        foreach ($members as $member_id => $member_name) {
            echo "<th style=\"width: $width_percent%;\">$member_name</th>\n";
        }
        ?>
        <th class="non-member transaction-stats">Сумма / Чел</th>
        </tr>
        </table>

        <div id="transactions" class="transactions"><!-- scroller -->
        <table class="table table-condensed transactions">
        <?php
        foreach ($transactions as $transaction_id => $transaction) {
            fe_print_transaction_input(
                $members,
                $transaction_id,
                $transaction,
                fe_get_or($deltas, $transaction_id, array()),
                $exchange_rates,
                fe_get_or($bad_lambda_norm, $transaction_id),
                $width_percent
            );
        }
        ?>
        </table>
        </div><!-- transactions scroller -->

        <!-- footer (Total) -->
        <table class="table table-condensed transactions transactions-footer">
        <tr>
            <th class="transaction-description">&nbsp;</th>
            <th class="transaction-currency">&nbsp;</th>
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
        echo "<td class=\"transaction-stats\"><b>$all_transactions_sum</b>&nbsp;р,<br/>\n".
            "${avg_spendings}&nbsp;/&nbsp;<i>чел</i></td>\n";
        ?>
        </tr>
        </table>

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
    </div><!-- class="container-fluid" -->
    <?php
}
