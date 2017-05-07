<?php

require_once('equalizer.php');


function fe_currency_selector($currency, $id, $exchange_rates) {
    echo "<select class=\"form-control input-sm currency-select\" name=\"$id\">";
    foreach ($exchange_rates as $curr => $rate) {
        $selected = ($curr == $currency) ? ' selected="selected" ' : '';
        echo "<option value=\"$curr\"$selected>$curr</option>\n";
    }
    echo "</select>";
}


function fe_calc_amount_class($charge_int) {
    $amount_class = "";
    if ($charge_int > 0) {
        $amount_class = "positive";
    } elseif ($charge_int < 0) {
        $amount_class = "negative";
    }
    return $amount_class;
}


function fe_calc_spent_class($member_spent) {
    $spent_class = "no-use";
    if ($member_spent > 0.01 && $member_spent < 0.99) {
        $spent_class = "lower-use";
    } elseif ($member_spent >= 0.99 && $member_spent <= 1.01) {
        $spent_class = "normal-use";
    } elseif ($member_spent > 1.01) {
        $spent_class = "high-use";
    }
    return $spent_class;
}


function fe_calc_row_visibility_class($transaction, $member_id_filter) {
    if (fe_empty($member_id_filter)) {
        return "";
    }

    $charge_int = fe_get_charge($transaction, $member_id_filter);
    if (abs($charge_int) < 0.01) {
        return "transaction-row-filtered";
    }
    return "";
}


function fe_print_transaction_input(
    $members,
    $transaction_id,
    $transaction,
    $transaction_deltas,
    $exchange_rates,
    $bad_lambda_norm,
    $width_percent,
    $member_id_filter
) {
    $currency = fe_get_currency($transaction);
    $description = fe_get_or($transaction, "description");
    $timestamp = fe_get_or($transaction, FE_KEY_TIMESTAMP_MODIFIED);

    $bad_lambda_norm_class = $bad_lambda_norm ? "warning" : "";

    $visibility_class = fe_calc_row_visibility_class($transaction, $member_id_filter);

    echo "<tr class=\"$bad_lambda_norm_class $visibility_class\">";
    echo "<td class=\"transaction-description\">";
    echo "<input type=\"hidden\" name=\"ts${transaction_id}\" value=\"$timestamp\"/>";
    echo "<input class=\"form-control input-sm transaction-description\" name=\"dtr${transaction_id}\" value=\"$description\" type=\"text\" ".
        "title=\"Товар или оказанная услуга\" placeholder=\"Трансфер из пункта А в пункт Б\" />";
    echo "<td class=\"transaction-currency\">";
    fe_currency_selector($currency, "cur$transaction_id", $exchange_rates);
    echo "</td>\n ";
    $transaction_sum = 0;
    $transaction_member_count = 0;

    foreach ($members as $member_id => $member_name) {
        $delta = $transaction_deltas[$member_id];
        echo "<td class=\"transaction-amount\" style=\"width: $width_percent%;\">";
        $charge_int = fe_get_charge($transaction, $member_id);
        $transaction_sum += $charge_int;

        $amount_class = fe_calc_amount_class($charge_int);
        echo "<input class=\"form-control input-sm amount $amount_class\" ".
            "name=\"tr${transaction_id}_${member_id}\" ".
            "value=\"$charge_int\" ".
            "type=\"text\" ".
            "title=\"Сколько потратил данный участник в указанной валюте\" ".
            "/>";
        $member_spent = fe_get_spent($transaction, $member_id);
        $spent_class = fe_calc_spent_class($member_spent);

        if ($member_spent > 0.01) {
            ++$transaction_member_count;
        }

        echo "&nbsp;<input class=\"form-control input-sm spent $spent_class\" name=\"sp${transaction_id}_${member_id}\" ".
            "value=\"$member_spent\" type=\"text\" ".
            "title=\"Коэффициент пользования данной услугой для данного участника\" />";
        echo "</td>\n ";
    }
    echo "<td class=\"transaction-stats\">";
    echo    "<img title=\"$timestamp\" src=\"/static/communism/images/clock.png\" border=\"0\"/>&nbsp;";
    echo    "$transaction_sum / $transaction_member_count</td>\n ";
    echo "</tr>";
}
