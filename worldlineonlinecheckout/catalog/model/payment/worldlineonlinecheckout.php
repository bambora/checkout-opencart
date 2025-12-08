<?php

namespace Opencart\Catalog\Model\Extension\WorldlineOnlineCheckout\Payment;
class WorldlineOnlineCheckout extends \Opencart\System\Engine\Model {
	/**
	 * @var string
	 */
	private const CODE = 'worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const MODULE_VERSION = '2.0.0';
	/**
	 * @var string
	 */
	private const WORLDLINE = 'extension/worldlineonlinecheckout/payment/worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const WORLDLINE_CHECKOUT_API_ENDPOINT = 'https://api.v1.checkout.bambora.com';
	/**
	 * @var string
	 */
	private const WORLDLINE_MERCHANT_API_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';

	private const WORLDLINE_DB_TABLE = 'bambora_online_checkout_transaction';

	/**
	 * Returns method data
	 *
	 * @param array $address
	 *
	 * @return array
	 */
	public function getMethods(array $address = []): array {
		$this->load->language(self::WORLDLINE);

		if (!$this->cart->hasShipping()) {
			$status = false;
		} elseif (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_' . self::CODE . '_geo_zone_id')) {
			$status = true;
		} else {
			$geoZoneId = (int)$this->config->get('payment_' . self::CODE . '_geo_zone_id');
			$countryId = (int)$address['country_id'];
			$zoneId = (int)$address['zone_id'];

			$query = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
                WHERE `geo_zone_id` = '{$geoZoneId}'
                AND `country_id` = '{$countryId}'
                AND (`zone_id` = '{$zoneId}' OR `zone_id` = '0')"
			);

			$status = $query->num_rows > 0;
		}

		$methodData = [];

		if ($status) {
			$title = $this->config->get('payment_' . self::CODE . '_payment_method_title') ?: 'Pay with Worldline Online Checkout';

			$optionData[self::CODE] = [
				'code' => self::CODE . '.' . self::CODE,
				'name' => $title,
			];

			$methodData = [
				'code'       => self::CODE,
				'name'       => $title,
				'option'     => $optionData,
				'sort_order' => $this->config->get('payment_' . self::CODE . '_sort_order')
			];
		}

		return $methodData;
	}

	/**
	 * Add transaction to database
	 *
	 * @param mixed $orderId
	 * @param mixed $transactionId
	 * @param mixed $amount
	 * @param mixed $currency
	 *
	 * @return mixed
	 */
	public function addDbTransaction($orderId, $transactionId, $amount, $currency): void {
		try {
			$dbTable = DB_PREFIX . self::WORLDLINE_DB_TABLE;
			$this->db->query(
				"INSERT INTO  {$dbTable} SET order_id = " . (int)$this->db->escape($orderId) . ",
            transaction_id = '" . $this->db->escape($transactionId) . "',
            amount = '" . $this->db->escape($amount) . "',
            currency = '" . $this->db->escape($currency) . "',
            module_version = '" . $this->db->escape(self::MODULE_VERSION) . "',
            created = NOW()"
			);
		} catch (\Exception $ex) {
			throw new \Exception("Could not add the transaction to the database. Possible dublicate entry txnid: {$transactionId} and orderId: {$orderId}");
		}
	}

	/**
	 * Get transaction from database
	 *
	 * @param mixed $orderId
	 *
	 * @return mixed
	 */
	public function getDbTransaction($orderId): mixed {
		$dbTable = DB_PREFIX . self::WORLDLINE_DB_TABLE;
		$result = $this->db->query(
			"SELECT * FROM {$dbTable} WHERE order_id = " . (int)$this->db->escape(
				$orderId
			)
		);

		if ($result->num_rows) {
			return $result->row;
		} else {
			return false;
		}
	}

	public function updateDbPaymentMethod($orderId, $paymentMethodName): void {
		$paymentMethod = [
			'code' => self::CODE . '.' . self::CODE,
			'name' => $paymentMethodName
		];
		$paymentMethodJson = json_encode($paymentMethod);

		$this->db->query(
			"UPDATE `" . DB_PREFIX . "order` SET payment_method = '" . $this->db->escape(
				$paymentMethodJson
			) . "' WHERE order_id = '" . $this->db->escape($orderId) . "';"
		);
	}

	/**
	 * Create a checkout session
	 *
	 * @param mixed $checkoutRequest
	 *
	 * @return mixed
	 */
	public function setCheckoutSession($checkoutRequest): mixed {
		$endpoint = self::WORLDLINE_CHECKOUT_API_ENDPOINT . '/sessions';

		return $this->sendApiRequest($checkoutRequest, $endpoint, 'POST');
	}

	/**
	 * Returns the module header information
	 *
	 * @return string
	 */
	public function getModuleHeaderInformation(): string {
		return 'OpenCart/' . VERSION . ' Module/' . self::MODULE_VERSION . ' PHP/' . PHP_VERSION;
	}

	/**
	 * Get allowed paymenttype ids
	 *
	 * @param mixed $currency
	 * @param mixed $amountInMinorunits
	 *
	 * @return mixed
	 */
	public function getPaymentTypeIds($currency, $amountInMinorunits): mixed {
		$endpoint = self::WORLDLINE_MERCHANT_API_ENDPOINT . "/paymenttypes?currency={$currency}&amount={$amountInMinorunits}";
		$request = [];

		return $this->sendApiRequest($request, $endpoint, 'GET');
	}

	/**
	 * Get a transaction
	 *
	 * @param string $transactionId
	 *
	 * @return mixed
	 */
	public function getTransaction($transactionId): mixed {
		$endpoint = self::WORLDLINE_MERCHANT_API_ENDPOINT . "/transactions/{$transactionId}";
		$request = [];

		return $this->sendApiRequest($request, $endpoint, 'GET');
	}

	/**
	 * Convert Price To MinorUnits
	 *
	 * @param mixed $amount
	 * @param mixed $minorunits
	 *
	 * @return float|int
	 */
	public function convertPriceToMinorunits($amount, $minorunits) {
		if ($amount == "" || $amount == null) {
			return 0;
		}
		$roundingMode = $this->config->get(
			'payment_' . self::CODE . '_rounding_mode'
		);
		switch ($roundingMode) {
			case 'up':
				$amount = ceil($amount * 10 ** $minorunits);
				break;
			case 'down':
				$amount = floor($amount * 10 ** $minorunits);
				break;
			default:
				$amount = round($amount * 10 ** $minorunits);
				break;
		}

		return $amount;
	}

	/**
	 * Get selected rounding mode for PHP
	 *
	 * @return int
	 */
	public function getRoundingMode(): int {
		$rounding = $this->config->get(
			'payment_' . self::CODE . '_rounding_mode'
		);

		return match ($rounding) {
			'up'    => PHP_ROUND_HALF_UP,
			'down'  => PHP_ROUND_HALF_DOWN,
			default => PHP_ROUND_HALF_EVEN,
		};
	}

	/**
	 * Convert Price From MinorUnits
	 *
	 * @param mixed $amount
	 * @param mixed $minorunits
	 * @param mixed $decimalPoint
	 * @param mixed $thousandSeparator
	 *
	 * @return int|string
	 */
	public function convertPriceFromMinorunits(
		$amount,
		$minorunits,
		$decimalPoint = '.',
		$thousandSeparator = ''
	): int|string {
		if (!isset($amount)) {
			return 0;
		}

		return number_format(
			$amount / 10 ** $minorunits,
			$minorunits,
			$decimalPoint,
			$thousandSeparator
		);
	}

	/**
	 * Get Currency Minorunits
	 *
	 * @param mixed $currencyCode
	 *
	 * @return int
	 */
	public function getCurrencyMinorunits($currencyCode): int {
		$currencyArray = [
			'TTD' => 0,
			'KMF' => 0,
			'ADP' => 0,
			'TPE' => 0,
			'BIF' => 0,
			'DJF' => 0,
			'MGF' => 0,
			'XPF' => 0,
			'GNF' => 0,
			'BYR' => 0,
			'PYG' => 0,
			'JPY' => 0,
			'CLP' => 0,
			'XAF' => 0,
			'TRL' => 0,
			'VUV' => 0,
			'CLF' => 0,
			'KRW' => 0,
			'XOF' => 0,
			'RWF' => 0,
			'IQD' => 3,
			'TND' => 3,
			'BHD' => 3,
			'JOD' => 3,
			'OMR' => 3,
			'KWD' => 3,
			'LYD' => 3
		];

		return array_key_exists(
			$currencyCode,
			$currencyArray
		) ? $currencyArray[$currencyCode] : 2;
	}

	public function worldlineOnlineCheckoutLog($logContent): void {
		$log = new \Opencart\System\Library\Log(self::CODE . '.log');
		$logMessage = "\r\n Shop information: " . $this->getModuleHeaderInformation(
		) . "\r\n Area: Catalog" . "\r\n Message: " . $logContent;
		$log->write($logMessage);
	}

	/**
	 * Send the API request to Worldline Online Checkout
	 *
	 * @param mixed $request
	 * @param mixed $endpoint
	 * @param mixed $type
	 *
	 * @return mixed
	 */
	private function sendApiRequest($request, $endpoint, $type): mixed {
		$requestJson = json_encode($request);
		$contentLength = strlen($requestJson);
		$headers = [
			'Content-Type: application/json',
			'Content-Length: ' . $contentLength,
			'Accept: application/json',
			'Authorization: ' . $this->getApiKey(),
			'X-EPay-System: ' . $this->getModuleHeaderInformation(),
		];
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
	 * Get the Worldline Online Checkout API key based on access token, merchant number and secret
	 * token
	 *
	 * @return string
	 */
	private function getApiKey(): string {
		$accesstoken = $this->config->get(
			'payment_' . self::CODE . '_access_token'
		);
		$merchantNumber = $this->config->get(
			'payment_' . self::CODE . '_merchant'
		);
		$secretToken = $this->config->get(
			'payment_' . self::CODE . '_secret_token'
		);

		$combined = $accesstoken . '@' . $merchantNumber . ':' . $secretToken;
		$encoded_key = base64_encode($combined);

		return 'Basic ' . $encoded_key;
	}
}
