<?php
include ('../config.php');
?>
<!Doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Card Token</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="alert cardAlert">
        </div>
        <form action="../get_token_first.php" method="post" id="pay" name="pay" >
            <fieldset>
                <ul>
                    <li>
                        <label for="email">Email</label>
                        <input id="email" name="email" value="test_user_19653727@testuser.com" type="email" placeholder="your email"/>
                    </li>
                    <li>
                        <label for="cardNumber">Credit card number:</label>
                        <input type="text" id="cardNumber" data-checkout="cardNumber" placeholder="4509 9535 6623 3704" required />
                    </li>
                    <li>
                        <label for="securityCode">Security code:</label>
                        <input type="text" id="securityCode" data-checkout="securityCode" placeholder="123"  required/>
                    </li>
                    <li>
                        <label for="cardExpirationMonth">Expiration month:</label>
                        <input type="text" id="cardExpirationMonth" data-checkout="cardExpirationMonth" placeholder="12" required/>
                    </li>
                    <li>
                        <label for="cardExpirationYear">Expiration year:</label>
                        <input type="text" id="cardExpirationYear" data-checkout="cardExpirationYear" placeholder="2015" required/>
                    </li>
                    <li>
                        <label for="cardholderName">Card holder name:</label>
                        <input type="text" name="cardholderName"  id="cardholderName" data-checkout="cardholderName" placeholder="APRO" required />
                    </li>
                    <li>
                        <label for="docType">Document type:</label>
                        <select id="docType" data-checkout="docType"></select>
                    </li>
                    <li>
                        <label for="docNumber">Document number:</label>
                        <input type="text" id="docNumber" data-checkout="docNumber" placeholder="12345678" required />
                    </li>
                    <li>
                        <label for="docNumber">precio:</label>
                        <input type="number" name="preci" min="20000" minlength="5" placeholder="100000" required />
                    </li>
                </ul>
                <input type="hidden" name="paymentMethodId">
                <input type="submit" value="Pay!" />
            </fieldset>
        </form>
    </div>
<script src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
    (function( $ ) {

        Mercadopago.setPublishableKey('<?php echo PUBLIC_KEY; ?>');
        Mercadopago.getIdentificationTypes();
        

        let checkPaymentId = null;
        let ccNumber = $('input[data-checkout="cardNumber"]');

        function getBin() {
            let valNumbercc = $(ccNumber).val();
            return valNumbercc.replace(/[ .-]/g, '').slice(0, 6);
        }

        function guessingPaymentMethod() {
            let bin = getBin();

            if (bin.length >= 6) {
                Mercadopago.getPaymentMethod({
                    "bin": bin
                }, setPaymentMethodInfo);

                if (checkPaymentId !== null){
                    clearInterval(checkPaymentId);
                    checkPaymentId = null;
                }
            }else{
                checkPaymentId = setInterval(guessingPaymentMethod, 100);
            }
        }

        function setPaymentMethodInfo(status, response) {
            if (status == 200) {
                $("input[name=paymentMethodId]").val(response[0].id)
            }
        }


        $("form#pay").submit(function (e){
            e.preventDefault();
            guessingPaymentMethod();
            $('.cardAlert').html();
            let valNumbercc = $(ccNumber).val();
            if (!valid_credit_card(valNumbercc)){
                $('.cardAlert').addClass('alert-danger');
                $('.cardAlert').html("veifique el número de tarjeta");
                $(ccNumber).focus();
                return;
            }
            Mercadopago.createToken(this, sdkResponseHandler);
        });


        function sdkResponseHandler(status, response) {
            if (status != 200 && status != 201) {
                $('.cardAlert').addClass('alert-danger');
                $('.cardAlert').html("verify filled data");
            }else{


                $.ajax({
                    type: 'POST',
                    url:  $('form#pay').attr('action'),
                    data: $('form#pay').serialize() + '&tokenCard=' + response.id,
                    dataType: 'json',
                    beforeSend: function(){
                        $('.cardAlert').addClass('alert-info');
                        $('.cardAlert').html('<strong>Espere pro favor...</strong>');
                        $('input[type="submit"]').prop('disabled', true);
                        $('body').css('cursor','wait');
                    },
                    success: function(r){
                        $('body').css('cursor','default');
                        if(r.status){
                            $('.cardAlert').addClass('alert-success');
                            $('.cardAlert').html('<strong>Estado del pago: '+r.statusPayment+', Transacción id: '+r.id+'</strong>');
                        }else{
                            $('.cardAlert').addClass('alert-danger');
                            $('.cardAlert').html('<strong>'+r.message+'</strong>');
                        }
                    }
                });

            }
        }

        $('input[data-checkout="cardNumber"]').keyup(function () {
            guessingPaymentMethod();
        });


        $('input[data-checkout="cardNumber"]').change(function () {
            guessingPaymentMethod();
        });


        function valid_credit_card(value) {
            // accept only digits, dashes or spaces
            if (/[^0-9-\s]+/.test(value)) return false;

            // The Luhn Algorithm. It's so pretty.
            var nCheck = 0, nDigit = 0, bEven = false;
            value = value.replace(/\D/g, "");

            for (var n = value.length - 1; n >= 0; n--) {
                var cDigit = value.charAt(n),
                    nDigit = parseInt(cDigit, 10);

                if (bEven) {
                    if ((nDigit *= 2) > 9) nDigit -= 9;
                }

                nCheck += nDigit;
                bEven = !bEven;
            }

            return (nCheck % 10) == 0;
        }

    })(jQuery);
</script>
</body>
</html>