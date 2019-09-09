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
    const ZERO_API_TRANSACTION_ENDPOINT = 'https://transaction-v1.api-eu.bambora.com';
    const ZERO_API_MERCHANT_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';

    /**
     * @var string
     */
    private $module_version = '1.2.5';

    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

    /**
     * Install and create Bambora Online Checkout Transaction table
     */
    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "bambora_online_checkout_transaction` (
                `order_id` int(11) NOT NULL,
                `transaction_id` VARCHAR(45) NOT NULL,
                `amount` VARCHAR(45) NOT NULL,
                `currency` VARCHAR(4) NOT NULL,
                `module_version` VARCHAR(10) NOT NULL,
                `created` DATETIME NOT NULL,
                PRIMARY KEY (`order_id`)
                ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
         ");
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
     * Capture a payment
     *
     * @param string $transactionId
     * @param int|long $amountInMinorunits
     * @param string $currencyCode
     * @return mixed
     */
    public function capture($transactionId, $amountInMinorunits, $currencyCode)
    {
        $endpoint = $this::ZERO_API_TRANSACTION_ENDPOINT . "/transactions/{$transactionId}/capture";
        $request = array();
        $request['amount'] = $amountInMinorunits;
        $request['currency'] = $currencyCode;
        $response = $this->sendApiRequest($request, $endpoint, 'POST');

        return $response;
    }

    /**
     * Refund a payment
     *
     * @param string $transactionId
     * @param int|long $amountInMinorunits
     * @param string $currencyCode
     * @return mixed
     */
    public function refund($transactionId, $amountInMinorunits, $currencyCode)
    {
        $endpoint = $this::ZERO_API_TRANSACTION_ENDPOINT . "/transactions/{$transactionId}/credit";
        $request = array();
        $request['amount'] = $amountInMinorunits;
        $request['currency'] = $currencyCode;
        $response = $this->sendApiRequest($request, $endpoint, 'POST');

        return $response;
    }

    /**
     * Void a payment
     *
     * @param string $transactionId
     * @return mixed
     */
    public function void($transactionId)
    {
        $endpoint = $this::ZERO_API_TRANSACTION_ENDPOINT . "/transactions/{$transactionId}/delete";
        $request = array();
        $response = $this->sendApiRequest($request, $endpoint, 'POST');

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
     * Get a transaction operations
     *
     * @param string $transactionId
     * @return mixed
     */
    public function getTransactionOperations($transactionId)
    {
        $endpoint = $this::ZERO_API_MERCHANT_ENDPOINT . "/transactions/{$transactionId}/transactionoperations";
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
        $logMessage = "\r\n Shop information: " . $this->getModuleHeaderInformation() . "\r\n Area: Admin" . "\r\n Message: " . $logContent;
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
