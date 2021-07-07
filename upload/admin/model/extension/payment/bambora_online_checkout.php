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
    const ZERO_API_DATA_ENDPOINT = 'https://data-v1.api-eu.bambora.com';
    /**
     * @var string
     */
    private $module_version = '1.4.1';

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
     * Get response code data
     *
     * @param string $source
     * @param string $actionCode
     * @return mixed
     */
    public function getResponseCodeData($source, $actionCode)
    {
        $endpoint = $this::ZERO_API_DATA_ENDPOINT . "/responsecodes/{$source}/{$actionCode}";
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
        $headerInformation = 'OpenCart/' . VERSION . ' Module/' . $this->module_version . ' PHP/' . phpversion();

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
        if ($this->is_oc_3()) {
            return "payment_{$this->module_name}";
        } else {
            return $this->module_name;
        }
    }

    public function is_oc_3()
    {
        return !version_compare(VERSION, '3', '<');
    }

    public function getDistinctExemptions($exemptions)
    {
        if (isset($exemptions)) {
            $exemptionValues = null;
            foreach ($exemptions as $exemption) {
                $exemptionValues[] = $exemption->value;
            }
            return implode(",", array_unique($exemptionValues));
        } else {
            return null;
        }
    }

    public function getLowestECI($ecis)
    {
        foreach ($ecis as $eci) {
            $eciValues[] = $eci->value;
        }
        return min($eciValues);
    }

    public function getEventExtra($operation)
    {
        $source = $operation->actionsource;
        $actionCode = $operation->actioncode;
        $responseCode = $this->getResponseCodeData($source, $actionCode);
        $merchantLabel = "";
        if (isset($responseCode->responsecode)) {
            $merchantLabel = $responseCode->responsecode->merchantlabel . " - " . $source . " " . $actionCode;
        }
        return $merchantLabel;
    }

    /**
     *  Get the Card Authentication Brand Name
     *
     * @param integer $paymentGroupId
     * @return string
     */
    public function getCardAuthenticationBrandName($paymentGroupId)
    {
        switch ($paymentGroupId) {
            case 1:
                return "Dankort Secured by Nets";
            case 2:
                return "Verified by Visa";
            case 3:
            case 4:
                return "MasterCard SecureCode";
            case 5:
                return "J/Secure";
            case 6:
                return "American Express SafeKey";
            default:
                return "3D Secure";
        }
    }

    /**
     *  Get the 3D Secure info.
     *
     * @param integer $eciLevel
     *
     * @return string
     */
    public function get3DSecureText($eciLevel)
    {
        switch ($eciLevel) {
            case "7":
            case "00":
            case "0":
            case "07":
                return "Authentication is unsuccessful or not attempted. The credit card is either a non-3D card or card issuing bank does not handle it as a 3D transaction.";
            case "06":
            case "6":
            case "01":
            case "1":
                return "Either cardholder or card issuing bank is not 3D enrolled. 3D card authentication is unsuccessful, in sample situations as: 1. 3D Cardholder not enrolled, 2. Card issuing bank is not 3D Secure ready.";
            case "05":
            case "5":
            case "02":
            case "2":
                return "Both cardholder and card issuing bank are 3D enabled. 3D card authentication is successful.";
            default:
                return "";
        }
    }

    /**
     *  Get event Log text.
     *
     * @param object $operation
     *
     * @return array
     */
    public function getEventText($operation)
    {
        $action = strtolower($operation->action);
        $subAction = strtolower($operation->subaction);
        $approved = $operation->status == 'approved';
        $threeDSecureBrandName = "";
        $eventInfo = array();

        if ($action === "authorize") {
            if (isset($operation->paymenttype->id)) {
                $threeDSecureBrandName = $this->getCardAuthenticationBrandName($operation->paymenttype->id);
            }
            // Temporary renaming for Lindorff to Collector Bank require until implemented in Acquire
            $thirdPartyName = $operation->acquirername;
            $thirdPartyName = strtolower($thirdPartyName) !== "lindorff"
                ? $thirdPartyName
                : "Collector Bank";

            switch ($subAction) {
                case "threed":
                {
                    $title = $approved ? 'Payment completed (' . $threeDSecureBrandName . ')' : 'Payment failed (' . $threeDSecureBrandName . ')';
                    $eci = $operation->eci->value;
                    $statusText = $approved
                        ? "completed successfully"
                        : "failed";
                    $description = "";
                    if ($eci === "7") {
                        $description = 'Authentication was either not attempted or unsuccessful. Either the card does not support' .
                            $threeDSecureBrandName . ' or the issuing bank does not handle it as a ' .
                            $threeDSecureBrandName . ' payment. Payment ' . $statusText . ' at ECI level ' . $eci;
                    }
                    if ($eci === "6") {
                        $description = 'Authentication was attempted but failed. Either cardholder or card issuing bank is not enrolled for ' .
                            $threeDSecureBrandName . '. Payment ' . $statusText . ' at ECI level ' . $eci;
                    }
                    if ($eci === "5") {
                        $description = $approved
                            ? 'Payment was authenticated at ECI level ' . $eci . ' via ' . $threeDSecureBrandName . ' and ' . $statusText
                            : 'Payment was did not authenticate via ' . $threeDSecureBrandName . ' and ' . $statusText;
                    }
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "ssl":
                {
                    $title = $approved
                        ? 'Payment completed'
                        : 'Payment failed';
                    $description = $approved
                        ? 'Payment was completed and authorized via SSL.'
                        : 'Authorization was attempted via SSL, but failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "recurring":
                {
                    $title = $approved
                        ? 'Subscription payment completed'
                        : 'Subscription payment failed';
                    $description = $approved
                        ? 'Payment was completed and authorized on a subscription.'
                        : 'Authorization was attempted on a subscription, but failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }

                case "update":
                {
                    $title = $approved
                        ? 'Payment updated'
                        : 'Payment update failed';
                    $description = $approved
                        ? 'The payment was successfully updated.'
                        : 'The payment update failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "return":
                {
                    $title = $approved
                        ? 'Payment completed'
                        : 'Payment failed';
                    $statusText = $approved
                        ? 'successful'
                        : 'failed';
                    $description = 'Returned from ' . $thirdPartyName . ' authentication with a ' . $statusText . ' authorization.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;

                }
                case "redirect":
                {
                    $statusText = $approved
                        ? "Successfully"
                        : "Unsuccessfully";
                    $eventInfo['title'] = 'Redirect to ' . $thirdPartyName;
                    $eventInfo['description'] = $statusText . ' redirected to ' . $thirdPartyName . ' for authentication.';

                    return $eventInfo;
                }
            }
        }
        if ($action === "capture") {
            $captureMultiText = (($subAction === "multi" || $subAction === "multiinstant") && $operation->currentbalance > 0)
                ? 'Further captures are possible.'
                : 'Further captures are no longer possible.';

            switch ($subAction) {
                case "full":
                {
                    $title = $approved
                        ? 'Captured full amount'
                        : 'Capture failed';
                    $description = $approved
                        ? 'The full amount was successfully captured.'
                        : 'The capture attempt failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "fullinstant":
                {
                    $title = $approved
                        ? 'Instantly captured full amount'
                        : 'Instant capture failed';
                    $description = $approved
                        ? 'The full amount was successfully captured.'
                        : 'The instant capture attempt failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "partly":
                case "multi":
                {
                    $title = $approved
                        ? 'Captured partial amount'
                        : 'Capture failed';
                    $description = $approved
                        ? 'The partial amount was successfully captured. ' . $captureMultiText
                        : 'The partial capture attempt failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
                case "partlyinstant":
                case "multiinstant":
                {
                    $title = $approved
                        ? 'Instantly captured partial amount'
                        : 'Instant capture failed';
                    $description = $approved
                        ? 'The partial amount was successfully captured. ' . $captureMultiText
                        : 'The instant partial capture attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
            }
        }

        if ($action === "credit") {
            switch ($subAction) {
                case "full":
                {
                    $title = $approved
                        ? 'Refunded full amount'
                        : 'Refund failed';
                    $description = $approved
                        ? 'The full amount was successfully refunded.'
                        : 'The refund attempt failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
                case "partly":
                case "multi":
                {
                    $title = $approved
                        ? 'Refunded partial amount'
                        : 'Refund failed';

                    $refundMultiText = $subAction === "multi"
                        ? "Further refunds are possible."
                        : "Further refunds are no longer possible.";

                    $description = $approved
                        ? 'The amount was successfully refunded. ' . $refundMultiText
                        : 'The partial refund attempt failed.';

                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
            }
        }
        if ($action === "delete") {
            switch ($subAction) {
                case "instant":
                {
                    $title = $approved
                        ? 'Canceled'
                        : 'Cancellation failed';
                    $description = $approved
                        ? 'The payment was canceled.'
                        : 'The cancellation failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;
                    return $eventInfo;
                }
                case "delay":
                {
                    $title = $approved
                        ? 'Cancellation scheduled'
                        : 'Cancellation scheduling failed';
                    $description = $approved
                        ? 'The payment was canceled.'
                        : 'The cancellation failed.';
                    $eventInfo['title'] = $title;
                    $eventInfo['description'] = $description;

                    return $eventInfo;
                }
            }
        }
        $eventInfo['title'] = $action . ":" . $subAction;
        $eventInfo['description'] = null;

        return $eventInfo;
    }
}
