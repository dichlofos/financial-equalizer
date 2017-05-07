<?php

require_once('transaction_input.php');


function fe_check_max_input_vars($members, $transactions) {
    // See issue https://bitbucket.org/dichlofos/financial-equalizer/issues/13
    $max_input_vars = (integer)(ini_get('max_input_vars'));
    if (count($members) * count($transactions) * 4 <= $max_input_vars) {
        return;
    }
    ?>
    <div class="tip bg-warning">
        Есть вероятность превышения количества входных переменных <tt>max_input_vars</tt>.
        Пожалуйста, для корректной работы увеличьте лимит (по умолчанию значение равно&nbsp;1000).
        Детали можно уточнить
        в&nbsp;<a href="http://php.net/manual/en/info.configuration.php#ini.max-input-vars">документации</a>.
    </div><?php
}


function fe_check_bad_lambda_norm($bad_lambda_norm) {
    if (count($bad_lambda_norm)) {?>
    <div class="tip bg-warning">
        В ведомости присутствуют статьи расходов, которые ни на кого не были потрачены (выделены цветом)
    </div><?php
    }
}


function fe_draw_scroll_transactions() {?>
    <script>
        $(function() {
            var transactions = document.getElementById("transactions");
            transactions.scrollTop = transactions.scrollHeight;
        });
    </script><?php
}


function fe_draw_transaction_tips() {?>
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
    </div><?php
}


function fe_edit_sheet($sheet_id, $member_id_filter) {
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

    global $FE_TRANSACTION_CELL_WIDTH;
    $float_width = (float)($FE_TRANSACTION_CELL_WIDTH);
    $width_percent = count($members)
        ? (integer)($float_width / count($members))
        : (integer)($float_width);

    $sheet_title_ht = htmlspecialchars(fe_get_or($sheet_data, "title"));

    $request_url = "/?sheet_id=$sheet_id";

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
                <input name="currency" class="form-control" id="currency-input" />
                <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">&nbsp;</div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=add_transaction">
                <div class="form-group">
                <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                <label for="description-input">Новая статья расходов:&nbsp;</label>
                <input class="form-control" type="text" name="description" id="description-input" value="" placeholder="Скинулись на шаурму" />
                <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>

        <div class="col-md-4">
            <form method="post" class="form-inline" action="<?php echo $PHP_SELF; ?>?action=set_sheet_title">
                <div class="form-group">
                <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
                <label for="sheet_title-input">Название листа:&nbsp;</label>
                <input class="form-control" type="text" name="title" id="sheet_title-input"
                    value="<?php echo $sheet_title_ht; ?>" placeholder="Поход по Монголии" />
                <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    fe_check_max_input_vars($members, $transactions);
    ?>

    <input id="member_count" type="hidden" value="<?php echo count($members); ?>"/>
    <input id="spent_min" type="hidden" value="<?php echo $result["spent_min"]; ?>"/>
    <input id="spent_max" type="hidden" value="<?php echo $result["spent_max"]; ?>"/>

    <form class="form-inline" method="post" action="<?php echo $PHP_SELF; ?>?action=update_sheet">
        <input type="hidden" name="sheet_id" value="<?php echo $sheet_id; ?>" />
        <label>Участники:</label><br/><?php
        foreach ($members as $member_id => $member_name) {
            echo "<div class=\"form-group member-list\">";
            echo "<input class=\"form-control\" type=\"text\" name=\"m$member_id\" id=\"m$member_id-input\" value=\"$member_name\" />";
            echo "</div>\n";
        }
        ?><br/>
        <label>Курсы валют:&nbsp;</label><br/><?php
        foreach ($exchange_rates as $currency => $rate) {
            echo "<div class=\"form-group member-list\"><label for=\"e$currency-input\">$currency:&nbsp;</label>";
            echo "<input class=\"form-control rate\" type=\"text\" name=\"e$currency\" id=\"e$currency-input\" value=\"$rate\" /></div>\n";
        }

        fe_check_bad_lambda_norm($bad_lambda_norm);
        ?>

        <table class="table table-condensed transactions">
        <tr>
        <th class="non-member transaction-description">Статья расхода или сбора</th>
        <th class="non-member transaction-currency">Валюта</th><?php
        foreach ($members as $member_id => $member_name) {
            echo "<th class=\"transaction-amount\" style=\"width: $width_percent%;\"><a href=\"$request_url&amp;member_id_filter=$member_id\">$member_name</a></th>\n";
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
                $width_percent,
                $member_id_filter
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
    <?php
    fe_draw_scroll_transactions();
    fe_draw_transaction_tips();
    ?>
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
