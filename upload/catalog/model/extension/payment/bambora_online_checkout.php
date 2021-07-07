<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 *
 */
class ModelExtensionPaymentBamboraOnlineCheckout extends Model
{

    const CHECKOUT_API_ENDPOINT = 'https://api.v1.checkout.bambora.com/sessions';
    const ZERO_API_MERCHANT_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';
    const ZERO_API_DATA_ENDPOINT = 'https://data-v1.api-eu.bambora.com';
    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

    /**
     * @var string
     */
    private $module_version = '1.4.1';

    /**
     * Returns method data
     *
     * @param mixed $address
     * @param mixed $total
     * @return array
     */
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/'.$this->module_name);

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get($this->getConfigBaseName() . '_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get($this->getConfigBaseName() . '_total') > 0 && $this->config->get($this->getConfigBaseName() . '_total') > $total) {
            $status = false;
        } elseif (!$this->config->get($this->getConfigBaseName() . '_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => $this->module_name,
                'title'      => $this->config->get($this->getConfigBaseName() .'_payment_method_title'),
                'terms'      => '',
                'sort_order' => $this->config->get($this->getConfigBaseName() . '_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * Add transaction to database
     *
     * @param mixed $orderId
     * @param mixed $transactionId
     * @param mixed $amount
     * @param mixed $currency
     * @return mixed
     */
    public function addDbTransaction($orderId, $transactionId, $amount, $currency)
    {
        try {
            $this->db->query("INSERT INTO " . DB_PREFIX . "bambora_online_checkout_transaction SET
            order_id = " . (int)$this->db->escape($orderId) . ",
            transaction_id = '" . $this->db->escape($transactionId) . "',
            amount = '" . $this->db->escape($amount) . "',
            currency = '" . $this->db->escape($currency) . "',
            module_version = '" . $this->db->escape($this->module_version) . "',
            created = NOW()
         ");
        }
        catch(Exception $ex) {
            throw new Exception("Could not add the transaction to the database. Possible dublicate entry txnid: {$transactionId} and orderId: {$orderId}");
        }

    }

    /**
     * Get transaction from database
     *
     * @param mixed $orderId
     * @return mixed
     */
    public function getDbTransaction($orderId)
    {
        $result = $this->db->query("SELECT * FROM " . DB_PREFIX . "bambora_online_checkout_transaction WHERE order_id = " . (int)$this->db->escape($orderId));

        if ($result->num_rows) {
            return $result->row;
        } else {
            return false;
        }
    }

    /**
     * Create a checkout session
     *
     * @param mixed $checkoutRequest
     * @return mixed
     */
    public function setCheckoutSession($checkoutRequest)
    {
        $endpoint = $this::CHECKOUT_API_ENDPOINT;
        $response = $this->sendApiRequest($checkoutRequest, $endpoint, 'POST');

        return $response;
    }

    /**
     * Get allowed paymenttype ids
     *
     * @param mixed $currency
     * @param mixed $amountInMinorunits
     * @return mixed
     */
    public function getPaymentTypeIds($currency, $amountInMinorunits)
    {
        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . "/paymenttypes?currency={$currency}&amount={$amountInMinorunits}";
        $request = array();
        $response = $this->sendApiRequest($request, $endpoint, 'GET');

        return $response;
    }


    /**
     * Get a transaction
     *
     * @param string $transactionId
     * @return mixed
     */
    public function getTransaction($transactionId)
    {
        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . "/transactions/{$transactionId}";
        $request = array();
        $response = $this->sendApiRequest($request, $endpoint, 'GET');

        return $response;
    }

    /**
     * Send the API request to Bambora
     *
     * @param mixed $request
     * @param mixed $endpoint
     * @param mixed $type
     * @return mixed
     */
    protected function sendApiRequest($request, $endpoint, $type)
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

    /**
     * Get the Bambora API key based on access token, merchant number and secret token
     *
     * @return string
     */
    protected function getApiKey()
    {
        $accesstoken = $this->config->get($this->getConfigBaseName() . '_access_token');
        $merchantNumber = $this->config->get($this->getConfigBaseName() . '_merchant');
        $secretToken = $this->config->get($this->getConfigBaseName() . '_secret_token');

        $combined = $accesstoken . '@' . $merchantNumber . ':' . $secretToken;
        $encoded_key = base64_encode($combined);
        $api_key = 'Basic ' . $encoded_key;
        return $api_key;
    }

    /**
     * Returns the module header information
     *
     * @return string
     */
    public function getModuleHeaderInformation()
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
    public function convertPriceToMinorunits($amount, $minorunits)
    {
        if ($amount == "" || $amount == null) {
            return 0;
        }
        $roundingMode = $this->config->get($this->getConfigBaseName() . '_rounding_mode');
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
    public function convertPriceFromMinorunits($amount, $minorunits, $decimalPoint = '.', $thousandSeparator = '')
    {
        if (!isset($amount)) {
            return 0;
        }
        return number_format(($amount / pow(10, $minorunits)), $minorunits, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get Currency Minorunits
     *
     * @param mixed $currencyCode
     * @return integer
     */
    public function getCurrencyMinorunits($currencyCode)
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

    public function bamboraLog($logContent)
    {
        $log = new Log('bambora_online_checkout.log');
        $logMessage = "\r\n Shop information: " . $this->getModuleHeaderInformation() . "\r\n Area: Catalog" . "\r\n Message: " . $logContent;
        $log->write($logMessage);
    }

    public function getConfigBaseName()
    {
        if($this->is_oc_3()) {
            return "payment_{$this->module_name}";
        } else {
            return $this->module_name;
        }
    }

    public function is_oc_3()
    {
        return !version_compare(VERSION, '3', '<');
    }
}
