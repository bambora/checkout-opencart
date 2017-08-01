<?php

/**
 * bambora_online_checkout short summary.
 *
 * bambora_online_checkout description.
 *
 * @version 1.0
 * @author al0830228
 */
class ControllerExtensionPaymentBamboraOnlineCheckout extends Controller
{

    const CHECKOUT_API_ENDPOINT = 'https://api.v1.checkout.bambora.com/session';
    const ZERO_API_MERCHANT_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';


    public function index()
    {
        $this->module_name = 'bambora_online_checkout';
        $this->language->load('extension/payment/bambora_online_checkout');
        $this->load->module('checkout/order');

        $data = array();
        $data['text_instruction'] = $this->language->get('text_instruction');
		$data['text_payment'] = $this->language->get('text_payment');

		$data['button_confirm'] = $this->language->get('button_confirm');
        //$data['button_back'] = $this->language->get('button_back');

		//$data['continue'] = $this->url->link('checkout/epay');

        //if($this->request->get['route'] == 'checkout/confirm') {
        //    $data['back'] = $this->url->link('checkout/payment');
        //} elseif ($this->request->get['route'] != 'checkout/guest_step_3') {
        //    $data['back'] = $this->url->link('checkout/confirm');
        //} else {
        //    $data['back'] = $this->url->link('checkout/guest_step_2');
        //}

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data['bambora_online_checkout_allowed_payment_type_ids'] = $this->getAllowedPaymentTypeIds($orderInfo['currency_code'], $orderInfo['total'], $orderInfo['order_id']);
        $data['bambora_online_checkout_window_state'] = $this->config->get('bambora_online_checkout_window_state');

        $checkoutSessionRequest = $this->createCheckoutSessionRequest($orderInfo);
        $checkoutSessionResponse = $this->sendApiRequest($checkoutSessionRequest, $this::CHECKOUT_API_ENDPOINT, 'POST');
        if($checkoutSessionResponse->meta->result == false){
            //Do error stuff
            return null;
        }

        $data['bambora_online_checkout_session_url'] = $checkoutSessionResponse->url;

        return $this->load->view('extension/payment/bambora_online_checkout.twig', $data);
    }



    private function getAllowedPaymentTypeIds($currency, $amount, $orderId)
    {
        $paymentCardIdsArray = array();
        $minorunits = $this->getCurrencyMinorunits($currency);
        $amountInMinorunits = $this->convertPriceToMinorunits($amount, $minorunits);

        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . "/paymenttypes?currency={$currency}&amount={$amountInMinorunits}";

        $paymentTypeResponse = $this->sendApiRequest(null, $endpoint, 'GET');
        if(!isset($paymentTypeResponse) || $paymentTypeResponse->meta->result == false) {
            $errorMessage = isset($paymentTypeResponse) ? $paymentTypeResponse->meta->message->merchant : "Could not connect to Bambora";
            $this->log->write("Get allowed payment types failed for order: {$orderId} Reason: {$errorMessage}");
        } else {
            foreach ($paymentTypeResponse->paymentCollections as $payment) {
                foreach ($payment->paymentGroups as $group) {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
        }

        return $paymentCardIdsArray;
    }



    public function callback()
    {
        $getParameteres = $_GET;
        $message = "";
        $transaction = null;
        if(!$this->validateCallback($getParameteres, $message, $transaction)) {
            $orderId = array_key_exists('orderid', $getParameteres) ? $getParameteres['orderid'] : -1;
            $errorMessage = "Callback failed for order: {$orderId}. Reason: {$message}";
            $this->log->write($errorMessage);
            if($orderId != -1) {
                $this->model_checkout_order->addOrderHistory($orderId, "pending", $errorMessage);
            }
            $this->setResponseHeaders(500);
            die($errorMessage);
        }

        $this->language->load('extension/payment/bambora_online_checkout');
        $this->load->model('checkout/order');

        $minorunits = $transaction->currency->minorunits;
        $amount = $this->convertPriceFromMinorUnits($transaction->total->authorized, $minorunits);


        $amountFormatted = $this->currency->format($amount, $transaction->currency->code, false, true);

        $comment = $this->language->get('payment_process') . $amountFormatted;
		$comment .= $this->language->get('payment_with_transactionid') . $transaction->id;
		$comment .= $this->language->get('payment_card') . $transaction->information->paymenttypes[0]->displayname . ' ' . $transaction->information->primaryaccountnumbers[0]->number;

        $this->model_checkout_order->addOrderHistory($transaction->orderid, $this->config->get('bambora_online_checkout_order_status_completed'), $comment, true);

        $this->setResponseHeaders(200);
        die("The callback was a success");
    }

    private function setResponseHeaders($responseCode)
    {
        header('X-EPay-System: ' . $this->getModuleHeaderInformation(), true, $responseCode);
    }



    private function validateCallback($getParameteres, &$message, &$transaction)
    {
        // Check exists txnid!
        if (empty($getParameteres["txnid"])) {
            $message = "No GET(txnid) was supplied to the system!";
            return false;
        }
        // Check exists orderid!
        if (empty($getParameteres["orderid"])) {
            $message = "No GET(orderid) was supplied to the system!";
            return false;
        }
        // Check exists hash!
        if (empty($getParameteres["hash"])) {
            $message = "No GET(hash) was supplied to the system!";
            return false;
        }
        // Validate MD5!
        $merchantMd5Key = $this->config->get('bambora_online_checkout_md5');
        $concatenatedValues  = '';
        foreach($getParameteres as $key => $value) {
            if ('hash' !== $key) {
                $concatenatedValues .= $value;
            }
        }
        $genstamp = md5($concatenatedValues . $merchantMd5Key);
        if (!hash_equals($genstamp, $getParameteres["hash"])) {
            $message = "Hash validation failed - Please check your MD5 key";
            return false;
        }

        $transactionId = $getParameteres["txnid"];
        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . '/transactions' . $transactionId;
        $transactionResponse = $this->sendApiRequest(null, $endpoint, 'GET');

        if($transactionResponse->meta->result == false) {
            $message = $transactionResponse->meta->message->merchant;
            return false;
        }
        $transaction = $transactionResponse->transaction;

        return true;
    }












    private function createCheckoutSessionRequest($orderInfo)
    {
        $minorunits = $this->getCurrencyMinorunits($orderInfo['currency_code']);
        $totalAmountInMinorunits = $this->convertPriceToMinorunits($orderInfo['total'], $minorunits);
        $shippingMethod = $this->cart->session->data['shipping_method'];
        $taxInfo = $this->tax->getRates($orderInfo['total'], $orderInfo['tax_class_id']);
        $shippingTaxAmount = $taxInfo['amount'];
        $productsTaxAmount = $this->cart->getTaxes();
        $totalVatAmountInMinorunits = $this->convertPriceToMinorunits($productsTaxAmount + $shippingTaxAmount, $minorunits);




        $params = array();
        $params['language'] = $this->language->get('code');
        $params['instantcaptureamount'] = $this->config->get('bambora_online_checkout_instantcapture') === 1 ? $totalAmountInMinorunits  : 0;
        $params['paymentwindowid'] = $this->config->get('bambora_online_checkout_paymentwindowid');


        $params['customer'] = array();
        $params['customer']['phonenumbercountrycode'] = $orderInfo['payment_iso_code_3'];
        $params['customer']['phonenumber'] = $orderInfo['telephone'];
        $params['customer']['email'] = $orderInfo['email'];

        $params['order'] = array();
        $params['order']['id'] = $orderInfo['order_id'];
        $params['order']['amount'] = $totalAmountInMinorunits;
        $params['order']['vatamount'] = $totalVatAmountInMinorunits;
        $params['order']['currency'] = $orderInfo['currency_code'];
        $params['order']['shippingaddress'] = $this->createCustomerAddress($orderInfo, 'shipping');
        $params['order']['billingaddress'] = $this->createCustomerAddress($orderInfo, 'payment');
        $params['order']['lines'] = $this->createOrderLines($orderInfo, $shippingMethod, $minorunits);

        $params['url'] = array();
        $params['url']['immediateredirecttoaccept'] = $this->config->get('bambora_online_checkout_immediateredirecttoaccept');
        //$params['url']['accept'] = $this->url->link('extension/payment/bambora_online_checkout/accept', '', 'SSL');
        $params['url']['accept'] = $this->url->link('checkout/success', '', 'SSL');
        $params['url']['cancel'] = $this->url->link('checkout/checkout', '', 'SSL');
        $params['url']['callbacks'] = array();
        $params['url']['callbacks']['url'] = $this->url->link('extension/payment/bambora_online_checkout/callback', '', 'SSL');


        return $params;

    }

    private function createOrderLines($orderInfo, $shippingMethod, $minorunits)
    {
        $params = array();
        $lineNumber = 1;
        //Add product lines
        foreach($this->cart->getProducts() as $product)
        {
            $line = array();
            $line["id"] = $product['product_id'];
            $line["linenumber"] = $lineNumber;
            $line["description"] = $product['name'];
            $line["text"] = $product['name'];
            $line["quantity"] = $product['quantity'];
            $line["unit"] = "pcs.";

            $taxInfo = $this->tax->getRates($product['total'], $product['tax_class_id']);
            $priceWithoutTax = $product['total'] - $taxInfo['amount'];

            $line["totalprice"] = $this->convertPriceToMinorunits($priceWithoutTax, $minorunits);
            $line["totalpriceinclvat"] = $this->convertPriceToMinorunits($product['total'], $minorunits);
            $line["totalpricevatamount"] = $this->convertPriceToMinorunits($taxInfo['amount'], $minorunits);
            $line["vat"] = $taxInfo['rate'];

            $params[] = $line;
            $lineNumber++;
        }

        //Add shipping
        $shipping = array();
        $shipping["id"] = $shippingMethod['code'];
        $shipping["linenumber"] = $lineNumber;
        $shipping["description"] = $shippingMethod['title'];
        $shipping["text"] = $shippingMethod['title'];
        $shipping["quantity"] = 1;
        $shipping["unit"] = "pcs.";

        $taxInfo = $this->tax->getRates($shippingMethod['cost'], $shippingMethod['tax_class_id']);
        $shippingWithTax = $shippingMethod['cost'] + $taxInfo['amount'];

        $shipping["totalprice"] = $this->convertPriceToMinorunits($shippingMethod['cost'], $minorunits);
        $shipping["totalpriceinclvat"] = $this->convertPriceToMinorunits($shippingWithTax, $minorunits);
        $shipping["totalpricevatamount"] = $this->convertPriceToMinorunits($taxInfo['amount'], $minorunits);
        $shipping["vat"] = $taxInfo['rate'];

        $params[] = $shipping;

        return $params;
    }

    private function createCustomerAddress($orderInfo, $type)
    {
        $params = array();
        $params["att"] = "";
        $params["firstname"] = $orderInfo[$type.'_firstname'];
        $params["lastname"] = $orderInfo[$type.'_lastname'];
        $params["street"] = $orderInfo[$type.'_address_1'];
        $params["zip"] = $orderInfo[$type.'_postcode'];
        $params["city"] = $orderInfo[$type.'_city'];
        $params["country"] = $orderInfo[$type.'_iso_code_3'];

        return $params;
    }



    private function sendApiRequest($request, $endpoint, $type)
    {
        if(!isset($request)) {
            return null;
        }
        $requestJson = json_encode($request);
        $contentLength = strlen($requestJson);
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: ' . $contentLength,
            'Accept: application/json',
            'Authorization: ' . $this->getApiKey(),
            'X-EPay-System: ' . $this->getModuleHeaderInformation(),
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestJson);
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $responseJson = curl_exec($curl);

        return json_decode($responseJson);
    }

    private function getApiKey()
    {
        $accesstoken = $this->config->get('bambora_online_checkout_accesstoken');
        $merchantNumber = $this->config->get('bambora_online_checkout_merchant');;
        $secretToken = $this->config->get('bambora_online_checkout_secrettoken');


        $combined = $accesstoken . '@' . $merchantNumber . ':' . $secretToken;
        $encoded_key = base64_encode( $combined );
        $api_key = 'Basic ' . $encoded_key;
        return $api_key;
    }

    private function getModuleHeaderInformation()
    {
        $module_version = "";
        $openCartVersion = "";
        $phpVersion = phpversion();
        $headerInformation = 'OpenCart/' . $openCartVersion . ' Module/' . $module_version . ' PHP/'.$phpVersion;
        return $headerInformation;
    }

    /**
     * Convert Price To MinorUnits
     *
     * @param mixed $amount
     * @param mixed $minorunits
     * @return double|integer
     */
    private function convertPriceToMinorunits($amount, $minorunits)
    {
        if ($amount == "" || $amount == null) {
            return 0;
        }
        $roundingMode = $this->config->get('bambora_online_checkout_roundingmode');
        switch ($roundingMode) {
            case 2:
                $amount = ceil($amount * pow(10, $minorunits));
                break;
            case 3:
                $amount = floor($amount * pow(10, $minorunits));
                break;
            default:
                $amount = round($amount * pow(10, $minorunits));
                break;
        }
        return $amount;
    }
    /**
     * Convert Price From MinorUnits
     *
     * @param mixed $amount
     * @param mixed $minorunits
     * @return string
     */
    private function convertPriceFromMinorunits($amount, $minorunits, $decimal_seperator = '.')
    {
        if (!isset($amount)) {
            return 0;
        }
        return number_format($amount / pow(10, $minorunits), $minorunits, $decimal_seperator, '');
    }
    /**
     * Get Currency Minorunits
     *
     * @param mixed $currencyCode
     * @return integer
     */
    private function getCurrencyMinorunits($currencyCode)
    {
        $currencyArray = array(
        'TTD' => 0, 'KMF' => 0, 'ADP' => 0, 'TPE' => 0, 'BIF' => 0,
        'DJF' => 0, 'MGF' => 0, 'XPF' => 0, 'GNF' => 0, 'BYR' => 0,
        'PYG' => 0, 'JPY' => 0, 'CLP' => 0, 'XAF' => 0, 'TRL' => 0,
        'VUV' => 0, 'CLF' => 0, 'KRW' => 0, 'XOF' => 0, 'RWF' => 0,
        'IQD' => 3, 'TND' => 3, 'BHD' => 3, 'JOD' => 3, 'OMR' => 3,
        'KWD' => 3, 'LYD' => 3);
        return key_exists($currencyCode, $currencyArray) ? $currencyArray[$currencyCode] : 2;
    }


}