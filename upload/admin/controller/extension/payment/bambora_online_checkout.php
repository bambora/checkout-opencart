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
class ControllerExtensionPaymentBamboraOnlineCheckout extends Controller
{
    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

    /**
     * @var string
     */
    private $module_version = '1.2.1';

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var array
     */
    private $errors = array();

    /**
     * @var array();
     */
    private $errorFields = array('merchant', 'access_token', 'secret_token', 'permission');

    /**
     * Inits the module admin section
     */
    public function index()
    {
        $this->language->load('extension/payment/'.$this->module_name);
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {

            $this->session->data['success'] = $this->language->get('text_success');
            $this->model_setting_setting->editSetting($this->getConfigBaseName(), $this->request->post);
            if($this->is_oc_3()) {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
            } else {
                $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true));
            }

        }

        $this->initSettings();
        $this->initSettingsContent();
        $this->populateBreadcrums();
        $this->populateSettingValues();
        $this->populateErrorMessages();
        $this->response->setOutput($this->load->view('extension/payment/'.$this->module_name, $this->data));

    }

    /**
     * Init the settings
     */
    protected function initSettings()
    {
        $keys = array(
            //Entry
            'entry_status',
            'entry_merchant',
            'entry_access_token',
            'entry_secret_token',
            'entry_md5',
            'entry_window_state',
            'entry_window_id',
            'entry_surcharge',
            'entry_instant_capture',
            'entry_immediate_redirect_to_accept',
            'entry_rounding_mode',
            'entry_payment_method_title',
            'entry_total',
            'entry_order_status_completed',
            'entry_geo_zone',
            'entry_sort_order',
           //Help
            'help_status',
            'help_merchant',
            'help_access_token',
            'help_secret_token',
            'help_md5',
            'help_window_state',
            'help_window_id',
            'help_surcharge',
            'help_instant_capture',
            'help_immediate_redirect_to_accept',
            'help_rounding_mode',
            'help_payment_method_title',
            'help_total',
            'help_order_status_completed',
            'help_geo_zone',
            'help_sort_order',
        );
        foreach ($keys as $key) {
            $this->data[$key] = $this->language->get($key);
        }
    }

    /**
     * Init the setting content
     */
    protected function initSettingsContent()
    {
        $keys = array(
            'heading_title',
            'button_save',
            'button_cancel',
            'text_edit',
            'text_enabled',
            'text_disabled',
            'text_all_zones',
            'text_window_state_fullscreen',
            'text_window_state_overlay',
            'text_rounding_mode_default',
            'text_rounding_mode_always_up',
            'text_rounding_mode_always_down'
        );

        foreach ($keys as $key) {
            $this->data[$key] = $this->language->get($key);
        }

        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->load->model('localisation/geo_zone');
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');
        $this->data['module_version'] = $this->module_version;
    }

    /**
     * Populates the page breadcrums
     */
    protected function populateBreadcrums()
    {
        $this->data['breadcrumbs'] = array();
        if($this->is_oc_3()) {
            $this->data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            $this->data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
            );
            $this->data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/payment/'.$this->module_name, 'user_token=' . $this->session->data['user_token'], true)
            );
            $this->data['action'] = $this->url->link('extension/payment/'.$this->module_name, 'user_token=' . $this->session->data['user_token'], true);
            $this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        } else {
            $this->data['breadcrumbs'][] = array(
              'text' => $this->language->get('text_home'),
              'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], true)
          );
            $this->data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
            );
            $this->data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/payment/'.$this->module_name, 'token=' . $this->session->data['token'], true)
            );
            $this->data['action'] = $this->url->link('extension/payment/'.$this->module_name, 'token=' . $this->session->data['token'], true);
            $this->data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);
        }
    }

    /**
     * Populates the setting values
     */
    protected function populateSettingValues()
    {
        $fields = array(
            'status',
            'merchant',
            'access_token',
            'secret_token',
            'md5',
            'window_state',
            'window_id',
            'surcharge',
            'instant_capture',
            'immediate_redirect_to_accept',
            'rounding_mode',
            'payment_method_title',
            'total',
            'order_status_completed',
            'geo_zone',
            'sort_order',
         );

        $defaultValues = array(
            'status' => '0',
            'window_state' => '1',
            'window_id' => '1',
            'surcharge' => '0',
            'instant_capture' => '0',
            'immediate_redirect_to_accept' => '0',
            'rounding_mode' => '1',
            'payment_method_title' => 'Bambora Online Checkout',
            'order_status_completed' => '5'
        );

        // Loop through configuration fields and populate them
        foreach ($fields as $field) {
            $field = $this->getConfigBaseName() . '_' . $field;
            if (isset($this->request->post[$field])) {
                $this->data[$field] = $this->request->post[$field];
            } else {
                $this->data[$field] = $this->config->get($field);
            }
        }

        // Check if fields with required default data is set. If not, we will populate default data to them.
        foreach ($defaultValues as $field => $default_value) {
            $field = $this->getConfigBaseName() . '_' . $field;
            if (!isset($this->data[$field])) {
                $this->data[$field] = $default_value;
            }
        }
    }

    /**
     * Populates the error messages
     */
    protected function populateErrorMessages()
    {
        foreach ($this->errorFields as $error) {
            if (isset($this->errors[$error])) {
                $this->data['error_' . $error] = $this->errors[$error];
            } else {
                $this->data['error_' . $error] = '';
            }
        }
    }

    /**
     * Validate if settings is correct
     *
     * @return boolean
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/'.$this->module_name)) {
            $this->errors['permission'] = $this->language->get('error_permission');
        } else {
            foreach ($this->errorFields as $error) {
                if ($error != 'permission' && !$this->request->post[$this->getConfigBaseName() . '_' . $error]) {
                    $this->errors[$error] = $this->language->get('error_' . $error);
                }
            }
        }

        return !$this->errors;
    }

    public function install()
    {
        $this->load->model('extension/payment/'.$this->module_name);
        $this->model_extension_payment_bambora_online_checkout->install();
    }

    // Legacy 2.0.0
    public function orderAction()
    {
        return $this->order();
    }

    // Legacy 2.0.3
    public function action()
    {
        return $this->order();
    }

    /**
     * Adds Transaction information to the order page
     *
     * @return mixed
     */
    public function order()
    {
        $this->load->language('extension/payment/'.$this->module_name);
        if($this->is_oc_3()){
            $data['user_token'] = $this->session->data['user_token'];
        } else {
            $data['token'] = $this->session->data['token'];
        }

        $data['order_id'] = $this->request->get['order_id'];

        return $this->load->view('extension/payment/' . $this->module_name . '_order', $data);
    }

    /**
     * Loads the content of the transaction information on the order page
     *
     * @return void
     */
    public function getPaymentTransaction()
    {
        $this->load->language('extension/payment/'.$this->module_name);
        $data = array();
        $moduleStatus = $this->config->get($this->getConfigBaseName() . '_status');
        if ($moduleStatus && isset($this->request->get['order_id'])) {
            $this->load->model('extension/payment/'.$this->module_name);
            $this->load->model('sale/order');

            $orderId = $this->request->get['order_id'];
            $dbTransaction = $this->model_extension_payment_bambora_online_checkout->getDbTransaction($orderId);
            if($this->is_oc_3()) {
                $data['user_token'] = $this->request->get['user_token'];
            } else {
                $data['token'] = $this->request->get['token'];
            }

            if ($dbTransaction && !empty($dbTransaction['transaction_id'])) {
                $transactionResponse = $this->model_extension_payment_bambora_online_checkout->getTransaction($dbTransaction['transaction_id']);

                if ($transactionResponse && $transactionResponse->meta->result) {
                    $transaction = $transactionResponse->transaction;
                    $data['text_payment_info'] = $this->language->get('text_payment_info');
                    $data['text_transaction_id'] = $this->language->get('text_transaction_id');
                    $data['text_transaction_authorized'] = $this->language->get('text_transaction_authorized');
                    $data['text_transaction_date'] = $this->language->get('text_transaction_date');
                    $data['text_transaction_payment_type'] = $this->language->get('text_transaction_payment_type');
                    $data['text_transaction_card_number'] = $this->language->get('text_transaction_card_number');
                    $data['text_transaction_surcharge_fee'] = $this->language->get('text_transaction_surcharge_fee');
                    $data['text_transaction_captured'] = $this->language->get('text_transaction_captured');
                    $data['text_transaction_refunded'] = $this->language->get('text_transaction_refunded');
                    $data['text_transaction_acquirer'] = $this->language->get('text_transaction_acquirer');
                    $data['text_transaction_status'] = $this->language->get('text_transaction_status');
                    $data['text_transaction_operations'] = $this->language->get('text_transaction_operations');
                    $data['text_transaction_operations_date'] = $this->language->get('text_transaction_operations_date');
                    $data['text_transaction_operations_action'] = $this->language->get('text_transaction_operations_action');
                    $data['text_transaction_operations_amount'] = $this->language->get('text_transaction_operations_amount');
                    $data['text_transaction_operations_eci'] = $this->language->get('text_transaction_operations_eci');
                    $data['text_transaction_operations_id'] = $this->language->get('text_transaction_operations_id');
                    $data['text_transaction_operations_parent_id'] = $this->language->get('text_transaction_operations_parent_id');
                    $data['text_btn_capture'] = $this->language->get('text_btn_capture');
                    $data['text_btn_refund'] = $this->language->get('text_btn_refund');
                    $data['text_btn_void'] = $this->language->get('text_btn_void');
                    $data['text_capture_payment_header'] = $this->language->get('text_capture_payment_header');
                    $data['text_capture_payment_body'] = $this->language->get('text_capture_payment_body');
                    $data['text_refund_payment_header'] = $this->language->get('text_refund_payment_header');
                    $data['text_refund_payment_body'] = $this->language->get('text_refund_payment_body');
                    $data['text_void_payment_header'] = $this->language->get('text_void_payment_header');
                    $data['text_void_payment_body'] = $this->language->get('text_void_payment_body');
                    $data['text_no'] = $this->language->get('text_no');
                    $data['text_yes'] = $this->language->get('text_yes');
                    $data['text_tooltip'] = $this->language->get('text_tooltip');
                    $data['text_goto_bambora_admin'] = $this->language->get('text_goto_bambora_admin');
                    $data['error_amount_format'] = $this->language->get('error_amount_format');
                    $data['error_action_base'] = $this->language->get('error_action_base');
                    $date['date_format'] = $this->language->get('date_format');

                    $data['transaction'] = array();
                    $data['transaction']['id'] = $transaction->id;

                    $decimalPoint = $this->language->get('currency_decimal_point');
                    $thousandSeparator = $this->language->get('currency_thousand_separator');

                    $authorizedAmount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->total->authorized, $transaction->currency->minorunits, $decimalPoint, $thousandSeparator);
                    $data['transaction']['authorized'] = "{$transaction->currency->code} {$authorizedAmount}";

                    if($this->is_oc_3()) {
                        $data['transaction']['date'] = $transaction->createddate;
                    } else {
                        $data['transaction']['date'] = date($this->language->get('date_format'), strtotime($transaction->createddate));
                    }

                    if(is_array($transaction->information->paymenttypes) && count($transaction->information->paymenttypes) > 0) {
                        $data['transaction']['paymentType'] = $transaction->information->paymenttypes[0]->displayname;
                    } else {
                        $data['transaction']['paymentType'] = "";
                    }

                    if(is_array($transaction->information->primaryaccountnumbers) && count($transaction->information->primaryaccountnumbers) > 0) {
                        $data['transaction']['cardNumber'] = $transaction->information->primaryaccountnumbers[0]->number;
                    } else {
                        $data['transaction']['cardNumber'] = "";
                    }

                    $surchargeFeeAmount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->total->feeamount, $transaction->currency->minorunits, $decimalPoint, $thousandSeparator);
                    $data['transaction']['surchargeFee'] = "{$transaction->currency->code} {$surchargeFeeAmount}";

                    $capturedAmount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->total->captured, $transaction->currency->minorunits, $decimalPoint, $thousandSeparator);
                    $data['transaction']['captured'] = "{$transaction->currency->code} {$capturedAmount}";

                    $redundedAmount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->total->credited, $transaction->currency->minorunits, $decimalPoint, $thousandSeparator);
                    $data['transaction']['refunded'] = "{$transaction->currency->code} {$redundedAmount}";

                    if(is_array($transaction->information->acquirers) && count($transaction->information->acquirers) > 0) {
                        $data['transaction']['acquirer'] = $transaction->information->acquirers[0]->name;
                    } else {
                         $data['transaction']['acquirer'] = "";
                    }

                    $data['transaction']['status'] = $this->checkoutStatus($transaction->status);
                    $data['transaction']['currencyCode'] = $transaction->currency->code;
                    $data['transaction']['orderId'] = $orderId;

                    $availableForCapture = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->available->capture, $transaction->currency->minorunits);
                    $data['transaction']['availableForCapture'] = $availableForCapture;

                    $availableForRefund = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($transaction->available->credit, $transaction->currency->minorunits);
                    $data['transaction']['availableForRefund'] = $availableForRefund;
                    $data['transaction']['canVoid'] = $transaction->candelete;

                    $data['showActions'] = $availableForCapture > 0 || $availableForRefund > 0 || $transaction->candelete;

                    $transactionOperationsResponse = $this->model_extension_payment_bambora_online_checkout->getTransactionOperations($dbTransaction['transaction_id']);
                    $data['transaction']['operations'] = array();
                    if ($transactionOperationsResponse && $transactionOperationsResponse->meta->result === true) {
                        $transactionOperation = $transactionOperationsResponse->transactionoperations;
                        if (count($transactionOperation) > 0) {
                            $data['transaction']['operations'] = $this->createTransactionOperations($transactionOperation, $decimalPoint, $thousandSeparator);
                        }
                    }
                    $data['getPaymentTransaction_success'] = true;
                } else {
                    $data['getPaymentTransaction_success'] = false;
                    $errorMessage = $transactionResponse ? $transactionResponse->meta->message->merchant : $this->language->get('error_getTransaction_api_error');
                    $data['text_getPaymentTransaction_error'] = $errorMessage;
                    $this->model_extension_payment_bambora_online_checkout->bamboraLog($errorMessage);
                }
            } else {
                $data['getPaymentTransaction_success'] = false;
                $errorMessage = $this->language->get('error_get_transaction_db');
                $data['text_getPaymentTransaction_error'] = $errorMessage;
                $this->model_extension_payment_bambora_online_checkout->bamboraLog($errorMessage);
            }
        } else {
            $data['getPaymentTransaction_success'] = false;
            if(!$moduleStatus) {
                $errorMessage = $this->language->get('error_module_not_loaded');
            } else {
                $errorMessage =  $this->language->get('error_order_id_not_supplied');
            }
            $data['text_getPaymentTransaction_error'] = $errorMessage;
        }

        $this->response->setOutput($this->load->view('extension/payment/'.$this->module_name.'_order_ajax', $data));
    }

    /**
     * Create transaction operation elements
     *
     * @param mixed $transactionOperation
     * @param mixed $decimalPoint
     * @param mixed $thousandSeparator
     * @return array
     */
    protected function createTransactionOperations($transactionOperation, $decimalPoint, $thousandSeparator)
    {
        $result = array();
        foreach ($transactionOperation as $operation) {
            $ope = array();
            if($this->is_oc_3()) {
                $ope['createdDate'] = $operation->createddate;
            } else {
                $ope['createdDate'] = date($this->language->get('date_format'), strtotime($operation->createddate));
            }

            $ope['action'] = $operation->action;

            $operationAmount = $this->model_extension_payment_bambora_online_checkout->convertPriceFromMinorunits($operation->amount, $operation->currency->minorunits, $decimalPoint, $thousandSeparator);
            $ope['amount'] =  "{$operation->currency->code} {$operationAmount}";

            if (array_key_exists('eci', $operation) && isset($operation->eci)) {
                $ope['eci'] = $operation->eci->value;
            } else {
                $ope['eci'] = "-";
            }

            $result[] = $ope;
            if (array_key_exists('transactionoperations', $operation) && count($operation->transactionoperations) > 0) {
                $result = array_merge($result, $this->createTransactionOperations($operation->transactionoperations, $decimalPoint, $thousandSeparator));
            }
        }

        return $result;
    }

    /**
     * Set the first letter to uppercase
     *
     * @param string $status
     * @return string
     */
    protected function checkoutStatus($status)
    {
        if (!isset($status)) {
            return "";
        }
        $firstLetter = substr($status, 0, 1);
        $firstLetterToUpper = strtoupper($firstLetter);
        $result = str_replace($firstLetter, $firstLetterToUpper, $status);
        return $result;
    }

    /**
     * Capture a payment
     */
    public function capture()
    {
        $json = array();
        try {
            $this->load->model('extension/payment/'.$this->module_name);
            $postParams = $this->request->post;
            $transactionId = $postParams['transactionId'];
            $currencyCode = $postParams['currencyCode'];
            $minorunits = $this->model_extension_payment_bambora_online_checkout->getCurrencyMinorunits($currencyCode);
            $rawAmount = $postParams['captureAmount'];
            $sanitizedAmount = str_replace(',', '.', $rawAmount);
            $amount = (float)$sanitizedAmount;
            $amountMinorunits =  $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($amount, $minorunits);
            $captureResponse = $this->model_extension_payment_bambora_online_checkout->capture($transactionId, $amountMinorunits, $currencyCode);

            if (isset($captureResponse)) {
                $json['meta'] = $captureResponse->meta;
            } else {
                $json['meta']['result'] = false;
                $errorMessage = "Connection to Bambora Failed";
                $json['meta']['message']['merchant'] = $errorMessage;
                $this->model_extension_payment_bambora_online_checkout->bamboraLog('Capture Failed: ' . $errorMessage);
            }
        }
        catch (Exception $ex) {
            $json['meta']['result'] = false;
            $json['meta']['message']['merchant'] = $ex->getMessage();
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($ex->getMessage());
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Refund a payment
     */
    public function refund()
    {
        $json = array();
        try {
            $this->load->model('extension/payment/'.$this->module_name);
            $postParams = $this->request->post;
            $transactionId = $postParams['transactionId'];
            $currencyCode = $postParams['currencyCode'];
            $minorunits = $this->model_extension_payment_bambora_online_checkout->getCurrencyMinorunits($currencyCode);
            $rawAmount = $postParams['refundAmount'];
            $sanitizedAmount = str_replace(',', '.', $rawAmount);
            $amount = (float)$sanitizedAmount;
            $amountMinorunits =  $this->model_extension_payment_bambora_online_checkout->convertPriceToMinorunits($amount, $minorunits);
            $refundResponse = $this->model_extension_payment_bambora_online_checkout->refund($transactionId, $amountMinorunits, $currencyCode);

            if (isset($refundResponse)) {
                $json['meta'] = $refundResponse->meta;
            } else {
                $json['meta']['result'] = false;
                $errorMessage = "Connection to Bambora Failed";
                $json['meta']['message']['merchant'] = $errorMessage;
                $this->model_extension_payment_bambora_online_checkout->bamboraLog('Refund Failed: ' . $errorMessage);
            }
        }
        catch (Exception $ex) {
            $json['meta']['result'] = false;
            $json['meta']['message']['merchant'] = $ex->getMessage();
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($ex->getMessage());
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Void a payment
     */
    public function void()
    {
        $json = array();
        try {
            $this->load->model('extension/payment/'.$this->module_name);
            $postParams = $this->request->post;
            $transactionId = $postParams['transactionId'];
            $voidResponse = $this->model_extension_payment_bambora_online_checkout->void($transactionId);

            if (isset($voidResponse)) {
                $json['meta'] = $voidResponse->meta;
            } else {
                $json['meta']['result'] = false;
                $errorMessage = "Connection to Bambora Failed";
                $json['meta']['message']['merchant'] = $errorMessage;
                $this->model_extension_payment_bambora_online_checkout->bamboraLog('Void Failed: ' .$errorMessage);
            }
        }
        catch (Exception $ex) {
            $json['meta']['result'] = false;
            $json['meta']['message']['merchant'] = $ex->getMessage();
            $this->model_extension_payment_bambora_online_checkout->bamboraLog($ex->getMessage());
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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
