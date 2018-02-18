<?php
/**
 * @package     Bambora Online Checkout for OpenCart
 * @author      Bambora Online (https://bambora.com)
 * @copyright   Copyright (C) 2017-2018 Bambora Online. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

class ControllerExtensionPaymentBamboraOnlineCheckout extends Controller
{
    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

    #region Actions

    /**
     * Inits the payment method
     *
     * @return mixed
     */
    public function index()
    {
        $this->load->language('extension/payment/' . $this->module_name);

        $data = array();
        $data['text_title'] = $this->config->get($this->getConfigBaseName() .'_payment_method_title');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_payment'] = $this->language->get('text_payment');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data[$this->module_name . '_allowed_payment_type_ids'] = $this->getAllowedPaymentTypeIds($orderInfo['currency_code'], $orderInfo['total'], $orderInfo['order_id']);
        $data[$this->module_name . '_window_state'] = $this->config->get($this->getConfigBaseName() . '_window_state');

        return $this->load->view('extension/payment/'.$this->module_name, $data);
    }

    /**
     * Init and open the payment window
     */
    public function confirm()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/bambora_online_checkout');
        $this->load->language('extension/payment/' . $this->module_name);

        $checkoutSessionRequest = $this->createCheckoutSessionRequest();
        $checkoutSessionResponse = $this->model_extension_payment_bambora_online_checkout->setCheckoutSession($checkoutSessionRequest);

        $json = array();
        if (!$checkoutSessionResponse || $checkoutSessionResponse->meta->result == false) {
            $json['error'] = $this->language->get('error_payment_window') . ' ' . $checkoutSessionResponse->meta->message->enduser;
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($this->language->get('error_payment_window') . ' ' . $checkoutSessionResponse->meta->message->merchant);
        } else {
            $json['url'] = $checkoutSessionResponse->url;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * When a customer returns from a successfull payment
     */
    public function accept()
    {
        $this->language->load('extension/payment/bambora_online_checkout');
        $this->load->model('checkout/order');

        $getParameteres = $_GET;
        $message = "";
        if ($this->validateRequest($getParameteres, $message)) {
            $orderInfo = $this->model_checkout_order->getOrder($getParameteres['orderid']);
            if ($orderInfo['order_status_id'] !== $this->config->get($this->getConfigBaseName() . '_order_status_completed')) {
                $message = $this->language->get('accept_order_text');
                $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], 1, $message);
            }
        }
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    /**
     * Handles the callback comming from Bambora
     */
    public function callback()
    {
        $this->language->load('extension/payment/bambora_online_checkout');
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/bambora_online_checkout');

        $getParameteres = $_GET;
        try {
            $message = "";
            $transaction = null;
            if (!$this->validateCallback($getParameteres, $message, $transaction)) {
                $orderId = array_key_exists('orderid', $getParameteres) ? $getParameteres['orderid'] : -1;
                $errorMessage = "Callback failed for order: {$orderId}. Reason: {$message}";

                if ($orderId != -1) {
                    $this->model_checkout_order->addOrderHistory($orderId, 1, $errorMessage);
                }
                $this->model_extension_payment_bambora_online_checkout->bamboraLog($errorMessage);
                header('X-EPay-System: ' . $this->model_extension_payment_bambora_online_checkout->getModuleHeaderInformation(), true, 500);
                die($errorMessage);
            }

            //Lock for multiple callbacks on already confirmed payment
            $orderInfo = $this->model_checkout_order->getOrder($transaction->orderid);
            if ($orderInfo['order_status_id'] === $this->config->get($this->getConfigBaseName() . '_order_status_completed')) {
                header('X-EPay-System: ' . $this->model_extension_payment_bambora_online_checkout->getModuleHeaderInformation(), true, 200);
                die("The callback was a success - Order already created");
            }

            $decimalPoint = $this->language->get('currency_decimal_point');
            $thousandSeparator = $this->language->get('currency_thousand_separator');
            $minorunits = $transaction->currency->minorunits;
            $amount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->total->authorized, $minorunits, $decimalPoint, $thousandSeparator);

            // Add surcharge fee to the order
            if ($this->is_oc_3() && ($this->config->get($this->getConfigBaseName() . '_surcharge') == 1 && $transaction->total->feeamount > 0 )) {
                $this->addSurchargeToOrderTotals($transaction, $orderInfo['order_status_id']);
            }

            // Add transaction to database
            $this->model_extension_payment_bambora_online_checkout->addDbTransaction($transaction->orderid, $transaction->id, $transaction->total->authorized, $transaction->currency->code);

            $paymentInfo = $transaction->information->paymenttypes[0]->displayname . ' ' . $transaction->information->primaryaccountnumbers[0]->number;
            $comment = '<table style="width: 60%"><tbody>';
            $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_process') . '</b></td><td>' . $transaction->currency->code . ' ' . $amount . '</td></tr>';
            $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_with_transactionid') . '</b></td><td>' . $transaction->id . '</td></tr>';
            $comment .= '<tr><td>'. '<b>'.$this->language->get('payment_card') . '</b></td><td>' . $paymentInfo . '</td></tr>';
            $comment .= '</tbody></table>';

            $this->model_checkout_order->addOrderHistory($transaction->orderid, $this->config->get($this->getConfigBaseName() . '_order_status_completed'), $comment, true);

            header('X-EPay-System: ' . $this->model_extension_payment_bambora_online_checkout->getModuleHeaderInformation(), true, 200);
            die("The callback was a success");
        }
        catch(Exception $ex) {
            header('X-EPay-System: ' . $this->model_extension_payment_bambora_online_checkout->getModuleHeaderInformation(), true, 500);
            $errorMessage = "An error occured: " . $ex->getMessage();
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($errorMessage);
            $orderId = array_key_exists('orderid', $getParameteres) ? $getParameteres['orderid'] : -1;
            if ($orderId != -1) {
                $this->model_checkout_order->addOrderHistory($orderId, 1, $errorMessage);
            }
            die($errorMessage);
        }
    }

    #endregion

    /**
     * Returns an array of allowed payment type id's
     *
     * @param mixed $currency
     * @param mixed $amount
     * @param mixed $orderId
     * @return array
     */
    protected function getAllowedPaymentTypeIds($currency, $amount, $orderId)
    {
        $this->load->model('extension/payment/bambora_online_checkout');

        $paymentCardIdsArray = array();
        $minorunits = $this->model_extension_payment_bambora_online_checkout->getCurrencyMinorunits($currency);
        $amountInMinorunits = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($amount, $minorunits);
        $paymentTypeResponse = $this->model_extension_payment_bambora_online_checkout->getPaymentTypeIds($currency, $amountInMinorunits);

        if (!isset($paymentTypeResponse) || $paymentTypeResponse->meta->result == false) {
            $errorMessage = "Get allowed payment types failed for order: {$orderId} Reason: ";
            $errorMessage .= isset($paymentTypeResponse) ? $paymentTypeResponse->meta->message->merchant : "Could not connect to Bambora";
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($errorMessage);
        } else {
            foreach ($paymentTypeResponse->paymentcollections as $payment) {
                foreach ($payment->paymentgroups as $group) {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
        }

        return $paymentCardIdsArray;
    }

    /**
     * Create Bambora Online Checkout session request
     *
     * @return array
     */
    protected function createCheckoutSessionRequest()
    {
        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $minorunits = $this->model_extension_payment_bambora_online_checkout->getCurrencyMinorunits($orderInfo['currency_code']);
        $orderTotalAmount = 0;
        $orderTaxAmount = 0;
        $orderTotals = array();
        if($this->is_oc_3()) {
            $orderTotals = $this->model_checkout_order->getOrderTotals($this->session->data['order_id']);
        } else {
            $orderTotalTemp = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$this->session->data['order_id'] . "'");
            $orderTotals = isset($orderTotalTemp) ? $orderTotalTemp->rows : array();
        }

        foreach ($orderTotals as $total) {
            if ($total['code'] === "tax") {
                $orderTaxAmount += $total['value'];
            } elseif ($total['code'] === "total") {
                $orderTotalAmount = $total['value'];
            }
        }

        //Format for currency
        $orderTaxAmount = $this->formatForCurrency($orderTaxAmount);
        $orderTotalAmount = $this->formatForCurrency($orderTotalAmount);

        $params = array();
        $params['language'] = $this->language->get('code');
        $params['instantcaptureamount'] = $this->config->get($this->getConfigBaseName() .'_instant_capture') === "1" ? $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($orderTotalAmount, $minorunits)  : 0;
        $params['paymentwindowid'] = $this->config->get($this->getConfigBaseName() . '_payment_window_id');

        $params['customer'] = array();
        $params['customer']['phonenumbercountrycode'] = html_entity_decode($orderInfo['payment_iso_code_2'], ENT_QUOTES, 'UTF-8');
        $params['customer']['phonenumber'] = html_entity_decode($orderInfo['telephone'], ENT_QUOTES, 'UTF-8');
        $params['customer']['email'] = html_entity_decode($orderInfo['email'], ENT_QUOTES, 'UTF-8');

        $params['order'] = array();
        $params['order']['id'] = $orderInfo['order_id'];
        $params['order']['amount'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($orderTotalAmount, $minorunits);
        $params['order']['vatamount'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($orderTaxAmount, $minorunits);

        $params['order']['currency'] = $orderInfo['currency_code'];
        $params['order']['shippingaddress'] = $this->createCustomerAddress($orderInfo, 'shipping');
        $params['order']['billingaddress'] = $this->createCustomerAddress($orderInfo, 'payment');
        $params['order']['lines'] = $this->createOrderLines($orderTotals, $minorunits);

        $params['url'] = array();
        $params['url']['immediateredirecttoaccept'] = $this->config->get($this->getConfigBaseName() . '_immediate_redirect_to_accept');
        $params['url']['accept'] = $this->url->link('extension/payment/bambora_online_checkout/accept', '', true);
        $params['url']['cancel'] = $this->url->link('checkout/checkout', '', true);
        $params['url']['callbacks'] = array();
        $params['url']['callbacks'][] = array('url' => $this->url->link('extension/payment/' . $this->module_name . '/callback', '', true));

        return $params;
    }

    /**
     * Create the custommer shipping or billing address for the payment request
     *
     * @param mixed $orderInfo
     * @param mixed $type
     * @return string[]
     */
    protected function createCustomerAddress($orderInfo, $type)
    {
        $params = array();
        $params['att'] = "";
        $params['firstname'] = html_entity_decode($orderInfo[$type.'_firstname'], ENT_QUOTES, 'UTF-8');
        $params['lastname'] = html_entity_decode($orderInfo[$type.'_lastname'], ENT_QUOTES, 'UTF-8');
        $params['street'] = html_entity_decode($orderInfo[$type.'_address_1'], ENT_QUOTES, 'UTF-8');
        $params['zip'] = html_entity_decode($orderInfo[$type.'_postcode'], ENT_QUOTES, 'UTF-8');
        $params['city'] = html_entity_decode($orderInfo[$type.'_city'], ENT_QUOTES, 'UTF-8');
        $params['country'] = html_entity_decode($orderInfo[$type.'_iso_code_3'], ENT_QUOTES, 'UTF-8');

        return $params;
    }

    /**
     * Create the order lines for the payment request
     *
     * @param mixed $orderTotals
     * @param mixed $minorunits
     * @return array[]
     */
    protected function createOrderLines($orderTotals, $minorunits)
    {
        $orderProducts = array();
        if($this->is_oc_3()) {
            $orderProducts = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);
        } else {
            $orderProducts = $this->cart->getProducts();
        }

        $params = array();
        $lineNumber = 1;
        //Add product lines
        foreach ($orderProducts as $product) {
            $line = array();
            $line['id'] = $product['product_id'];
            $line['linenumber'] = $lineNumber;
            $line['description'] = $product['name'];
            $line['text'] = $product['name'];
            $line['quantity'] = $product['quantity'];
            $line['unit'] = $this->language->get('pcs');
            $lineTaxAmount = 0;
            if($this->is_oc_3()) {
                $lineTaxAmount = $product['tax'] * $product['quantity'];
            } else {
                $lineTaxAmount = $this->calculateLineTaxAmount($product);
            }
            $lineTotalPrice = $product['total'];
            $lineTotalPriceInclVat = $product['total'] + $lineTaxAmount;

            //Format for currency
            $lineTaxAmount = $this->formatForCurrency($lineTaxAmount);
            $lineTotalPrice = $this->formatForCurrency($lineTotalPrice);
            $lineTotalPriceInclVat = $this->formatForCurrency($lineTotalPriceInclVat);

            $line['totalprice'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($lineTotalPrice, $minorunits);
            $line['totalpriceinclvat'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($lineTotalPriceInclVat, $minorunits);
            $line['totalpricevatamount'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($lineTaxAmount, $minorunits);
            $line['vat'] = $lineTaxAmount > 0 ? ($lineTaxAmount / $product['total']) * 100 : 0;
            $params[] = $line;
            $lineNumber++;
        }

        $shipping = null;
        $orderTotalDiscount = null;
        $orderTotalVoucher = null;
        foreach ($orderTotals as $total) {
            if ($total['code'] === "coupon") {
                $orderTotalDiscount = $total;
            } elseif ($total['code'] === "voucher") {
                $orderTotalVoucher = $total;
            } elseif ($total['code'] === "shipping") {
                $shipping = $total;
            }
        }

        if (isset($shipping) && isset($this->cart->session->data['shipping_method'])) {
            $shippingMethod = $this->cart->session->data['shipping_method'];
            if (!empty($shippingMethod['cost']) && $shippingMethod['cost'] > 0) {
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
                foreach ($shippingTaxArray as $shippingTax) {
                    $shippingTaxAmount += $shippingTax['amount'];
                }
                $shippintTotalPrice = $shippingMethod['cost'];
                $shippingWithTax = $shippintTotalPrice + $shippingTaxAmount;
                //Format for currency
                $shippingTaxAmount = $this->formatForCurrency($shippingTaxAmount);
                $shippintTotalPrice = $this->formatForCurrency($shippintTotalPrice);
                $shippingWithTax = $this->formatForCurrency($shippingWithTax);

                $shipping['totalprice'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($shippintTotalPrice, $minorunits);
                $shipping['totalpriceinclvat'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($shippingWithTax, $minorunits);
                $shipping['totalpricevatamount'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($shippingTaxAmount, $minorunits);
                $shipping['vat'] = $shippingTaxAmount > 0 ? ($shippingTaxAmount / $shippingMethod['cost']) * 100 : 0;

                $params[] = $shipping;
                $lineNumber++;
            }
        }

        if (!empty($orderTotalDiscount) && (float)$orderTotalDiscount['value'] < 0) {
            $coupon = array();
            $coupon['id'] = $orderTotalDiscount['code'];
            $coupon['linenumber'] = $lineNumber;
            $coupon['description'] = $orderTotalDiscount['title'];
            $coupon['text'] = $orderTotalDiscount['title'];
            $coupon['quantity'] = 1;
            $coupon['unit'] = $this->language->get('pcs');
            $cuponTotalPrice = $orderTotalDiscount['value'];
            //Format for currency
            $cuponTotalPrice = $this->formatForCurrency($cuponTotalPrice);

            $coupon['totalprice'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($cuponTotalPrice, $minorunits);
            $coupon['totalpriceinclvat'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($cuponTotalPrice, $minorunits);
            $coupon['totalpricevatamount'] = 0;
            $coupon['vat'] = 0;
            $params[] = $coupon;
            $lineNumber++;
        }

        if (!empty($orderTotalVoucher) && (float)$orderTotalVoucher['value'] < 0) {
            $voucher = array();
            $voucher['id'] = $orderTotalVoucher['code'];
            $voucher['linenumber'] = $lineNumber;
            $voucher['description'] = $orderTotalVoucher['title'];
            $voucher['text'] = $orderTotalVoucher['title'];
            $voucher['quantity'] = 1;
            $voucher['unit'] = $this->language->get('pcs');
            $voucherTotalPrice = $orderTotalVoucher['value'];
            //Format for currency
            $voucherTotalPrice = $this->formatForCurrency($voucherTotalPrice);

            $voucher['totalprice'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($voucherTotalPrice, $minorunits);
            $voucher['totalpriceinclvat'] = $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($voucherTotalPrice, $minorunits);
            $voucher['totalpricevatamount'] = 0;
            $voucher['vat'] = 0;
            $params[] = $voucher;
        }

        return $params;
    }

    /**
     * Calculate the total line tax amount
     *
     * @param mixed $product
     * @return double|integer
     */
    protected function calculateLineTaxAmount($product)
    {
        $tax_class_id = $product['tax_class_id'];
        $totalAmount = $product['price'];
        $taxes = $this->tax->getRates($totalAmount, $tax_class_id);
        $taxAmount = 0;
        if(!empty($taxes) && is_array($taxes)) {
            foreach($taxes as $tax) {
                $taxAmount += $tax['amount'];
            }
        }
        return $taxAmount * $product['quantity'];

    }

    /**
     * Format the base amount to the currency based amount
     *
     * @param mixed $amount
     * @return mixed
     */
    protected function formatForCurrency($amount) {
        return $this->currency->format($amount, $this->session->data['currency'], '', false);
    }

    /**
     * Validate the request get parameteres
     *
     * @param mixed $getParameteres
     * @param mixed $message
     * @return boolean
     */
    protected function validateRequest($getParameteres, &$message)
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
        $merchantMd5Key = $this->config->get($this->getConfigBaseName() . '_md5');
        if(!empty($merchantMd5Key)) {
            $concatenatedValues  = '';
            foreach ($getParameteres as $key => $value) {
                if ('hash' !== $key) {
                    $concatenatedValues .= $value;
                }
            }
            $genstamp = md5($concatenatedValues . $merchantMd5Key);
            if (!hash_equals($genstamp, $getParameteres["hash"])) {
                $message = "Hash validation failed - Please check your MD5 key";
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the Callback from Bambora
     *
     * @param mixed $getParameteres
     * @param mixed $message
     * @param mixed $transaction
     * @return boolean
     */
    protected function validateCallback($getParameteres, &$message, &$transaction)
    {
        if (!$this->validateRequest($getParameteres, $message)) {
            return false;
        }

        $transactionId = $getParameteres["txnid"];
        $transactionResponse = $this->model_extension_payment_bambora_online_checkout->getTransaction($transactionId);

        if (!isset($transactionResponse) || !$transactionResponse->meta->result) {
            $message = isset($transactionResponse) ? $transactionResponse->meta->message->merchant : "Connection to Bambora Failed";
            return false;
        }
        $transaction = $transactionResponse->transaction;

        return true;
    }

    /**
     * Add surcharge fee to order totals
     *
     * @param mixed $transaction
     * @param mixed $currentStatusId
     */
    protected function addSurchargeToOrderTotals($transaction, $currentStatusId)
    {
        $this->load->language('extension/total/bambora_online_checkout_fee');
        $transactionFeeMinorUnits = $transaction->total->feeamount;
        $currencyValue = $this->currency->getValue($transaction->currency->code);
        $baseTransactionFee = $currencyValue > 0 ? ($transactionFeeMinorUnits / $currencyValue) : $transactionFeeMinorUnits;
        $transactionFee = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($baseTransactionFee, $transaction->currency->minorunits);

        $orderTotals = array();
        if($this->is_oc_3()) {
            $orderTotals = $this->model_checkout_order->getOrderTotals($transaction->orderid);
        } else {
            $orderTotalTemp = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$transaction->orderid . "'");
            $orderTotals = isset($orderTotalTemp) ? $orderTotalTemp->rows : array();
        }
        if(!empty($orderTotals)) {
            $orderTotals[] = array(
            'order_id' => $transaction->orderid,
            'code' => 'bambora_online_checkout_fee',
            'title' => $this->language->get('bambora_online_checkout_fee') . ' ' . $transaction->information->paymenttypes[0]->displayname,
            'value' => (float)$transactionFee,
            'sort_order' => 8
            );

            if (count($orderTotals) > 1) {
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
        } else {
            $message = "Could not add surchage to the order";
            $this->model_checkout_order->addOrderHistory($transaction->orderid, $currentStatusId, $message);
        }

    }
    protected function getConfigBaseName()
    {
        if($this->is_oc_3()) {
            return "payment_{$this->module_name}";
        } else {
            return $this->module_name;
        }
    }

    protected function is_oc_3()
    {
        return !version_compare(VERSION, '3', '<');
    }
}
