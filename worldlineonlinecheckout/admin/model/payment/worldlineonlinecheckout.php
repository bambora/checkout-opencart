<?php
namespace Opencart\Admin\Model\Extension\WorldlineOnlineCheckout\Payment;
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
	private const WORLDLINE_TRANSACTION_API_ENDPOINT = 'https://transaction-v1.api-eu.bambora.com';
	/**
	 * @var string
	 */
	private const WORLDLINE_MERCHANT_API_ENDPOINT = 'https://merchant-v1.api-eu.bambora.com';
	/**
	 * @var string
	 */
	private const WORLDLINE_DATA_API_ENDPOINT = 'https://data-v1.api-eu.bambora.com';

	/**
	 * Install and create Worldline Online Checkout Transaction table
	 * Legacy naming "bambora_online_checkout_transaction
	 */
	public function install(): void {
		$this->db->query(
			" CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "bambora_online_checkout_transaction` (
                `order_id` int(11) NOT NULL,
                `transaction_id` VARCHAR(45) NOT NULL,
                `amount` VARCHAR(45) NOT NULL,
                `currency` VARCHAR(4) NOT NULL,
                `module_version` VARCHAR(10) NOT NULL,
                `created` DATETIME NOT NULL,
                PRIMARY KEY (`order_id`)
                ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;"
		);
	}

	/**
	 * Get transaction from database
	 *
	 * @param mixed $orderId
	 *
	 * @return mixed
	 */
	public function getDbTransaction($orderId): mixed {
		$result = $this->db->query(
			"SELECT * FROM " . DB_PREFIX . "bambora_online_checkout_transaction WHERE order_id = " . (int)$this->db->escape(
				$orderId
			)
		);

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
	 * @param int    $amountInMinorunits
	 * @param string $currencyCode
	 *
	 * @return mixed
	 */
	public function capture($transactionId, $amountInMinorunits, $currencyCode): mixed {
		$endpoint = self::WORLDLINE_TRANSACTION_API_ENDPOINT . "/transactions/{$transactionId}/capture";
		$request = [];
		$request['amount'] = $amountInMinorunits;
		$request['currency'] = $currencyCode;

		return $this->sendApiRequest($request, $endpoint, 'POST');
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
			'X-EPay-System: ' . self::MODULE_VERSION,
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

	/**
	 * Returns the module header information
	 *
	 * @return string
	 */
	public function getModuleHeaderInformation(): string {
		return 'OpenCart/' . VERSION . ' Module/' . self::MODULE_VERSION . ' PHP/' . PHP_VERSION;
	}

	/**
	 * Refund a payment
	 *
	 * @param string $transactionId
	 * @param int    $amountInMinorunits
	 * @param string $currencyCode
	 *
	 * @return mixed
	 */
	public function refund($transactionId, $amountInMinorunits, $currencyCode): mixed {
		$endpoint = self::WORLDLINE_TRANSACTION_API_ENDPOINT . "/transactions/{$transactionId}/credit";
		$request = [];
		$request['amount'] = $amountInMinorunits;
		$request['currency'] = $currencyCode;

		return $this->sendApiRequest($request, $endpoint, 'POST');
	}

	/**
	 * Void a payment
	 *
	 * @param string $transactionId
	 *
	 * @return mixed
	 */
	public function void($transactionId): mixed {
		$endpoint = self::WORLDLINE_TRANSACTION_API_ENDPOINT . "/transactions/{$transactionId}/delete";
		$request = [];

		return $this->sendApiRequest($request, $endpoint, 'POST');
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
	 * Get a transaction operations
	 *
	 * @param string $transactionId
	 *
	 * @return mixed
	 */
	public function getTransactionOperations($transactionId): mixed {
		$endpoint = self::WORLDLINE_MERCHANT_API_ENDPOINT . "/transactions/{$transactionId}/transactionoperations";
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
	public function convertPriceToMinorunits($amount, $minorunits): float|int {
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
	 * @return string
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
			($amount / 10 ** $minorunits),
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

	/**
	 * Worldline Online Checkout Logging
	 *
	 * @param mixed $logContent
	 *
	 * @return void
	 */
	public function worldlineOnlineCheckoutLog($logContent): void {
		$log = new \Opencart\System\Library\Log(self::CODE . '.log');
		$logMessage = "\r\n Shop information: " . $this->getModuleHeaderInformation(
		) . "\r\n Area: Admin" . "\r\n Message: " . $logContent;
		$log->write($logMessage);
	}

	/**
	 * Get Distinct Exemptions
	 *
	 * @param mixed $exemptions
	 *
	 * @return string|null
	 */
	public function getDistinctExemptions($exemptions): ?string {
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

	/**
	 * Get the Lowest ECI
	 *
	 * @param array $ecis
	 *
	 * @return mixed
	 */
	public function getLowestECI($ecis): mixed {
		$eciValues = [];
		foreach ($ecis as $eci) {
			$eciValues[] = $eci->value;
		}

		return min($eciValues);
	}

	/**
	 * Get Event Extra
	 *
	 * @param mixed $operation
	 *
	 * @return string
	 */
	public function getEventExtra($operation): string {
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
	 * Get response code data
	 *
	 * @param string $source
	 * @param string $actionCode
	 *
	 * @return mixed
	 */
	public function getResponseCodeData($source, $actionCode): mixed {
		$endpoint = self::WORLDLINE_DATA_API_ENDPOINT . "/responsecodes/{$source}/{$actionCode}";
		$request = [];

		return $this->sendApiRequest($request, $endpoint, 'GET');
	}

	/**
	 *  Get the 3D Secure info.
	 *
	 * @param int $eciLevel
	 *
	 * @return string
	 */
	public function get3DSecureText($eciLevel): string {
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
	public function getEventText($operation): array {
		$action = strtolower($operation->action);
		$subAction = strtolower($operation->subaction);
		$approved = $operation->status == 'approved';
		$threeDSecureBrandName = "";
		$eventInfo = [];

		if ($action === "authorize") {
			if (isset($operation->paymenttype->id)) {
				$threeDSecureBrandName = $this->getCardAuthenticationBrandName(
					$operation->paymenttype->id
				);
			}
			// Renaming for Collector Bank to Walley
			$thirdPartyName = $operation->acquirername;
			$thirdPartyName = strtolower(
				$thirdPartyName
			) === "collectorbank" ? "Walley" : $thirdPartyName;

			switch ($subAction) {
				case "threed":
					$title = $approved ? 'Payment completed (' . $threeDSecureBrandName . ')' : 'Payment failed (' . $threeDSecureBrandName . ')';
					$eci = $operation->eci->value;
					$statusText = $approved ? "completed successfully" : "failed";
					$description = "";
					if ($eci === "7") {
						$description = 'Authentication was either not attempted or unsuccessful. Either the card does not support' . $threeDSecureBrandName . ' or the issuing bank does not handle it as a ' . $threeDSecureBrandName . ' payment. Payment ' . $statusText . ' at ECI level ' . $eci;
					}
					if ($eci === "6") {
						$description = 'Authentication was attempted but failed. Either cardholder or card issuing bank is not enrolled for ' . $threeDSecureBrandName . '. Payment ' . $statusText . ' at ECI level ' . $eci;
					}
					if ($eci === "5") {
						$description = $approved ? 'Payment was authenticated at ECI level ' . $eci . ' via ' . $threeDSecureBrandName . ' and ' . $statusText : 'Payment was did not authenticate via ' . $threeDSecureBrandName . ' and ' . $statusText;
					}
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "ssl":
					$title = $approved ? 'Payment completed' : 'Payment failed';
					$description = $approved ? 'Payment was completed and authorized via SSL.' : 'Authorization was attempted via SSL, but failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "recurring":
					$title = $approved ? 'Subscription payment completed' : 'Subscription payment failed';
					$description = $approved ? 'Payment was completed and authorized on a subscription.' : 'Authorization was attempted on a subscription, but failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "update":
					$title = $approved ? 'Payment updated' : 'Payment update failed';
					$description = $approved ? 'The payment was successfully updated.' : 'The payment update failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "return":
					$title = $approved ? 'Payment completed' : 'Payment failed';
					$statusText = $approved ? 'successful' : 'failed';
					$description = 'Returned from ' . $thirdPartyName . ' authentication with a ' . $statusText . ' authorization.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "redirect":
					$statusText = $approved ? "Successfully" : "Unsuccessfully";
					$eventInfo['title'] = 'Redirect to ' . $thirdPartyName;
					$eventInfo['description'] = $statusText . ' redirected to ' . $thirdPartyName . ' for authentication.';

					return $eventInfo;

			}
		}
		if ($action === "capture") {
			$captureMultiText = (($subAction === "multi" || $subAction === "multiinstant") && $operation->currentbalance > 0) ? 'Further captures are possible.' : 'Further captures are no longer possible.';

			switch ($subAction) {
				case "full":
					$title = $approved ? 'Captured full amount' : 'Capture failed';
					$description = $approved ? 'The full amount was successfully captured.' : 'The capture attempt failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "fullinstant":
					$title = $approved ? 'Instantly captured full amount' : 'Instant capture failed';
					$description = $approved ? 'The full amount was successfully captured.' : 'The instant capture attempt failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "partly":
				case "multi":
					$title = $approved ? 'Captured partial amount' : 'Capture failed';
					$description = $approved ? 'The partial amount was successfully captured. ' . $captureMultiText : 'The partial capture attempt failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "partlyinstant":
				case "multiinstant":
					$title = $approved ? 'Instantly captured partial amount' : 'Instant capture failed';
					$description = $approved ? 'The partial amount was successfully captured. ' . $captureMultiText : 'The instant partial capture attempt failed.';

					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
			}
		}

		if ($action === "credit") {
			switch ($subAction) {
				case "full":
					$title = $approved ? 'Refunded full amount' : 'Refund failed';
					$description = $approved ? 'The full amount was successfully refunded.' : 'The refund attempt failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "partly":
				case "multi":
					$title = $approved ? 'Refunded partial amount' : 'Refund failed';

					$refundMultiText = $subAction === "multi" ? "Further refunds are possible." : "Further refunds are no longer possible.";

					$description = $approved ? 'The amount was successfully refunded. ' . $refundMultiText : 'The partial refund attempt failed.';

					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;
			}
		}
		if ($action === "delete") {
			switch ($subAction) {
				case "instant":
					$title = $approved ? 'Canceled' : 'Cancellation failed';
					$description = $approved ? 'The payment was canceled.' : 'The cancellation failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

				case "delay":
					$title = $approved ? 'Cancellation scheduled' : 'Cancellation scheduling failed';
					$description = $approved ? 'The payment was canceled.' : 'The cancellation failed.';
					$eventInfo['title'] = $title;
					$eventInfo['description'] = $description;

					return $eventInfo;

			}
		}
		$eventInfo['title'] = $action . ":" . $subAction;
		$eventInfo['description'] = null;

		return $eventInfo;
	}

	/**
	 *  Get the Card Authentication Brand Name
	 *
	 * @param int $paymentGroupId
	 *
	 * @return string
	 */
	public function getCardAuthenticationBrandName($paymentGroupId): string {
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

	public function getRoundingModes(): array {
		return [
			"default" => $this->language->get('text_rounding_mode_default'),
			"up"      => $this->language->get('text_rounding_mode_always_up'),
			"down"    => $this->language->get('text_rounding_mode_always_down')
		];
	}

	public function getPaymentWindowStates(): array {
		return [
			"1" => $this->language->get('text_window_state_fullscreen'),
			"2" => $this->language->get('text_window_state_overlay')
		];
	}
}
