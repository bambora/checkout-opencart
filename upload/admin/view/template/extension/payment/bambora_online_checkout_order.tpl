<div class="alert alert-danger" id="bambora-online-checkout-error-message"
     style="display:none;"></div>
<div id="bambora_online_checkout_payment_container"></div>
<script type="text/javascript"><!--
    function getTransactionInformation() {
        $('#bambora-online-checkout-error-message').click(function (e) {
            $('#bambora-online-checkout-error-message').fadeOut('slow');
        });
        $.ajax({
            url: 'index.php?route=extension/payment/bambora_online_checkout/getPaymentTransaction&token=<?php echo $token; ?>',
            dataType: 'html',
            data: {
                order_id: '<?php echo $order_id; ?>'
            },
            beforeSend: function () {
                $('#bambora_online_checkout_payment_container').html('<i class="bambora-online-checkout-loading fa fa-spinner fa-spin fa-5x" style="text-align: center; margin: 0 auto; width: 100%; font-size: 5em;"></i>');
            },
            success: function (html) {
                $('#bambora_online_checkout_payment_container').html(html);
            }
        });
    }

    getTransactionInformation();
    //--></script>

<style>
    .input-group-addon {
        padding: 6px 8px;
        font-size: 12px;
        font-weight: normal;
        line-height: 1;
        color: #555;
        text-align: center;
        background-color: #F5F8F9;
        border-top: 1px solid #C7D6DB;
        border-left: 1px solid #C7D6DB;
        border-bottom: 1px solid #C7D6DB;
        border-right: 1px solid #C7D6DB;
        border-radius: 3px;
    }

    .input-group input {
        display: block;
        width: 100%;
        height: 31px;
        padding: 6px 8px;
        font-size: 12px;
        line-height: 1.42857;
        color: #555;
        background-color: #F5F8F9;
        background-image: none;
        border: 1px solid #C7D6DB;
        border-radius: 3px;
        -webkit-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
        -o-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
        transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    }

    .bambora-button {
        margin: 3px 3px 3px 0;
    }

    .bambora-logo {
        width: 300px;
        max-width: 100%;
    }
</style>
