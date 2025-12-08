<?php
namespace Opencart\Admin\Controller\Extension\WorldlineOnlineCheckout\Payment;

class WorldlineOnlineCheckout extends \Opencart\System\Engine\Controller {
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
	private const WORLDLINE_ORDER = 'extension/worldlineonlinecheckout/payment/worldlineonlinecheckout_order';
	/**
	 * @var string
	 */
	private const WORLDLINE_ORDER_AJAX = 'extension/worldlineonlinecheckout/payment/worldlineonlinecheckout_order_ajax';
	/**
	 * @var string
	 */
	private const WORLDLINE_AJAX_ENDPOINT = 'index.php?route=extension/worldlineonlinecheckout/payment/worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const WORLDLINE_MERCHANT_ENDPOINT = 'https://merchant.bambora.com';
	/**
	 * @var string
	 */
	private const WORLDLINE_STATIC_ENDPOINT = 'https://static.bambora.com';

	/**
	 * Inits the module admin section
	 */
	public function index(): void {
		$this->load->language(self::WORLDLINE);
		$this->load->model('setting/setting');
		$this->load->model(self::WORLDLINE);
		$this->document->setTitle($this->language->get('heading_title'));

		$viewData = $this->initializeData();
		$viewData = array_merge($viewData, $this->prepareSettingBreadcrumbs());
		$viewData = array_merge($viewData, $this->prepareSettingValues());

		$this->response->setOutput(
			$this->load->view(
				self::WORLDLINE,
				$viewData
			)
		);
	}

	public function save(): void {
		$this->load->language(self::WORLDLINE);
		$json = $this->validateSettings();

		if (!$json) {
			// Setting
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_' . self::CODE, $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install the Module
	 *
	 * @return void
	 */
	public function install(): void {
		$this->load->model(self::WORLDLINE);
		$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->install();
	}

	/**
	 * Adds Transaction information to the order page
	 *
	 * @return mixed
	 */
	public function order(): mixed {
		$this->load->language(self::WORLDLINE);
		$data['user_token'] = $this->session->data['user_token'];
		$data['order_id'] = $this->request->get['order_id'];
		$data['worldline_ajax_endpoint'] = self::WORLDLINE_AJAX_ENDPOINT;

		return $this->load->view(
			self::WORLDLINE_ORDER,
			$data
		);
	}

	/**
	 * Loads the content of the transaction information on the order page
	 *
	 * @return void
	 */
	public function getPaymentTransaction(): void {
		$this->load->language(self::WORLDLINE);
		$data         = [];
		$moduleStatus = $this->config->get(
			'payment_' . self::CODE . '_status'
		);
		if ($moduleStatus && isset($this->request->get['order_id'])) {
			$this->load->model(self::WORLDLINE);
			$this->load->model('sale/order');

			$data['worldline_merchant_endpoint'] = self::WORLDLINE_MERCHANT_ENDPOINT;
			$data['worldline_ajax_endpoint'] = self::WORLDLINE_AJAX_ENDPOINT;

			$orderId       = $this->request->get['order_id'];
			$dbTransaction = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getDbTransaction(
				$orderId
			);
			$data['user_token'] = $this->request->get['user_token'];

			if ($dbTransaction && !empty($dbTransaction['transaction_id'])) {
				$transactionResponse = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getTransaction(
					$dbTransaction['transaction_id']
				);

				if ($transactionResponse && $transactionResponse->meta->result) {
					$transaction = $transactionResponse->transaction;
					$data['transaction']       = [];
					$data['transaction']['id'] = $transaction->id;

					$paymentTypesGroupId = $transaction->information->paymenttypes[0]->groupid;
					$paymentTypesId      = $transaction->information->paymenttypes[0]->id;
					if ($paymentTypesGroupId == 19 && $paymentTypesId == 40) { //Collector Bank
						$isCollector = true;
					} else {
						$isCollector = false;
					}

					$data['transaction']['isCollector']  = $isCollector;
					$decimalPoint      = $this->language->get(
						'currency_decimal_point'
					);
					$thousandSeparator = $this->language->get(
						'currency_thousand_separator'
					);

					$authorizedAmount = $this->convertPriceFromMinorunits(
						$transaction->total->authorized,
						$transaction->currency->minorunits,
						$decimalPoint,
						$thousandSeparator
					);
					$data['transaction']['authorized'] = "{$transaction->currency->code} {$authorizedAmount}";
					$data['transaction']['date'] = $transaction->createddate;

					if (is_array(
						$transaction->information->paymenttypes
					) && count(
						$transaction->information->paymenttypes
					) > 0) {
						$data['transaction']['paymentType'] = $transaction->information->paymenttypes[0]->displayname;
					} else {
						$data['transaction']['paymentType'] = "";
					}

					if (is_array(
						$transaction->information->primaryaccountnumbers
					) && count(
						$transaction->information->primaryaccountnumbers
					) > 0) {
						$data['transaction']['cardNumber'] = $transaction->information->primaryaccountnumbers[0]->number;
					} else {
						$data['transaction']['cardNumber'] = "";
					}
					if (is_array(
						$transaction->information->acquirerreferences
					) && count(
						$transaction->information->acquirerreferences
					) > 0) {
						$data['transaction']['acquirerReference'] = $transaction->information->acquirerreferences[0]->reference;
					} else {
						$data['transaction']['acquirerReference'] = "";
					}

					$surchargeFeeAmount                  = $this->convertPriceFromMinorunits(
						$transaction->total->feeamount,
						$transaction->currency->minorunits,
						$decimalPoint,
						$thousandSeparator
					);
					$data['transaction']['surchargeFee'] = "{$transaction->currency->code} {$surchargeFeeAmount}";

					$capturedAmount                  = $this->convertPriceFromMinorunits(
						$transaction->total->captured,
						$transaction->currency->minorunits,
						$decimalPoint,
						$thousandSeparator
					);
					$data['transaction']['captured'] = "{$transaction->currency->code} {$capturedAmount}";

					$redundedAmount                  = $this->convertPriceFromMinorunits(
						$transaction->total->credited,
						$transaction->currency->minorunits,
						$decimalPoint,
						$thousandSeparator
					);
					$data['transaction']['refunded'] = "{$transaction->currency->code} {$redundedAmount}";

					if (is_array($transaction->information->acquirers) && count(
						$transaction->information->acquirers
					) > 0) {
						$data['transaction']['acquirer'] = $transaction->information->acquirers[0]->name;
					} else {
						$data['transaction']['acquirer'] = "";
					}

					$data['transaction']['status']       = $this->checkoutStatus(
						$transaction->status
					);
					$data['transaction']['currencyCode'] = $transaction->currency->code;
					$data['transaction']['orderId']      = $orderId;

					if (isset($transaction->information->ecis)) {
						$data['transaction']['eci'] = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getLowestECI(
							$transaction->information->ecis
						);
					}
					if (isset($transaction->information->exemptions)) {
						$data['transaction']['exemptions'] = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getDistinctExemptions(
							$transaction->information->exemptions
						);
					}

					$availableForCapture                        = $this->convertPriceFromMinorunits(
						$transaction->available->capture,
						$transaction->currency->minorunits
					);
					$data['transaction']['availableForCapture'] = $availableForCapture;
					$availableForRefund                         = $this->convertPriceFromMinorunits(
						$transaction->available->credit,
						$transaction->currency->minorunits
					);
					$data['transaction']['availableForRefund']  = $availableForRefund;
					$data['transaction']['canVoid']             = $transaction->candelete;
					$data['showActions']                        = $availableForCapture > 0 || $availableForRefund > 0 || $transaction->candelete;
					$transactionOperationsResponse              = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getTransactionOperations(
						$dbTransaction['transaction_id']
					);
					$data['transaction']['operations']          = [];
					if ($transactionOperationsResponse && $transactionOperationsResponse->meta->result === true) {
						$transactionOperation = $transactionOperationsResponse->transactionoperations;
						if (count($transactionOperation) > 0) {
							$data['transaction']['operations'] = $this->createTransactionOperations(
								$transactionOperation,
								$decimalPoint,
								$thousandSeparator
							);
						}
					}
					$data['getPaymentTransaction_success'] = true;
					$data['transaction']['static_endpoint'] = self::WORLDLINE_STATIC_ENDPOINT;
					$data['transaction']['card_image']  = $transaction->information->paymenttypes[0]->groupid;
					$data['transaction']['card_name']      = $transaction->information->paymenttypes[0]->displayname;

					if (isset($transactionOperationsResponse->transactionoperations[0]->transactionoperations[0]->acquirerdata[0]->key)) {
						if ($transactionOperationsResponse->transactionoperations[0]->transactionoperations[0]->acquirerdata[0]->key == "nordeaepaymentfi.customerbank") {
							$data['transaction']['bank_image'] = $transactionOperationsResponse->transactionoperations[0]->transactionoperations[0]->acquirerdata[0]->value;
						}
					}

					if (isset($transaction->information->wallets[0]->name)) {
						$wallet_name = $transaction->information->wallets[0]->name;
						$wallet_img = '';
						if ($wallet_name == "MobilePay") {
							$wallet_img = "13";
						}
						if ($wallet_name == "Vipps") {
							$wallet_img = "14";
						}
						if ($wallet_name == "GooglePay") {
							$wallet_img  = "22";
							$wallet_name = "Google Pay";
						}
						if ($wallet_name == "ApplePay") {
							$wallet_img  = "21";
							$wallet_name = "Apple Pay";
						}
						if ($wallet_img) {
							$data['transaction']['wallet_image'] = $wallet_img;
							$data['transaction']['wallet_name'] = $wallet_name;
						}
					}

				} else {
					$data['getPaymentTransaction_success']    = false;
					$errorMessage                             = $transactionResponse ? $transactionResponse->meta->message->merchant : $this->language->get(
						'error_getTransaction_api_error'
					);
					$data['text_getPaymentTransaction_error'] = $errorMessage;
					$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
						$errorMessage
					);
				}
			} else {
				$data['getPaymentTransaction_success']    = false;
				$errorMessage                             = $this->language->get(
					'error_get_transaction_db'
				);
				$data['text_getPaymentTransaction_error'] = $errorMessage;
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
					$errorMessage
				);
			}
		} else {
			$data['getPaymentTransaction_success'] = false;
			if (!$moduleStatus) {
				$errorMessage = $this->language->get('error_module_not_loaded');
			} else {
				$errorMessage = $this->language->get(
					'error_order_id_not_supplied'
				);
			}
			$data['text_getPaymentTransaction_error'] = $errorMessage;
		}

		$this->response->setOutput(
			$this->load->view(
				self::WORLDLINE_ORDER_AJAX,
				$data
			)
		);
	}

	/**
	 * Capture a payment
	 *
	 * @return void
	 */
	public function capture(): void {
		$json = [];

		try {
			$this->load->model(self::WORLDLINE);
			$postParams       = $this->request->post;
			$transactionId    = $postParams['transactionId'];
			$currencyCode     = $postParams['currencyCode'];
			$minorunits       = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getCurrencyMinorunits(
				$currencyCode
			);
			$rawAmount        = $postParams['captureAmount'];
			$sanitizedAmount  = str_replace(',', '.', $rawAmount);
			$amount           = (float)$sanitizedAmount;
			$amountMinorunits = $this->convertPriceToMinorunits(
				$amount,
				$minorunits
			);
			$captureResponse  = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->capture(
				$transactionId,
				$amountMinorunits,
				$currencyCode
			);

			if (isset($captureResponse)) {
				$json['meta'] = $captureResponse->meta;
			} else {
				$json['meta']['result']              = false;
				$errorMessage                        = "Connection to Worldline Failed";
				$json['meta']['message']['merchant'] = $errorMessage;
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
					'Capture Failed: ' . $errorMessage
				);
			}
		} catch (\Exception $ex) {
			$json['meta']['result']              = false;
			$json['meta']['message']['merchant'] = $ex->getMessage();
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$ex->getMessage()
			);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Refund a payment
	 *
	 * @return void
	 */
	public function refund(): void {
		$json = [];

		try {
			$this->load->model(self::WORLDLINE);
			$postParams       = $this->request->post;
			$transactionId    = $postParams['transactionId'];
			$currencyCode     = $postParams['currencyCode'];
			$minorunits       = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getCurrencyMinorunits(
				$currencyCode
			);
			$rawAmount        = $postParams['refundAmount'];
			$sanitizedAmount  = str_replace(',', '.', $rawAmount);
			$amount           = (float)$sanitizedAmount;
			$amountMinorunits = $this->convertPriceToMinorunits(
				$amount,
				$minorunits
			);
			$refundResponse   = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->refund(
				$transactionId,
				$amountMinorunits,
				$currencyCode
			);

			if (isset($refundResponse)) {
				$json['meta'] = $refundResponse->meta;
			} else {
				$json['meta']['result']              = false;
				$errorMessage                        = "Connection to Worldline Failed";
				$json['meta']['message']['merchant'] = $errorMessage;
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
					'Refund Failed: ' . $errorMessage
				);
			}
		} catch (\Exception $ex) {
			$json['meta']['result']              = false;
			$json['meta']['message']['merchant'] = $ex->getMessage();
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$ex->getMessage()
			);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Void a payment
	 *
	 * @return void
	 */
	public function void(): void {
		$json = [];

		try {
			$this->load->model(self::WORLDLINE);
			$postParams    = $this->request->post;
			$transactionId = $postParams['transactionId'];
			$voidResponse  = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->void(
				$transactionId
			);

			if (isset($voidResponse)) {
				$json['meta'] = $voidResponse->meta;
			} else {
				$json['meta']['result']              = false;
				$errorMessage                        = "Connection to Worldline Failed";
				$json['meta']['message']['merchant'] = $errorMessage;
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
					'Void Failed: ' . $errorMessage
				);
			}
		} catch (\Exception $ex) {
			$json['meta']['result']              = false;
			$json['meta']['message']['merchant'] = $ex->getMessage();
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$ex->getMessage()
			);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Proxy method for Convert Price To Minorunits
	 *
	 * @param mixed $amount
	 * @param mixed $minorunits
	 *
	 * @return float|int
	 */
	private function convertPriceToMinorunits($amount, $minorunits): float|int {
		return $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->convertPriceToMinorunits($amount, $minorunits);
	}

	/**
	 * Proxy method for Convert Price From Minorunits
	 *
	 * @param mixed $amount
	 * @param mixed $minorunits
	 * @param mixed $decimalPoint
	 * @param mixed $thousandSeparator
	 *
	 * @return int|string
	 */
	private function convertPriceFromMinorunits($amount, $minorunits, $decimalPoint = '.', $thousandSeparator = ''): int|string {
		return $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->convertPriceFromMinorunits(
			$amount,
			$minorunits,
			$decimalPoint,
			$thousandSeparator
		);
	}

	/**
	 * Validate Module settings
	 *
	 * @return array
	 */
	private function validateSettings(): array {
		$json = [];
		if (!$this->user->hasPermission('modify', self::WORLDLINE)) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			// Merchant Empty Validation
			if (!$this->request->post['payment_' . self::CODE . '_merchant']) {
				$json['error']['merchant'] = $this->language->get('error_merchant');
			}
			// Access Token Empty Validation
			if (!$this->request->post['payment_' . self::CODE . '_access_token']) {
				$json['error']['access-token'] = $this->language->get('error_access_token');
			}
			// Secret Token Empty Validation
			if (!$this->request->post['payment_' . self::CODE . '_secret_token']) {
				$json['error']['secret-token'] = $this->language->get('error_secret_token');
			}
			// MD5 Empty Validation
			if (!$this->request->post['payment_' . self::CODE . '_md5']) {
				$json['error']['md5'] = $this->language->get('error_md5');
			}
			// Window ID Numeric Validation
			if ($this->request->post['payment_' . self::CODE . '_window_id']
				&& !is_numeric($this->request->post['payment_' . self::CODE . '_window_id'])) {
				$json['error']['window-id'] = $this->language->get('error_window_id');
			}
			// Low Value Exemption Enabled and Numeric Validation
			if ($this->request->post['payment_' . self::CODE . '_limit_for_low_value_exemption']
				&& $this->request->post['payment_' . self::CODE . '_allow_low_value_exemptions'] == "0") {
				$json['error']['limit-for-low-value-exemption'] = $this->language->get('error_limit_for_low_value_exemption_disabled');
			} elseif ($this->request->post['payment_' . self::CODE . '_limit_for_low_value_exemption']
				&& !is_numeric($this->request->post['payment_' . self::CODE . '_limit_for_low_value_exemption'])) {
				$json['error']['limit-for-low-value-exemption'] = $this->language->get('error_limit_for_low_value_exemption');
			}
			// Sort Order Numeric Validation
			if ($this->request->post['payment_' . self::CODE . '_sort_order']
				&& !is_numeric($this->request->post['payment_' . self::CODE . '_sort_order'])) {
				$json['error']['sort-order'] = $this->language->get('error_sort_order');
			}
		}

		return $json;
	}

	/**
	 * Init the setting data
	 *
	 * @return array
	 */
	private function initializeData(): array {

		$this->load->model('localisation/order_status');
		$this->load->model('localisation/geo_zone');

		$data = [];
		$data['window_states'] = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getPaymentWindowStates();
		$data['rounding_modes'] = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getRoundingModes();
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses(
		);
		$data['geo_zones']      = $this->model_localisation_geo_zone->getGeoZones(
		);
		$data['header']         = $this->load->controller('common/header');
		$data['column_left']    = $this->load->controller('common/column_left');
		$data['footer']         = $this->load->controller('common/footer');
		$data['module_version'] = self::MODULE_VERSION;

		return $data;
	}

	/**
	 * Populates the page breadcrumbs
	 *
	 * @return array
	 */
	private function prepareSettingBreadcrumbs(): array {
		$userToken = $this->session->data['user_token'];
		$data = [];
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link(
				'common/dashboard',
				"user_token={$userToken}"
			)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(
				'marketplace/extension',
				"user_token={$userToken}&type=payment"
			)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(
				self::WORLDLINE,
				"user_token={$userToken}"
			)
		];
		$data['save'] = $this->url->link(
			self::WORLDLINE . '.save',
			"user_token={$userToken}"
		);
		$data['back']        = $this->url->link(
			'marketplace/extension',
			'user_token=' . $this->session->data['user_token'] . '&type=payment',
			true
		);

		return $data;
	}

	/**
	 * Populates the setting values
	 *
	 * @return array
	 */
	private function prepareSettingValues(): array {
		$fields = [
			'status'                        => '0',
			'merchant'                      => '',
			'access_token'                  => '',
			'secret_token'                  => '',
			'md5'                           => '',
			'window_id'                     => '1',
			'payment_method_title'          => 'Worldline Online Checkout',
			'window_state'                  => '1', //Full Screen
			'instant_capture'               => '0',
			'immediate_redirect_to_accept'  => '0',
			'surcharge'                     => '0',
			'payment_method_update'         => '0',
			'allow_low_value_exemptions'    => '0',
			'limit_for_low_value_exemption' => '',
			'rounding_mode'                 => 'default',
			'order_status_completed'        => '2', //Processing
			'geo_zone_id'                   => '0',
			'total'                         => '',
			'sort_order'                    => '',

		];

		$data = [];
		// Loop through configuration fields and populate them
		foreach ($fields as $fieldKey => $fieldDefaultValue) {
			$fieldPath = 'payment_' . self::CODE . "_{$fieldKey}";

			if ($this->config->has($fieldPath)) {
				$data[$fieldPath] = $this->config->get($fieldPath);
			} else {
				// Init default value
				$data[$fieldPath] = $fieldDefaultValue;
			}
		}

		return $data;
	}

	/**
	 * Set the first letter to uppercase
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	private function checkoutStatus($status): string {
		if (!isset($status)) {
			return "";
		}
		$firstLetter        = substr($status, 0, 1);
		$firstLetterToUpper = strtoupper($firstLetter);

		return str_replace(
			$firstLetter,
			$firstLetterToUpper,
			$status
		);
	}

	/**
	 * Create transaction operation elements
	 *
	 * @param mixed $transactionOperation
	 * @param mixed $decimalPoint
	 * @param mixed $thousandSeparator
	 *
	 * @return array
	 */
	private function createTransactionOperations(
		$transactionOperation,
		$decimalPoint,
		$thousandSeparator
	): array {
		$result = [];
		foreach ($transactionOperation as $operation) {
			$eventText = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getEventText(
				$operation
			);
			if ($eventText['description'] != null) {
				$eventInfoExtra = '';

				if ($operation->status != 'approved') {
					$eventInfoExtra = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getEventExtra(
						$operation
					);
					$eventInfoExtra = '<div style="color:#ec6459;">' . $eventInfoExtra . '</div>';
				}
				$ope          = [];
				$ope['title'] = $eventText['title'];
				$ope['title'] = str_replace(
					"CollectorBank",
					"Walley",
					$ope['title']
				);

				$ope['description'] = $eventText['description'] . $eventInfoExtra;

				$ope['description'] = str_replace(
					"CollectorBank",
					"Walley",
					$ope['description']
				);
				$ope['createdDate'] = $operation->createddate;

				$ope['action']   = $operation->action;
				$operationAmount = $this->convertPriceFromMinorunits(
					$operation->amount,
					$operation->currency->minorunits,
					$decimalPoint,
					$thousandSeparator
				);
				$ope['amount']   = "{$operation->currency->code} {$operationAmount}";
				$result[]        = $ope;
			}
			if (isset($operation->transactionoperations) && count(
				$operation->transactionoperations
			) > 0) {
				$result = array_merge(
					$result,
					$this->createTransactionOperations(
						$operation->transactionoperations,
						$decimalPoint,
						$thousandSeparator
					)
				);
			}
		}

		return $result;
	}
}
