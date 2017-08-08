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

    const CHECKOUT_API_ENDPOINT = 'https://api.v1.checkout.bambora.com/sessions';
    const ZERO_API_MERCHANT_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';

    /**
     * @var string
     */
    private $module_version = '0.1.0';

    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

    public function index()
    {
        $this->load->language('extension/payment/' . $this->module_name);

        $data = array();
        $data['text_title'] = $this->config->get('payment_'.$this->module_name .'_payment_method_title');
        $data['text_payment'] = $this->language->get('text_payment');
		$data['button_confirm'] = $this->language->get('button_confirm');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data[$this->module_name . '_allowed_payment_type_ids'] = $this->getAllowedPaymentTypeIds($orderInfo['currency_code'], $orderInfo['total'], $orderInfo['order_id']);
        $data[$this->module_name . '_window_state'] = $this->config->get('payment_'.$this->module_name . '_window_state');

        return $this->load->view('extension/payment/'.$this->module_name, $data);
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/' . $this->module_name);
        $json = array();

        $checkoutSessionRequest = $this->createCheckoutSessionRequest();
        $checkoutSessionResponse = $this->sendApiRequest($checkoutSessionRequest, $this::CHECKOUT_API_ENDPOINT, 'POST');
        if(!$checkoutSessionResponse || $checkoutSessionResponse->meta->result == false){
            //Do error stuff
            $json['error'] = $this->language->get('error_payment_window') . ' ' . $checkoutSessionResponse->meta->message->enduser;
        }  else {
            $json['url'] = $checkoutSessionResponse->url;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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
            foreach ($paymentTypeResponse->paymentcollections as $payment) {
                foreach ($payment->paymentgroups as $group) {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
        }

        return $paymentCardIdsArray;
    }



    public function callback()
    {
        $this->language->load('extension/payment/bambora_online_checkout');
        $this->load->model('checkout/order');

        $getParameteres = $_GET;
        $message = "";
        $transaction = null;
        if(!$this->validateCallback($getParameteres, $message, $transaction)) {
            $orderId = array_key_exists('orderid', $getParameteres) ? $getParameteres['orderid'] : -1;
            $errorMessage = "Callback failed for order: {$orderId}. Reason: {$message}";
            $this->log->write($errorMessage);
            if($orderId != -1) {
                $this->model_checkout_order->addOrderHistory($orderId, 1, $errorMessage);
            }
            $this->setResponseHeaders(500);
            die($errorMessage);
        }

        //Lock for multiple callbacks on already confirmed payment
        $orderInfo = $this->model_checkout_order->getOrder($transaction->orderid);
        if($orderInfo['order_status_id'] === $this->config->get('payment_' . $this->module_name . '_order_status_completed')) {
            $this->setResponseHeaders(200);
            die("The callback was a success - Order already created");
        }

        $minorunits = $transaction->currency->minorunits;
        $amount = $this->convertPriceFromMinorUnits($transaction->total->authorized, $minorunits);

        // Add surcharge fee to the order
        if($this->config->get('payment_' . $this->module_name . '_surcharge') == 1 && $transaction->total->feeamount > 0) {
            $this->addSurchargeToOrderTotals($transaction, $orderInfo['order_status_id']);
        }


        $amountFormatted = $this->currency->format($amount, $transaction->currency->code, false, true);
        $paymentInfo = $transaction->information->paymenttypes[0]->displayname . ' ' . $transaction->information->primaryaccountnumbers[0]->number;
        $comment = '<table style="width: 60%"><tbody>';
        $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_process') . '</b></td><td>' . $amountFormatted . '</td></tr>';
        $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_with_transactionid') . '</b></td><td>' . $transaction->id . '</td></tr>';
        $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_card') . '</b></td><td>' . $paymentInfo . '</td></tr>';
        $comment .= '</tbody></table>';

        $this->model_checkout_order->addOrderHistory($transaction->orderid, $this->config->get('payment_' . $this->module_name . '_order_status_completed'), $comment, true);

        $this->setResponseHeaders(200);
        die("The callback was a success");
    }

    private function addSurchargeToOrderTotals($transaction, $currentStatusId)
    {

        $this->load->language('extension/total/bambora_online_checkout_fee');

        $transactionFee = $this->convertPriceFromMinorunits($transaction->total->feeamount, $transaction->currency->minorunits);

        $orderTotals = $this->model_checkout_order->getOrderTotals($transaction->orderid);
        $orderTotals[] = array(
        'order_id' => $transaction->orderid,
        'code' => 'bambora_online_checkout_fee',
        'title' => $this->language->get('bambora_online_checkout_fee') . ' ' . $transaction->information->paymenttypes[0]->displayname,
        'value' => (float)$transactionFee,
        'sort_order' => 8
        );

        if(count($orderTotals) > 1) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$transaction->orderid . "'");

            foreach ($orderTotals as $total) {
                // Add fee to the total price
                if ($total['code'] === 'total') {
                    $total['value'] += $transactionFee;

                    // Update the order entry
                    $this->db->query("UPDATE " . DB_PREFIX . "order SET total = '". $total['value'] . "' WHERE order_id = '" . (int)$transaction->orderid . "'");
                }
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$transaction->orderid . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
            $transactionFeeFormatted = $this->currency->format($transactionFee, $transaction->currency->code, false, true);
            $message = "Surcharge fee of {$transactionFeeFormatted} added to the order ";

            $this->model_checkout_order->addOrderHistory($transaction->orderid, $currentStatusId, $message);
        }
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
        $merchantMd5Key = $this->config->get('payment_'.$this->module_name.'_md5');
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
        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . '/transactions/' . $transactionId;
        $transactionResponse = $this->sendApiRequest(null, $endpoint, 'GET');

        if(!isset($transactionResponse) || !$transactionResponse->meta->result) {
            $message = isset($transactionResponse) ? $transactionResponse->meta->message->merchant : "Connection to Bambora Failed";
            return false;
        }
        $transaction = $transactionResponse->transaction;

        return true;
    }

    private function createCheckoutSessionRequest()
    {
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderTotals = $this->model_checkout_order->getOrderTotals($this->session->data['order_id']);
        $minorunits = $this->getCurrencyMinorunits($orderInfo['currency_code']);
        $orderTotalAmount = 0;
        $orderTaxAmount = 0;
        foreach($orderTotals as $total) {
            if($total['code'] === "tax") {
                $orderTaxAmount = $total['value'];
            } else if($total['code'] === "total") {
                $orderTotalAmount = $total['value'];
            }

        }


        $params = array();
        $params['language'] = $this->language->get('code');
        $params['instantcaptureamount'] = $this->config->get('payment_'.$this->module_name.'_instant_capture') === 1 ? $this->convertPriceToMinorunits($orderTotalAmount, $minorunits)  : 0;
        $params['paymentwindowid'] = $this->config->get('payment_' . $this->module_name . '_payment_window_id');


        $params['customer'] = array();
        $params['customer']['phonenumbercountrycode'] = $orderInfo['payment_iso_code_3'];
        $params['customer']['phonenumber'] = $orderInfo['telephone'];
        $params['customer']['email'] = $orderInfo['email'];

        $params['order'] = array();
        $params['order']['id'] = $orderInfo['order_id'];
        $params['order']['amount'] = $this->convertPriceToMinorunits($orderTotalAmount, $minorunits);
        $params['order']['vatamount'] = $this->convertPriceToMinorunits($orderTaxAmount, $minorunits);;
        $params['order']['currency'] = $orderInfo['currency_code'];
        $params['order']['shippingaddress'] = $this->createCustomerAddress($orderInfo, 'shipping');
        $params['order']['billingaddress'] = $this->createCustomerAddress($orderInfo, 'payment');
        $params['order']['lines'] = $this->createOrderLines($orderTotals, $minorunits);

        $params['url'] = array();
        $params['url']['immediateredirecttoaccept'] = $this->config->get('payment_'.$this->module_name.'_immediate_redirect_to_accept');
        //$params['url']['accept'] = $this->url->link('extension/payment/bambora_online_checkout/accept', '', 'SSL');
        $params['url']['accept'] = $this->url->link('checkout/success', '', true);
        $params['url']['cancel'] = $this->url->link('checkout/checkout', '', true);
        $params['url']['callbacks'] = array();
        $params['url']['callbacks'][] = array('url' => $this->url->link('extension/payment/' . $this->module_name . '/callback', '', true));


        return $params;

    }

    private function createOrderLines($orderTotals, $minorunits)
    {
        $orderProducts = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);
        $params = array();
        $lineNumber = 1;
        //Add product lines
        foreach($orderProducts as $product)
        {
            $line = array();
            $line['id'] = $product['product_id'];
            $line['linenumber'] = $lineNumber;
            $line['description'] = $product['name'];
            $line['text'] = $product['name'];
            $line['quantity'] = $product['quantity'];
            $line['unit'] = $this->language->get('pcs');
            $line['totalprice'] = $this->convertPriceToMinorunits($product['total'], $minorunits);
            $line['totalpriceinclvat'] = $this->convertPriceToMinorunits($product['total'] + ($product['tax'] * $product['quantity']), $minorunits);
            $line['totalpricevatamount'] = $this->convertPriceToMinorunits($product['tax'], $minorunits);
            $line['vat'] = $product['tax'] > 0 ? round((($product['tax'] * $product['quantity']) / $product['total']) * 100) : 0;

            $params[] = $line;
            $lineNumber++;
        }

        $shippingMethod = $this->cart->session->data['shipping_method'];

        if(!empty($shippingMethod['cost']) && $shippingMethod['cost'] > 0) {
            //Add shipping
            $shipping = array();
            $shipping['id'] = $shippingMethod['code'];
            $shipping['linenumber'] = $lineNumber;
            $shipping['description'] = $shippingMethod['title'];
            $shipping['text'] = $shippingMethod['title'];
            $shipping['quantity'] = 1;
            $shipping['unit'] = $this->language->get('pcs');

            $shippingTaxArray = $this->tax->getRates($shippingMethod['cost'], $shippingMethod['tax_class_id']);
            $shippingTaxAmount = 0;
            $shippingTaxRate = 0;
            foreach($shippingTaxArray as $shippingTax) {
                $shippingTaxAmount += $shippingTax['amount'];
                $shippingTaxRate = $shippingTax['rate'];
            }

            $shippingWithTax = $shippingMethod['cost'] + $shippingTaxAmount;

            $shipping['totalprice'] = $this->convertPriceToMinorunits($shippingMethod['cost'], $minorunits);
            $shipping['totalpriceinclvat'] = $this->convertPriceToMinorunits($shippingWithTax, $minorunits);
            $shipping['totalpricevatamount'] = $this->convertPriceToMinorunits($shippingTaxAmount, $minorunits);
            $shipping['vat'] = $shippingTaxRate;

            $params[] = $shipping;
            $lineNumber++;
        }

        $orderTotalDiscount = null;
        $orderTotalVoucher = null;
        foreach($orderTotals as $total) {
            if($total['code'] === "coupon") {
                $orderTotalDiscount = $total;
            } else if($total['code'] === "voucher") {
                $orderTotalVoucher = $total;
            }
        }

        if(!empty($orderTotalDiscount) && (float)$orderTotalDiscount['value'] < 0) {
            $coupon = array();
            $coupon['id'] = $orderTotalDiscount['code'];
            $coupon['linenumber'] = $lineNumber;
            $coupon['description'] = $orderTotalDiscount['title'];
            $coupon['text'] = $orderTotalDiscount['title'];
            $coupon['quantity'] = 1;
            $coupon['unit'] = $this->language->get('pcs');
            $coupon['totalprice'] = $this->convertPriceToMinorunits($orderTotalDiscount['value'], $minorunits);;
            $coupon['totalpriceinclvat'] = $this->convertPriceToMinorunits($orderTotalDiscount['value'], $minorunits);
            $coupon['totalpricevatamount'] = 0;
            $coupon['vat'] = 0;
            $params[] = $coupon;
            $lineNumber++;
        }

        if(!empty($orderTotalVoucher) && (float)$orderTotalVoucher['value'] < 0) {
            $voucher = array();
            $voucher['id'] = $orderTotalVoucher['code'];
            $voucher['linenumber'] = $lineNumber;
            $voucher['description'] = $orderTotalVoucher['title'];
            $voucher['text'] = $orderTotalVoucher['title'];
            $voucher['quantity'] = 1;
            $voucher['unit'] = $this->language->get('pcs');
            $voucher['totalprice'] = $this->convertPriceToMinorunits($orderTotalVoucher['value'], $minorunits);;
            $voucher['totalpriceinclvat'] = $this->convertPriceToMinorunits($orderTotalVoucher['value'], $minorunits);
            $voucher['totalpricevatamount'] = 0;
            $voucher['vat'] = 0;
            $params[] = $voucher;
        }

        return $params;
    }

    private function createCustomerAddress($orderInfo, $type)
    {
        $params = array();
        $params['att'] = "";
        $params['firstname'] = $orderInfo[$type.'_firstname'];
        $params['lastname'] = $orderInfo[$type.'_lastname'];
        $params['street'] = $orderInfo[$type.'_address_1'];
        $params['zip'] = $orderInfo[$type.'_postcode'];
        $params['city'] = $orderInfo[$type.'_city'];
        $params['country'] = $orderInfo[$type.'_iso_code_3'];

        return $params;
    }



    private function sendApiRequest($request, $endpoint, $type)
    {
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
        $accesstoken = $this->config->get('payment_'.$this->module_name.'_access_token');
        $merchantNumber = $this->config->get('payment_'.$this->module_name.'_merchant');;
        $secretToken = $this->config->get('payment_'.$this->module_name.'_secret_token');


        $combined = $accesstoken . '@' . $merchantNumber . ':' . $secretToken;
        $encoded_key = base64_encode( $combined );
        $api_key = 'Basic ' . $encoded_key;
        return $api_key;
    }

    private function getModuleHeaderInformation()
    {
        $headerInformation = 'OpenCart/' . VERSION . ' Module/' . $this->module_version . ' PHP/'. phpversion();

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
        $roundingMode = $this->config->get('payment_'.$this->module_name.'_rounding_mode');
        switch ($roundingMode) {
            case 'up':
                $amount = ceil($amount * pow(10, $minorunits));
                break;
            case 'down':
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