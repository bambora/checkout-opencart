<?php if($getPaymentTransaction_success == true) { ?>
<div class="col-xs-12 col-sm-12 col-md-6 col-lg-4">
    <h3><?php echo $text_payment_info; ?></h3>
    <table class="table table-striped">
        <tr>
            <td><?php echo $text_transaction_id; ?></td>
            <td class="text-right"><?php echo $transaction['id']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_authorized; ?></td>
            <td class="text-right"><?php echo $transaction['authorized']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_date; ?></td>
            <td class="text-right"><?php echo $transaction['date']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_payment_type; ?></td>
            <td class="text-right"><?php echo $transaction['paymentType']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_card_number; ?></td>
            <td class="text-right"><?php echo $transaction['cardNumber']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_surcharge_fee; ?></td>
            <td class="text-right"><?php echo $transaction['surchargeFee']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_captured; ?></td>
            <td class="text-right"><?php echo $transaction['captured']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_refunded; ?></td>
            <td class="text-right"><?php echo $transaction['refunded']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_acquirer; ?></td>
            <td class="text-right"><?php echo $transaction['acquirer']; ?></td>
        </tr>
        <tr>
            <td><?php echo $text_transaction_status; ?></td>
            <td class="text-right"><?php echo $transaction['status']; ?></td>
        </tr>
    </table>

    <?php if($showActions == true) { ?>
        <div class="bambora_online_checkout_action_container">
            <div class="input-group">
                <div class="input-group-addon"><?php echo $transaction['currencyCode']; ?></div>
                <?php if($transaction['availableForCapture'] > 0) { ?>
                    <input type="text" data-toggle="tooltip" title="<?php echo $text_tooltip; ?>" id="bambora_online_checkout_amount" name="bambora_online_checkout_amount" value="<?php echo $transaction['availableForCapture']; ?>" />
                <?php } else { ?>
                    <input type="text" data-toggle="tooltip" title="<?php echo $text_tooltip; ?>" id="bambora_online_checkout_amount" name="bambora_online_checkout_amount" value="<?php echo $transaction['availableForRefund']; ?>" />
                <?php } ?>
            </div>
            <div id="bambora_online_checkout_format_error" class="alert alert-danger" style="display:none"><?php echo $error_amount_format; ?></div>
            <?php if($transaction['availableForCapture'] > 0) { ?>
                <a class="bambora-button btn btn-success" id="btn-bambora-online-checkout-capture"><?php echo $text_btn_capture; ?></a>
                <span class="bambora-button btn btn-success" id="img-loading-capture" style="display:none;"><i class="fa fa-cog fa-spin fa-lg"></i></span>
            <?php } ?>
            <?php if($transaction['availableForRefund'] > 0) { ?>
                <a class="bambora-button btn btn-warning" id="btn-bambora-online-checkout-refund"><?php echo $text_btn_refund; ?></a>
                <span class="bambora-button btn btn-warning" id="img-loading-refund" style="display:none;"><i class="fa fa-cog fa-spin fa-lg"></i></span>
            <?php } ?>
            <?php if($transaction['canVoid'] == true) { ?>
                <a class="bambora-button btn btn-danger" id="btn-bambora-online-checkout-void"><?php echo $text_btn_void; ?></a>
                <span class="bambora-button btn btn-danger" id="img-loading-void" style="display:none;"><i class="fa fa-cog fa-spin fa-lg"></i></span>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<div class="col-xs-12 col-sm-12 col-md-6 col-lg-5">
<h3><?php echo $text_transaction_operations; ?></h3>
    <table class="table table-striped">
        <tr>
            <th><?php echo $text_transaction_operations_date; ?></th>
            <th><?php echo $text_transaction_operations_action; ?></th>
            <th><?php echo $text_transaction_operations_amount; ?></th>
            <th><?php echo $text_transaction_operations_eci; ?></th>
        </tr>
<?php if(count($transaction['operations']) > 0) {
        foreach($transaction['operations'] as $operation) { ?>
            <tr>
                <td><?php echo $operation['createdDate']; ?></td>
                <td><?php echo $operation['action']; ?></td>
                <td><?php echo $operation['amount']; ?></td>
                <td><?php echo $operation['eci']; ?></td>
            </tr>
<?php } } ?>
    </table>
</div>

<div class="col-lg-3 text-center hidden-xs hidden-sm hidden-md">
    <a href="https://merchant.bambora.com" title="<?php echo $text_goto_bambora_admin; ?>" target="_blank">
        <img class="bambora-logo" src="https://d3r1pwhfz7unl9.cloudfront.net/bambora/bambora-logo.svg" style="padding-bottom: 10px;" />

    </a>
    <div>
        <a href="https://merchant.bambora.com"  title="<?php echo $text_goto_bambora_admin; ?>" target="_blank"><?php echo $text_goto_bambora_admin; ?></a>
    </div>
</div>

<script type="text/javascript"><!--
    var amountInputField = $("#bambora_online_checkout_amount");
    $("#btn-bambora-online-checkout-capture").bind('click', function() {
        if (validateInputField()) {
            var confirmBodyText = '<?php echo $text_capture_payment_body . " " . $transaction["currencyCode"]; ?>' + amountInputField.val() + ' ? ';
            confirm('<?php echo $text_capture_payment_header; ?>', confirmBodyText, '<?php echo $text_no; ?>', '<?php echo $text_yes; ?>', function() {
                $.ajax({
                    type:'POST',
                    dataType: 'html',
                    data: {
                        'transactionId': '<?php echo $transaction["id"];?>',
                        'captureAmount': amountInputField.val(),
                        'currencyCode': '<?php echo $transaction["currencyCode"]; ?>'
                    },
                    url: 'index.php?route=extension/payment/bambora_online_checkout/capture&token=<?php echo $token; ?>',
                    beforeSend: handleBeforeSend('capture'),
                    success: function(json) {
                        handleSuccess('capture', json);
                    }
                });
            });
        } else {
            $("#bambora_online_checkout_format_error").toggle();
        }
    });

    $("#btn-bambora-online-checkout-refund").bind('click', function() {
        if (validateInputField()) {
            var confirmBodyText = '<?php echo $text_refund_payment_body . " " . $transaction["currencyCode"]; ?>' + ' ' + amountInputField.val() + ' ? ';
            confirm('<?php echo $text_refund_payment_header; ?>', confirmBodyText, '<?php echo $text_no; ?>', '<?php echo $text_yes; ?>', function() {
                $.ajax({
                    type:'POST',
                    dataType: 'html',
                    data: {
                        'transactionId': '<?php echo $transaction["id"]; ?>',
                        'refundAmount': amountInputField.val(),
                        'currencyCode': '<?php echo $transaction["currencyCode"]; ?>'
                    },
                    url: 'index.php?route=extension/payment/bambora_online_checkout/refund&token=<?php echo $token; ?>',
                    beforeSend: handleBeforeSend('refund'),
                    success: function(json) {
                        handleSuccess('refund', json);
                    }
                });
            });
        } else {
            $("#bambora_online_checkout_format_error").toggle();
        }
    });

    $("#btn-bambora-online-checkout-void").bind('click', function() {
        confirm('<?php echo $text_void_payment_header; ?>', '<?php echo $text_void_payment_body; ?>', '<?php echo $text_no; ?>', '<?php echo $text_yes; ?>', function() {
            $.ajax({
                type:'POST',
                dataType: 'html',
                data: {
                    'transactionId': '<?php echo $transaction["id"]; ?>',
                },
                url: 'index.php?route=extension/payment/bambora_online_checkout/void&token=<?php echo $token; ?>',
                beforeSend: handleBeforeSend('void'),
                success: function(json) {
                    handleSuccess('void', json);
                }
            });
        });
    });

    function validateInputField() {
        var reg = new RegExp(/^(?:[\d]+([,.]?[\d]{0,3}))$/);
        if (amountInputField.length > 0 && !reg.test(amountInputField.val())) {
            return false;
        }

        return true;
    }

    function handleBeforeSend(type) {
        $('#btn-bambora-online-checkout-' + type).hide();
        $('#img-loading-' + type).show();
    }

    function handleSuccess(type, json) {
        $('#img-loading-' + type).hide();
        data = JSON.parse(json);
        if(data.meta.result === true) {
            $('#bambora-online-checkout-error-message').hide();
            getTransactionInformation();
        } else {
            $('#btn-bambora-online-checkout-' + type).show();
            $('#bambora-online-checkout-error-message').text('<?php echo $error_action_base; ?>'+ ': ' + data.meta.message.merchant);
            $('#bambora-online-checkout-error-message').show();
        }
    }

    function confirm(heading, body, btnNoText, btnYesText, callback) {
        var confirmModal =
          $('<div class="modal fade">' +
              '<div class="modal-dialog">' +
              '<div class="modal-content">' +
              '<div class="modal-header">' +
                '<a class="close" data-dismiss="modal" >&times;</a>' +
                '<h3>' + heading +'</h3>' +
              '</div>' +

              '<div class="modal-body">' +
                '<p>' + body + '</p>' +
              '</div>' +

              '<div class="modal-footer">' +
                '<a class="btn" data-dismiss="modal">' +
                  btnNoText +
                '</a>' +
                '<a id="btnYes" class="btn btn-success">' +
                  btnYesText +
                '</a>' +
              '</div>' +
              '</div>' +
              '</div>' +
            '</div>'
        );
        confirmModal.find('#btnYes').click(function(event) {
            callback();
            confirmModal.modal('hide');
        });
        confirmModal.modal('show');
    };

    $("#bambora_online_checkout_amount").keydown(function (e) {
        var digit = String.fromCharCode(e.which || e.keyCode);
        if (e.which !== 8 && e.which !== 46 && !(e.which >= 37 && e.which <= 40) && e.which !== 110 && e.which !== 188
            && e.which !== 190 && e.which !== 35 && e.which !== 36 && !(e.which >= 96 && e.which <= 106)) {
            var reg = new RegExp(/^(?:\d+(?:,\d{0,3})*(?:\.\d{0,2})?|\d+(?:\.\d{0,3})*(?:,\d{0,2})?)$/);
            if (reg.test(digit)) {
                return;
            } else {
                return false;
            }
        }
    });

    amountInputField.focus(function () {
        if ($("#bambora_online_checkout_format_error").css("display") !== "none") {
            $("#bambora_online_checkout_format_error").fadeOut('slow');
        }
    });
    $('#bambora-online-checkout-error-message').click(function(){
        $('#bambora-online-checkout-error-message').fadeOut('slow');
    });

    $('[data-toggle="tooltip"]').tooltip();
//-->
</script>
<?php }else { ?>
<div class="alert alert-danger" style="margin-left:10px; margin-right:10px;"><?php echo $text_getPaymentTransaction_error; ?></div>
<?php } ?>