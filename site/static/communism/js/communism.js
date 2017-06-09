
$(document).ready(function () {
    fe_on_resize();
});

$(document).resize(function () {
    fe_on_resize();
});

function fe_on_resize() {
    var total_width = $('#transactions').innerWidth();

    // console.log("total width: " + total_width);
    total_width = _set_transaction_description_width(total_width);
    total_width -= 110; // currency, see CSS
    total_width -= 85; // total stats, see CSS
    // console.log("total width rest: " + total_width);

    _set_transaction_amount_width(total_width);
}

function _set_width_by_selector(selector, width) {
    var elements = $(selector);
    for (var i = 0; i < elements.length; ++i) {
        $(elements[i]).innerWidth(width);
    }
}

function _set_transaction_description_width(total_width) {
    var descr_width = Math.trunc(total_width / 6);
    // console.log("descr width: " + descr_width);
    total_width -= descr_width;
    _set_width_by_selector('td.transaction-description', descr_width - 2); // 2px border
    _set_width_by_selector('input.transaction-description', descr_width - 15);
    _set_width_by_selector('th.transaction-description', descr_width);
    return total_width;
}

function _set_transaction_amount_width(total_width) {
    var member_count_ele = $('#member_count');
    if (!member_count_ele.length) {
        // main page does not contain this element
        return;
    }

    var member_count = parseInt(member_count_ele.val());
    var spent_max_length = $('#spent_max').val().length;
    var spent_min_length = $('#spent_min').val().length;
    var digit_count = Math.max(spent_max_length, spent_min_length);

    // initial values are too narrow
    var amount_input_width = 30;
    if (digit_count >= 3) {
        amount_input_width = digit_count * 8 + 4;
    }

    var amount_width = 50;
    if (member_count > 0) {
        amount_width = Math.trunc(total_width / member_count);
        if (amount_width < 50)
            amount_width = 50;
    }
    _set_width_by_selector('td.transaction-amount', amount_width - 2);
    _set_width_by_selector('th.transaction-amount', amount_width - 2);
    _set_width_by_selector('input.amount', amount_input_width + 'px');
}
