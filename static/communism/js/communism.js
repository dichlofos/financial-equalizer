
$(document).ready(function () {
    var total_width = $('#transactions').innerWidth();
    console.log("total width: " + total_width);
    var descriptions = $('td.transaction-description');
    var descr_width = Math.trunc(total_width / 6);
    total_width -= descr_width;
    for (var i = 0; i < descriptions.length; ++i) {
        $(descriptions[i]).innerWidth(descr_width);
    }
    console.log("descr width: " + descr_width);
    total_width -= 70; // currency
    total_width -= 80; // total sums
    console.log("total width rest: " + total_width);

    var sums = $('td.transaction-amount');
    var amount_width = Math.trunc(total_width / 14) - 2; // border
    console.log("amount width: " + amount_width);
    for (var i = 0; i < sums.length; ++i) {
        $(sums[i]).width(amount_width);//  "width: " + amount_width+ "px;");
        //console.log(x[i]);
    }
});
