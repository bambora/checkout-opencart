<div class="col-lg-12"> 
    <div id="bambora-online-checkout-error" class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <p id="bambora-online-checkout-error_text"></p>
    </div>     
    <h3><?php echo $text_title; ?></h3>
    <p><?php echo $text_payment; ?></p>
    <div class="bambora-online-checkout-paymentlogos col-sm-9 col-xs-12">
        <?php foreach($bambora_online_checkout_allowed_payment_type_ids as $paymentTypeId) { ?>
            <img src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/<?php echo $paymentTypeId; ?>.svg"/>
        <?php } ?>
        </div>
    <div class="buttons">
      <div class="pull-right">
        <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
      </div>
    </div>
    <script type="text/javascript">
        (function (n, t, i, r, u, f, e) { n[u] = n[u] || function() {
        (n[u].q = n[u].q || []).push(arguments)}; f = t.createElement(i);
        e = t.getElementsByTagName(i)[0]; f.async = 1; f.src = r;
        e.parentNode.insertBefore(f, e)})(window, document, "script", "https://v1.checkout.bambora.com/assets/paymentwindow-v1.min.js", "bam");
    </script>
    <script type="text/javascript">
    $('#button-confirm').on('click', function() {
        $.ajax({
            url: 'index.php?route=extension/payment/bambora_online_checkout/confirm',
            dataType: 'json',
            beforeSend: function() {
                $('#button-confirm').button('loading');
            },
            success: function(json) {
                if(json['error']) {
                    $('#bambora-online-checkout-error_text').text(json['error']);
                    $('#bambora-online-checkout-error').css('display','block');
                    $('#button-confirm').button('reset');
                    return false;
                } 

                var options = {
                    windowstate: <?php echo $bambora_online_checkout_window_state; ?>,
                    onClose: function(){ 
                      $('#button-confirm').button('reset');
                    }  
                }
                bam("open", json['url'], options);           
            },
            error: function(xhr, ajaxOptions, thrownError) {
                xhr.responseText;
                $('#button-confirm').button('reset');
            }
        });
    });
    </script>
    <style>
    .bambora-online-checkout-paymentlogos {
        padding-left: 0;
    }
    .bambora-online-checkout-paymentlogos img {
        display: inline-block;
        padding: 6px 3px 6px 0;
        max-width: 53px;
    }
    #bambora-online-checkout-error{
        display: none;
    }
    </style>
</div>
