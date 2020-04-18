

<section class="add-funds m-t-30">
    <div class="container-fluid">
        <div class="row justify-content-md-center" id="result_ajaxSearch">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title"><?=lang("perfect_money_creditdebit_card_payment")?></h3>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <form id="paymentFrm" method="post" action="https://perfectmoney.is/api/step1.asp">

                                <input type="hidden" name="PAYEE_ACCOUNT" value="<?php echo get_option("perfect_money_account_id", ''); ?>">
                                <input type="hidden" name="PAYEE_NAME" value="<?php echo get_option("perfect_money_account_name", ''); ?>">
                                <input type="hidden" name="PAYMENT_ID" value="<?php echo $uid; ?>">
                                <input type="hidden" id="new_amount_perfectmoney" name="PAYMENT_AMOUNT" value="<?=$amount?>">
                                <input type="hidden" name="PAYMENT_UNITS" value="<?php echo get_option("currency_code", "USD") == ""?'USD' : get_option("currency_code", "") ; ?>">
                                <input type="hidden" name="STATUS_URL"  value="<?=cn($module."/response")?>">
                                <input type="hidden" name="PAYMENT_URL" value="<?=cn($module."/payment_success")?>">
                                <input type="hidden" name="PAYMENT_URL_METHOD" value="POST">
                                <input type="hidden" name="NOPAYMENT_URL"  value="<?=cn($module."/payment_failed")?>">
                                <input type="hidden" name="NOPAYMENT_URL_METHOD" value="POST">
                                <input type="hidden" name="SUGGESTED_MEMO" value="">
                                <input type="hidden" name="uid" value="<?php echo $uid; ?>">
                                <input type="hidden" name="BAGGAGE_FIELDS" value="uid">
                                <input type="submit" id="perfectmoney" class="login_btn col-sm-12" name="PAYMENT_METHOD" value="Pay <?=$amount?>">
                                <!-- submit button -->
<!--                                <input type="submit" class="btn btn-primary btn-lg btn-block" value="--><?//=lang("Submit")?><!--">-->
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2Checkout JavaScript library -->

<?php
//if (get_option('payment_environment',"") == "sandbox") {
//    $payment_environment = 'sandbox';
//}else{
//    $payment_environment = 'production';
//}
//?>
<script>
    // Called when token created successfully.
    var successCallback = function(data) {
        var myForm = document.getElementById('paymentFrm');

        // Set the token as the value for the token input
        myForm.token.value = data.response.token.token;

        // Form submission
        myForm.submit();
    };

    // Called when token creation fails.
    var errorCallback = function(data) {
        if (data.errorCode === 200) {
            tokenRequest();
        } else {
            alert(data.errorMsg);
        }
    };

    var tokenRequest = function() {
        // Setup token request arguments
        var args = {
            sellerId: "<?=get_option('2checkout_seller_id',"")?>",
            publishableKey: "<?=get_option('2checkout_publishable_key',"")?>",
            ccNo: $("#card_num").val(),
            cvv: $("#cvv").val(),
            expMonth: $("#exp_month").val(),
            expYear: $("#exp_year").val()
        };

        // Make the token request
        TCO.requestToken(successCallback, errorCallback, args);
    };

    $(function() {
        // Pull in the public encryption key for our environment
        TCO.loadPubKey("<?=$payment_environment?>");

        $("#paymentFrm").submit(function(e) {
            // Call our token request function
            tokenRequest();
            // Prevent form from submitting
            return false;
        });
    });
</script>