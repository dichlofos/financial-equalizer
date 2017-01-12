
$(document).ready(function () {
    var total_width = $('#transactions').innerWidth();

    console.log("total width: " + total_width);
    total_width = _set_transaction_description_width(total_width);
    total_width -= 70; // currency
    total_width -= 80; // total sums
    console.log("total width rest: " + total_width);

    _set_transaction_amount_width(total_width);

});

function _set_transaction_description_width(total_width) {
    var descriptions = $('td.transaction-description');
    var descr_width = Math.trunc(total_width / 6);
    total_width -= descr_width;
    for (var i = 0; i < descriptions.length; ++i) {
        // 2px is for border
        $(descriptions[i]).innerWidth(descr_width - 2);
    }
    console.log("descr width: " + descr_width);

    var inputs = $('input.transaction-description');
    for (i = 0; i < inputs.length; ++i) {
        // 2px is for border
        $(inputs[i]).innerWidth(descr_width - 15);
    }

    return total_width;
}

function _set_transaction_amount_width(total_width) {
    var member_count = parseInt($('#member_count').val());
    var spent_max_length = parseInt($('#spent_max').val());

    var sums = $('td.transaction-amount');
    var amount_width = 50;
    if (member_count > 0) {
        amount_width = Math.trunc(total_width / member_count);
        if (amount_width < 40)
            amount_width = 40;
    }
    console.log("amount width: " + amount_width);
    for (var i = 0; i < sums.length; ++i) {
        // 2px is for border
        $(sums[i]).width(amount_width - 2);
    }

    var inputs = $('input.amount');
    for (var i = 0; i < inputs.length; ++i) {
        console.log(spent_max_length);
        $(inputs[i]).width((spent_max_length * 0.4) + 'ex');
    }
}
