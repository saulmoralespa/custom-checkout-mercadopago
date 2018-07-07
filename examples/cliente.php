<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 6/07/18
 * Time: 06:55 PM
 */

require_once('../vendor/autoload.php');
include('../config.php');

$mp = new MP(ACCESS_TOKEN);

$customer = array (
    "email" => "andresperez@gmail.com"
);


$saved_customer = $mp->get ("/v1/customers/search", $customer);
$customer_result = $saved_customer["response"]["results"];


if (!empty($customer_result)){
    $idCustomer = $customer_result[0]["id"];

    $cards = $mp->get ("/v1/customers/$idCustomer/cards");

}else{
    $customer_new = $mp->post ("/v1/customers", $customer);
    $idCustomer = $customer_new['response']['id'];
}

if (!empty($cards["response"])) {
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
    <li>
        <label>Payment Method:</label>
        <select id="cardId" name="cardId" data-checkout='cardId'>
            <?php foreach ($cards["response"] as $card) { ?>
                <option value="<?php echo $card["id"]; ?>"
                        first_six_digits="<?php echo $card["first_six_digits"]; ?>"
                        security_code_length="<?php echo $card["security_code"]["length"]; ?>">
                    <?php echo $card["payment_method"]["name"]; ?> ended in <?php echo $card["last_four_digits"]; ?>
                </option>
            <?php } ?>
        </select>
    </li>
    <li id="cvv">
        <label for="cvv">Security code:</label>
        <input type="text" id="cvv" data-checkout="securityCode" placeholder="123"/>
    </li>
            <li>
                <label for="docNumber">precio:</label>
                <input type="number" name="preci" min="20000" minlength="5" placeholder="100000" required />
            </li>
            <input type="hidden" name="idCustomer" value="<?php echo $idCustomer; ?>">
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




        $('select[data-checkout="cardId"]').change(function (){
            let card = $('select[data-checkout="cardId"]');

            let security_code_length = $(this).find(':selected').attr('security_code_length');

            if(security_code_length == 0){
                $('#cvv').hide();
            }else{
                $('#cvv').show();
            }
        });


        $("form#pay").submit(function (e){
            e.preventDefault();
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


    })(jQuery);
</script>
</body>
    </html>
    <?php
}else{
    echo "Aun no hay tarjetas guardadas";
}
    ?>