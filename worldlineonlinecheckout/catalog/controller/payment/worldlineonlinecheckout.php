<?php
namespace Opencart\Catalog\Controller\Extension\WorldlineOnlineCheckout\Payment;
class WorldlineOnlineCheckout extends \Opencart\System\Engine\Controller {
	/**
	 * @var string
	 */
	private const CODE = 'worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const WORLDLINE = 'extension/worldlineonlinecheckout/payment/worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const WORLDLINE_TOTAL = 'extension/worldlineonlinecheckout/total/worldlineonlinecheckout_fee';
	/**
	 * @var string
	 */
	private const WORLDLINE_AJAX_ENDPOINT = 'index.php?route=extension/worldlineonlinecheckout/payment/worldlineonlinecheckout';
	/**
	 * @var string
	 */
	private const WORLDLINE_STATIC_ENDPOINT = 'https://static.bambora.com';

	/**
	 * Inits the payment method
	 *
	 * @return mixed
	 */
	public function index(): mixed {
		$this->load->language(self::WORLDLINE);
		$this->load->model('checkout/order');

		$viewData = $this->initializeData();
		$viewData = array_merge($viewData, $this->prepareCheckoutData());

		return $this->load->view(self::WORLDLINE, $viewData);
	}

	/**
	 * Init and open the payment window
	 *
	 * @return void
	 */
	public function confirm(): void {
		$this->load->model('checkout/order');
		$this->load->model(self::WORLDLINE);
		$this->load->language(self::WORLDLINE);

		$checkoutSessionRequest = $this->createCheckoutSessionRequest();
		$checkoutSessionResponse = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->setCheckoutSession(
			$checkoutSessionRequest
		);

		$json = [];
		if (!$checkoutSessionResponse || !$checkoutSessionResponse->meta->result) {
			$json['error'] = $this->language->get(
				'error_payment_window'
			) . ' ' . $checkoutSessionResponse->meta->message->enduser;
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$this->language->get(
					'error_payment_window'
				) . ' ' . $checkoutSessionResponse->meta->message->merchant
			);
		} else {
			$json['token'] = $checkoutSessionResponse->token;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * When a customer returns from a successful payment
	 *
	 * @return void
	 */
	public function accept(): void {
		$this->language->load(self::WORLDLINE);
		$this->load->model('checkout/order');

		$getParameteres = $_GET;
		$message = "";
		if ($this->validateRequest($getParameteres, $message)) {
			$orderInfo = $this->model_checkout_order->getOrder(
				$getParameteres['orderid']
			);
			if ($orderInfo['order_status_id'] !== $this->config->get(
				'payment_' . self::CODE . '_order_status_completed'
			)) {
				$message = $this->language->get('accept_order_text');
				$this->model_checkout_order->addHistory(
					$orderInfo['order_id'],
					1,
					$message
				);
			}
		}
		$this->response->redirect($this->url->link('checkout/success', '', true));
	}

	/**
	 * Handles the callback coming from Worldline Online Checkout
	 *
	 * @return void
	 */
	public function callback(): void {
		$this->language->load(self::WORLDLINE);
		$this->load->model('checkout/order');
		$this->load->model(self::WORLDLINE);

		$getParameteres = $_GET;

		try {
			$message = "";
			$transaction = null;
			if (!$this->validateCallback($getParameteres, $message, $transaction)) {
				$orderId = array_key_exists(
					'orderid',
					$getParameteres
				) ? $getParameteres['orderid'] : -1;
				$errorMessage = "Callback failed for order: {$orderId}. Reason: {$message}";

				if ($orderId != -1) {
					$this->model_checkout_order->addHistory(
						$orderId,
						1,
						$errorMessage
					);
				}
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
					$errorMessage
				);
				header(
					'X-EPay-System: ' . $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getModuleHeaderInformation(
					),
					true,
					500
				);
				exit("Callback Failed - Check the log to see the issue");
			}

			//Lock for multiple callbacks on already confirmed payment
			$orderId = $transaction->orderid;
			$dbTransaction = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getDbTransaction(
				$orderId
			);
			$orderInfo = $this->model_checkout_order->getOrder($orderId);
			if (($dbTransaction && !empty($dbTransaction['transaction_id'])) || $orderInfo['order_status_id'] === $this->config->get(
				'payment_' . self::CODE . '_order_status_completed'
			)) {
				header(
					'X-EPay-System: ' . $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getModuleHeaderInformation(
					),
					true,
					200
				);
				exit("The callback was a success - Order already created");
			}

			$decimalPoint = $this->language->get('currency_decimal_point');
			$thousandSeparator = $this->language->get('currency_thousand_separator');
			$minorunits = $transaction->currency->minorunits;
			$amount = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->convertPriceFromMinorunits(
				$transaction->total->authorized,
				$minorunits,
				$decimalPoint,
				$thousandSeparator
			);

			// Add surcharge fee to the order
			if ($this->config->get(
				'payment_' . self::CODE . '_surcharge'
			) == 1 && $transaction->total->feeamount > 0) {
				$this->addSurchargeToOrderTotals(
					$transaction,
					$orderInfo['order_status_id']
				);
			}

			// Add transaction to database
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->addDbTransaction(
				$orderId,
				$transaction->id,
				$transaction->total->authorized,
				$transaction->currency->code
			);

			$paymentTypeDisplayName = "";
			if (is_array($transaction->information->paymenttypes) && count(
				$transaction->information->paymenttypes
			) > 0) {
				$paymentTypeDisplayName = $transaction->information->paymenttypes[0]->displayname;
			}

			$accountNumber = "";
			if (is_array($transaction->information->primaryaccountnumbers) && count(
				$transaction->information->primaryaccountnumbers
			) > 0) {
				$accountNumber = $transaction->information->primaryaccountnumbers[0]->number;
			}

			$paymentInfo = $paymentTypeDisplayName . ' ' . $accountNumber;
			$comment = '<table style="width: 60%"><tbody>';
			$comment .= '<tr><td>' . '<b>' . $this->language->get(
				'payment_process'
			) . '</b></td><td>' . $transaction->currency->code . ' ' . $amount . '</td></tr>';
			$comment .= '<tr><td>' . '<b>' . $this->language->get(
				'payment_with_transactionid'
			) . '</b></td><td>' . $transaction->id . '</td></tr>';
			$comment .= '<tr><td>' . '<b>' . $this->language->get(
				'payment_card'
			) . '</b></td><td>' . $paymentInfo . '</td></tr>';
			$comment .= '</tbody></table>';

			$this->model_checkout_order->addHistory(
				$orderId,
				$this->config->get(
					'payment_' . self::CODE . '_order_status_completed'
				),
				$comment,
				true
			);

			// Update payment method title on order
			if ($this->config->get(
				'payment_' . self::CODE . '_payment_method_update'
			) === "1" && !empty($paymentTypeDisplayName)) {
				try {
					$paymentMethodTitle = $this->config->get(
						'payment_' . self::CODE . '_payment_method_title'
					);
					$newPaymentMethodTitle = "{$paymentMethodTitle} - {$paymentTypeDisplayName}";

					$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->updateDbPaymentMethod($orderId, $newPaymentMethodTitle);
				} catch (\Exception $ex) {
					$errorMessage = "Could not update Payment Method title for order {$orderId}: " . $ex->getMessage();
					$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
						$errorMessage
					);
				}
			}

			header(
				'X-EPay-System: ' . $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getModuleHeaderInformation(
				),
				true,
				200
			);
			exit("The callback was a success");
		} catch (\Exception $ex) {
			header(
				'X-EPay-System: ' . $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getModuleHeaderInformation(
				),
				true,
				500
			);
			$errorMessage = "An error occured: " . $ex->getMessage();
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$errorMessage
			);
			$orderId = array_key_exists(
				'orderid',
				$getParameteres
			) ? $getParameteres['orderid'] : -1;
			if ($orderId != -1) {
				$this->model_checkout_order->addHistory(
					$orderId,
					1,
					$errorMessage
				);
			}
			exit($errorMessage);
		}
	}

	/**
	 * Initialize Data
	 *
	 * @return array
	 */
	private function initializeData(): array {
		$data = [];
		$data['text_title'] = $this->config->get(
			'payment_' . self::CODE . '_payment_method_title'
		);
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_payment'] = $this->language->get('text_payment');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['language'] = $this->config->get('config_language');
		$data['worldline_static_endpoint'] = self::WORLDLINE_STATIC_ENDPOINT;
		$data['worldline_ajax_endpoint'] = self::WORLDLINE_AJAX_ENDPOINT;

		return $data;
	}

	/**
	 * Prepare Checkout Data
	 *
	 * @return array
	 */
	private function prepareCheckoutData(): array {
		$orderInfo = $this->model_checkout_order->getOrder(
			$this->session->data['order_id']
		);
		$data = [];
		$data['payment_' . self::CODE . '_allowed_payment_type_ids'] = $this->getAllowedPaymentTypeIds(
			$orderInfo['currency_code'],
			$orderInfo['total'],
			$orderInfo['order_id']
		);
		$data['payment_' . self::CODE . '_window_state'] = $this->config->get(
			'payment_' . self::CODE . '_window_state'
		);

		return $data;
	}

	/**
	 * Returns an array of allowed payment type id's
	 *
	 * @param mixed $currency
	 * @param mixed $amount
	 * @param mixed $orderId
	 *
	 * @return array
	 */
	private function getAllowedPaymentTypeIds($currency, $amount, $orderId): array {
		$this->load->model(self::WORLDLINE);

		$paymentCardIdsArray = [];
		$minorunits = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getCurrencyMinorunits(
			$currency
		);
		$amountInMinorunits = $this->convertPriceToMinorunits(
			$amount,
			$minorunits
		);
		$paymentTypeResponse = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getPaymentTypeIds(
			$currency,
			$amountInMinorunits
		);

		if (!isset($paymentTypeResponse) || !$paymentTypeResponse->meta->result) {
			$errorMessage = "Get allowed payment types failed for order: {$orderId} Reason: ";
			$errorMessage .= isset($paymentTypeResponse) ? $paymentTypeResponse->meta->message->merchant : "Could not connect to Worldline";
			$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->worldlineOnlineCheckoutLog(
				$errorMessage
			);
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
	 * Create Worldline Online Checkout session request
	 *
	 * @return array
	 */
	private function createCheckoutSessionRequest() {
		$this->language->load(self::WORLDLINE);

		$orderInfo = $this->model_checkout_order->getOrder(
			$this->session->data['order_id']
		);
		$minorunits = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getCurrencyMinorunits(
			$orderInfo['currency_code']
		);
		$orderTotalAmount = 0;
		$orderTaxAmount = 0;
		$orderTotals = [];
		$orderTotals = $this->model_checkout_order->getTotals(
			$this->session->data['order_id']
		);

		foreach ($orderTotals as $total) {
			if ($total['code'] === "tax") {
				$orderTaxAmount += $total['value'];
			} elseif ($total['code'] === "total") {
				$orderTotalAmount = $total['value'];
			}
		}

		//Format for currency
		$orderTaxAmount = $this->formatForCurrency($orderTaxAmount);
		$orderTaxAmountMinorunits = $this->convertPriceToMinorunits(
			$orderTaxAmount,
			$minorunits
		);

		$orderTotalAmount = $this->formatForCurrency($orderTotalAmount);
		$orderTotalAmountMinorunits = $this->convertPriceToMinorunits(
			$orderTotalAmount,
			$minorunits
		);

		$params = [];
		$params['language'] = $this->config->get('config_language');
		$params['instantcaptureamount'] = $this->config->get(
			'payment_' . self::CODE . '_instant_capture'
		) === "1" ? $orderTotalAmountMinorunits : 0;
		$params['paymentwindowid'] = $this->config->get(
			'payment_' . self::CODE . '_window_id'
		);

		$params['customer'] = [];
		$params['customer']['phonenumbercountrycode'] = html_entity_decode(
			$orderInfo['payment_iso_code_2'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['customer']['phonenumber'] = html_entity_decode(
			$orderInfo['telephone'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['customer']['email'] = html_entity_decode(
			$orderInfo['email'],
			ENT_QUOTES,
			'UTF-8'
		);

		$params['order'] = [];
		$params['order']['id'] = $orderInfo['order_id'];
		$params['order']['amount'] = $orderTotalAmountMinorunits;
		$params['order']['vatamount'] = $orderTaxAmountMinorunits;

		$params['order']['currency'] = $orderInfo['currency_code'];
		$params['order']['shippingaddress'] = $this->createCustomerAddress(
			$orderInfo,
			'shipping'
		);
		$params['order']['billingaddress'] = $this->createCustomerAddress(
			$orderInfo,
			'payment'
		);
		$params['order']['lines'] = $this->createOrderLines(
			$orderTotals,
			$minorunits
		);

		$params['url'] = [];
		$params['url']['immediateredirecttoaccept'] = $this->config->get(
			'payment_' . self::CODE . '_immediate_redirect_to_accept'
		);
		$params['url']['accept'] = $this->url->link(
			self::WORLDLINE . '.accept',
			'',
			true
		);
		$params['url']['cancel'] = $this->url->link('checkout/checkout', '', true);
		$params['url']['callbacks'] = [];
		$params['url']['callbacks'][] = [
			'url' => $this->url->link(
				self::WORLDLINE . '.callback',
				'',
				true
			)
		];

		$allow_low_value = $this->config->get(
			'payment_' . self::CODE . '_allow_low_value_exemptions'
		);
		if ($allow_low_value) {
			if ($params['order']['amount'] < $this->convertPriceToMinorunits(
				$this->config->get(
					'payment_' . self::CODE . '_limit_for_low_value_exemption'
				),
				$minorunits
			)) {
				$params['securityexemption'] = "lowvaluepayment";
				$params['securitylevel'] = "none";
			}
		}

		return $params;
	}

	/**
	 * Format the base amount to the currency based amount
	 *
	 * @param mixed $amount
	 *
	 * @return mixed
	 */
	private function formatForCurrency($amount): mixed {
		return $this->currency->format(
			$amount,
			$this->session->data['currency'],
			0,
			false
		);
	}

	/**
	 * Create the customer shipping or billing address for the payment request
	 *
	 * @param mixed $orderInfo
	 * @param mixed $type
	 *
	 * @return string[]
	 */
	private function createCustomerAddress($orderInfo, $type): array {
		$params = [];
		$params['att'] = "";
		$params['firstname'] = html_entity_decode(
			$orderInfo[$type . '_firstname'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['lastname'] = html_entity_decode(
			$orderInfo[$type . '_lastname'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['street'] = html_entity_decode(
			$orderInfo[$type . '_address_1'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['zip'] = html_entity_decode(
			$orderInfo[$type . '_postcode'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['city'] = html_entity_decode(
			$orderInfo[$type . '_city'],
			ENT_QUOTES,
			'UTF-8'
		);
		$params['country'] = html_entity_decode(
			$orderInfo[$type . '_iso_code_3'],
			ENT_QUOTES,
			'UTF-8'
		);

		return $params;
	}

	/**
	 * Create the order lines for the payment request
	 *
	 * @param mixed $orderTotals
	 * @param mixed $minorunits
	 *
	 * @return array[]
	 */
	private function createOrderLines($orderTotals, $minorunits): array {
		$orderProducts = [];
		$orderProducts = $this->model_checkout_order->getProducts(
			$this->session->data['order_id']
		);

		$params = [];
		$lineNumber = 1;
		//Add product lines
		foreach ($orderProducts as $product) {
			$line = [];
			$line['id'] = $product['product_id'];
			$line['linenumber'] = $lineNumber;
			$line['description'] = $product['name'];
			$line['text'] = $product['name'];
			$line['quantity'] = $product['quantity'];
			$line['unit'] = $this->language->get('pcs');
			$lineTaxAmount = $product['tax'] * $product['quantity'];
			$lineTotalPrice = $product['total'];
			$lineTotalPriceInclVat = $product['total'] + $lineTaxAmount;

			//Format for currency
			$lineTaxAmount = $this->formatForCurrency($lineTaxAmount);
			$lineTotalPrice = $this->formatForCurrency($lineTotalPrice);
			$lineTotalPriceInclVat = $this->formatForCurrency(
				$lineTotalPriceInclVat
			);

			$line['totalprice'] = $this->convertPriceToMinorunits(
				$lineTotalPrice,
				$minorunits
			);
			$line['totalpriceinclvat'] = $this->convertPriceToMinorunits(
				$lineTotalPriceInclVat,
				$minorunits
			);
			$line['totalpricevatamount'] = $this->convertPriceToMinorunits(
				$lineTaxAmount,
				$minorunits
			);
			$line['unitprice'] = $this->convertPriceToMinorunits(
				$lineTotalPrice / $product['quantity'],
				$minorunits
			);
			$line['unitpriceinclvat'] = $this->convertPriceToMinorunits(
				$lineTotalPriceInclVat / $product['quantity'],
				$minorunits
			);
			$line['unitpricevatamount'] = $this->convertPriceToMinorunits(
				$lineTaxAmount / $product['quantity'],
				$minorunits
			);
			$line['vat'] = round(
				$lineTaxAmount > 0 ? ($lineTaxAmount / $product['total']) * 100 : 0,
				2,
				$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getRoundingMode(
				)
			);
			$params[] = $line;
			$lineNumber++;
		}

		$shipping = null;
		$orderTotalDiscount = null;
		$orderTotalVoucher = null;
		$orderTotal = null;
		foreach ($orderTotals as $total) {
			if ($total['code'] === "coupon") {
				$orderTotalDiscount = $total;
			} elseif ($total['code'] === "voucher") {
				$orderTotalVoucher = $total;
			} elseif ($total['code'] === "shipping") {
				$shipping = $total;
			} elseif ($total['code'] === "total") {
				$orderTotal = $total;
			}
		}

		if (isset($shipping) && isset($this->session->data['shipping_method'])) {
			$shippingMethod = $this->session->data['shipping_method'];
			if (!empty($shippingMethod['cost']) && $shippingMethod['cost'] > 0) {
				//Add shipping
				$shipping = [];
				$shipping['id'] = $this->language->get('text_shipping_id');
				$shipping['linenumber'] = $lineNumber;
				$shipping['description'] = $shippingMethod['name'];
				$shipping['text'] = $shippingMethod['text'];
				$shipping['quantity'] = 1;
				$shipping['unit'] = $this->language->get('pcs');

				$shippingTaxArray = $this->tax->getRates(
					$shippingMethod['cost'],
					$shippingMethod['tax_class_id']
				);
				$shippingTaxAmount = 0;
				foreach ($shippingTaxArray as $shippingTax) {
					$shippingTaxAmount += $shippingTax['amount'];
				}
				$shippingTotalPrice = $shippingMethod['cost'];
				$shippingWithTax = $shippingTotalPrice + $shippingTaxAmount;
				//Format for currency
				$shippingTaxAmount = $this->formatForCurrency($shippingTaxAmount);
				$shippingTotalPrice = $this->formatForCurrency($shippingTotalPrice);
				$shippingWithTax = $this->formatForCurrency($shippingWithTax);

				$shipping['totalprice'] = $this->convertPriceToMinorunits(
					$shippingTotalPrice,
					$minorunits
				);
				$shipping['totalpriceinclvat'] = $this->convertPriceToMinorunits(
					$shippingWithTax,
					$minorunits
				);
				$shipping['totalpricevatamount'] = $this->convertPriceToMinorunits(
					$shippingTaxAmount,
					$minorunits
				);
				$shipping['unitprice'] = $this->convertPriceToMinorunits(
					$shippingTotalPrice,
					$minorunits
				);
				$shipping['unitpriceinclvat'] = $this->convertPriceToMinorunits(
					$shippingWithTax,
					$minorunits
				);
				$shipping['unitpricevatamount'] = $this->convertPriceToMinorunits(
					$shippingTaxAmount,
					$minorunits
				);
				$shipping['vat'] = round(
					$shippingTaxAmount > 0 ? ($shippingTaxAmount / $shippingMethod['cost']) * 100 : 0,
					2,
					$this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getRoundingMode(
					)
				);
				$params[] = $shipping;
				$lineNumber++;
			}
		}

		if (!empty($orderTotalDiscount) && (float)$orderTotalDiscount['value'] < 0) {
			$coupon = [];
			$coupon['id'] = $this->language->get('text_coupon_id');
			$coupon['linenumber'] = $lineNumber;
			$coupon['description'] = $orderTotalDiscount['title'];
			$coupon['text'] = $orderTotalDiscount['title'];
			$coupon['quantity'] = 1;
			$coupon['unit'] = $this->language->get('pcs');
			$couponTotalPrice = $orderTotalDiscount['value'];
			//Format for currency
			$couponTotalPrice = $this->formatForCurrency($couponTotalPrice);

			$coupon['totalprice'] = $this->convertPriceToMinorunits(
				$couponTotalPrice,
				$minorunits
			);
			$coupon['totalpriceinclvat'] = $this->convertPriceToMinorunits(
				$couponTotalPrice,
				$minorunits
			);
			$coupon['totalpricevatamount'] = 0;
			$coupon['unitprice'] = $this->convertPriceToMinorunits(
				$couponTotalPrice,
				$minorunits
			);
			$coupon['unitpriceinclvat'] = $this->convertPriceToMinorunits(
				$couponTotalPrice,
				$minorunits
			);
			$coupon['unitpricevatamount'] = 0;
			$coupon['vat'] = 0;
			$params[] = $coupon;
			$lineNumber++;
		}

		if (!empty($orderTotalVoucher) && (float)$orderTotalVoucher['value'] < 0) {
			$voucher = [];
			$voucher['id'] = $this->language->get(
				'text_voucher_id'
			);
			$voucher['linenumber'] = $lineNumber;
			$voucher['description'] = $orderTotalVoucher['title'];
			$voucher['text'] = $orderTotalVoucher['title'];
			$voucher['quantity'] = 1;
			$voucher['unit'] = $this->language->get('pcs');
			$voucherTotalPrice = $orderTotalVoucher['value'];
			//Format for currency
			$voucherTotalPrice = $this->formatForCurrency($voucherTotalPrice);

			$voucher['totalprice'] = $this->convertPriceToMinorunits(
				$voucherTotalPrice,
				$minorunits
			);
			$voucher['totalpriceinclvat'] = $this->convertPriceToMinorunits(
				$voucherTotalPrice,
				$minorunits
			);
			$voucher['totalpricevatamount'] = 0;
			$voucher['unitprice'] = $this->convertPriceToMinorunits(
				$voucherTotalPrice,
				$minorunits
			);
			$voucher['unitpriceinclvat'] = $this->convertPriceToMinorunits(
				$voucherTotalPrice,
				$minorunits
			);
			$voucher['unitpricevatamount'] = 0;
			$voucher['vat'] = 0;
			$params[] = $voucher;
			$lineNumber++;
		}

		$roundingOrderline = $this->createRoundingFee($orderTotal, $minorunits, $params, $lineNumber);
		if ($roundingOrderline) {
			$params[] = $roundingOrderline;
		}

		return $params;
	}

	private function convertPriceToMinorunits($price, $minorunits) {
		return $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->convertPriceToMinorunits(
			$price,
			$minorunits
		);
	}

	private function createRoundingFee($orderTotal, $minorunits, $params, $lineNumber) {
		$ocTotal = $this->convertPriceToMinorunits($orderTotal['value'], $minorunits);
		$wocTotal = 0;
		foreach ($params as $orderLine) {
			$wocTotal += $orderLine['quantity'] * $orderLine['unitpriceinclvat'];
		}
		if ($ocTotal != $wocTotal) {
			$roundingOrderline                        = [];
			$roundingOrderline['id']                  = $this->language->get('adjustment');
			$roundingOrderline['totalprice']          = $ocTotal - $wocTotal;
			$roundingOrderline['totalpriceinclvat']   = $ocTotal - $wocTotal;
			$roundingOrderline['totalpricevatamount'] = 0;
			$roundingOrderline['text']                = $this->language->get('Rounding adjustment');
			$roundingOrderline['unitprice']           = $ocTotal - $wocTotal;
			$roundingOrderline['unitpriceinclvat']    = $ocTotal - $wocTotal;
			$roundingOrderline['unitpricevatamount']  = 0;
			$roundingOrderline['quantity']            = 1;
			$roundingOrderline['description']         = $this->language->get('Rounding adjustment');
			$roundingOrderline['linenumber']          = $lineNumber++;
			$roundingOrderline['unit']                = $this->language->get('pcs');
			$roundingOrderline['vat']                 = 0.0;

			return $roundingOrderline;
		}
	}

	/**
	 * Validate the request get parameteres
	 *
	 * @param mixed $getParameters
	 * @param mixed &$message
	 *
	 * @return bool
	 */
	private function validateRequest($getParameters, &$message) {
		// Check exists txnid!
		if (empty($getParameters["txnid"])) {
			$message = "No GET(txnid) was supplied to the system!";

			return false;
		}
		// Check exists orderid!
		if (empty($getParameters["orderid"])) {
			$message = "No GET(orderid) was supplied to the system!";

			return false;
		}
		// Check exists hash!
		if (empty($getParameters["hash"])) {
			$message = "No GET(hash) was supplied to the system!";

			return false;
		}
		// Validate MD5!
		$merchantMd5Key = $this->config->get('payment_' . self::CODE . '_md5');
		if (!empty($merchantMd5Key)) {
			$concatenatedValues = '';
			foreach ($getParameters as $key => $value) {
				if ('hash' === $key) {
					break;
				}
				$concatenatedValues .= $value;
			}
			$genstamp = md5($concatenatedValues . $merchantMd5Key);
			if (!hash_equals($genstamp, $getParameters["hash"])) {
				$message = "Hash validation failed - Please check your MD5 key";

				return false;
			}
		}

		return true;
	}

	/**
	 * Validate the Callback from Worldline Online Checkout
	 *
	 * @param mixed $getParameters
	 * @param mixed $message
	 * @param mixed $transaction
	 * @param mixed $getParameteres
	 *
	 * @return bool
	 */
	private function validateCallback($getParameters, &$message, &$transaction): bool {
		if (!$this->validateRequest($getParameters, $message)) {
			return false;
		}

		$transactionId = $getParameters["txnid"];
		$transactionResponse = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->getTransaction(
			$transactionId
		);

		if (!isset($transactionResponse) || !$transactionResponse->meta->result) {
			$message = isset($transactionResponse) ? $transactionResponse->meta->message->merchant : "Connection to Worldline Failed";

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
	 *
	 * @return void
	 */
	private function addSurchargeToOrderTotals($transaction, $currentStatusId): void {
		$this->load->language(self::WORLDLINE_TOTAL);
		$transactionFeeMinorUnits = $transaction->total->feeamount;
		$currencyValue = $this->currency->getValue($transaction->currency->code);
		$baseTransactionFee = $currencyValue > 0 ? ($transactionFeeMinorUnits / $currencyValue) : $transactionFeeMinorUnits;
		$transactionFee = $this->model_extension_worldlineonlinecheckout_payment_worldlineonlinecheckout->convertPriceFromMinorunits(
			$baseTransactionFee,
			$transaction->currency->minorunits
		);

		$orderTotals = [];
		$orderTotals = $this->model_checkout_order->getTotals(
			$transaction->orderid
		);

		if (!empty($orderTotals)) {
			$orderTotals[] = [
				'order_id' => $transaction->orderid,
				'code'     => self::CODE . '_fee',
				'title'    => $this->language->get(
					self::CODE . '_fee'
				) . ' ' . $transaction->information->paymenttypes[0]->displayname,
				'value'      => (float)$transactionFee,
				'sort_order' => 8
			];

			if (count($orderTotals) > 1) {
				$this->db->query(
					"DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$transaction->orderid . "'"
				);

				foreach ($orderTotals as $total) {
					// Add fee to the total price
					if ($total['code'] === 'total') {
						$total['value'] += $transactionFee;

						// Update the order entry
						$this->db->query(
							"UPDATE " . DB_PREFIX . "order SET total = '" . $total['value'] . "' WHERE order_id = '" . (int)$transaction->orderid . "'"
						);
					}
					$this->db->query(
						"INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$transaction->orderid . "', code = '" . $this->db->escape(
							$total['code']
						) . "', title = '" . $this->db->escape(
							$total['title']
						) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'"
					);
				}
				$transactionFeeFormatted = $this->currency->format(
					$transactionFee,
					$transaction->currency->code,
					false,
					true
				);
				$message = "Surcharge fee of {$transactionFeeFormatted} added to the order ";
				$this->model_checkout_order->addHistory(
					$transaction->orderid,
					$currentStatusId,
					$message
				);
			}
		} else {
			$message = "Could not add surcharge to the order";
			$this->model_checkout_order->addHistory(
				$transaction->orderid,
				$currentStatusId,
				$message
			);
		}
	}
}
