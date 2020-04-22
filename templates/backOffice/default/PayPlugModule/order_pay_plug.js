var inputAmountRefund = $('#input_amount_refund');
var maxAmountRefund = parseFloat(inputAmountRefund.attr('max'));

$("#quick_select_amount_refund").change(function (e) {
    var amountToRefund = 0;
    var selected = $(e.target).val();

    if (null !== selected) {
        amountToRefund = selected.reduce(function (a, c) {
            return parseFloat(a) + parseFloat(c);
        });
    }

    amountToRefund =  amountToRefund > maxAmountRefund ? maxAmountRefund : amountToRefund;
    inputAmountRefund.val(amountToRefund).trigger('change');
});

inputAmountRefund.change(function (e) {
    var amountToRefund = $(e.target).val() > maxAmountRefund ? maxAmountRefund : $(e.target).val();
    $("#refund_amount").val(amountToRefund);
});