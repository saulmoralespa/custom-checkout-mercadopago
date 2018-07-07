<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 5/07/18
 * Time: 01:13 PM
 */

require_once ('vendor/autoload.php');
include ('config.php');

$mp = new MP(CLIENT_ID, CLIENT_SECRET);
$access_token = $mp->get_access_token();
$mp = new MP(ACCESS_TOKEN);

if(isset($_POST)){

    $tokenCard = $_POST['tokenCard'];
    $preci = (int)$_POST['preci'];

    if (isset($_POST['email'])){
        $emailclient = $_POST['email'];
        $payment_method_id = $_POST['paymentMethodId'];
        $cardholderName = cleanSpace($_POST['cardholderName']);
        $first_six_digits = substr($cardholderName, 6);
        $last_four_digits = substr($cardholderName, -4);
        try{

            $customer = array (
                "email" => $emailclient
            );

            /**
             * seacrh if register customer if no register
             */

            $saved_customer = $mp->get ("/v1/customers/search", $customer);
            $customer_result = $saved_customer["response"]["results"];


            $cards = array();
            $newRegistered = false;

            if (!empty($customer_result)){
                $idCustomer = $customer_result[0]["id"];
                $cards = $mp->get ("/v1/customers/$idCustomer/cards");

            }else{
                $customer_new = $mp->post ("/v1/customers", $customer);
                $idCustomer = $customer_new['response']['id'];
                $newRegistered = true;
            }


            /***
             * We verify the cards that the client already has and verify that it is not registered
             *
             */

            $identifyCard = false;

            if (!empty($cards) && !empty($cards["response"])){

                foreach ($cards["response"] as $card) {
                    if ($card["first_six_digits"] == $first_six_digits && $card["last_four_digits"] == $last_four_digits){
                        $identify = true;
                        break;
                    }
                }

            }

            if ($newRegistered || !$identify){
                $card = $mp->post ("/v1/customers/".$idCustomer."/cards", array("token" => "$tokenCard"));
            }




            /**
             * Save tokenCard with relation customer email in dtabase
             * $tokenCard
             * $emailclient
             *
             */

            /**
             * execute first payment
             * take into account the currency_id
             *
             * depending on where the account is from mercadopago
             *
             *

            ARS
            Argentine peso
            BRL
            Brazilian real
            VEF
            Venezuelan strong bolivar
            CLP
            Chilean peso
            MXN
            Mexican peso
            COP
            Colombian peso
            PEN
            Peruvian sol
            UYU
            Uruguayan peso
             *
             *
             */


            $paramsPayment = array("amount" => $preci, "tokenCard" => "$tokenCard", "payment_method_id" => "$payment_method_id", "email" => "$emailclient", "id" => "$idCustomer");

            executePayment($paramsPayment);


        }catch (Exception $ex){
            echo json_encode(array('status' => false, 'message' => $ex->getMessage()));
        }
    }else{
        $idCustomer = $_POST['idCustomer'];
        $paramsPayment = array("amount" => $preci, "tokenCard" => "$tokenCard", "id" => "$idCustomer");
        executePayment($paramsPayment);

    }

}


function executePayment($params, $isSaveCard = false)
{
    global $mp;

    $payment_data = array();

    if ($isSaveCard){

        $payment_data = array(
            "transaction_amount" => $params['amount'],
            "token" => $params['tokenCard'],
            "description" => "Title of what you are paying for",
            "installments" => 1,
            "payer" => array (
                "type" => "customer",
                "id" => $params['id']
            ),
        );

    }else{
        $payment_data = array(
            "transaction_amount" => $params['amount'],
            "token" => $params['tokenCard'],
            "description" => "Title of what you are paying for",
            "installments" => 1,
            "payment_method_id" => $params['payment_method_id'],
            "payer" => array (
                "email" => $params['email'],
                "id" => $params['id']
            ),
        );
    }

    try{
        $payment = $mp->post("/v1/payments", $payment_data);
        /***
         * status
         *
         * example response with status pending
         *
         * [status] => in_process
        [status_detail] => pending_contingency
         *
         */


        echo json_encode(array('status' => true, 'id' => $payment['response']['id'], 'statusPayment' => $payment['response']['status_detail']));
    }catch (Exception $ex){
        echo json_encode(array('status' => false, 'message' => $ex->getMessage()));
    }
}

function cleanSpace($str)
{
    return str_replace(' ', '', $str);
}