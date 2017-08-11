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
    private $module_version = '0.1.0';

    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

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
    private $errorFields = array('merchant', 'access_token', 'secret_token');

    /**
     * Inits the module admin section
     */
    public function index()
    {
        $this->language->load('extension/payment/'.$this->module_name);
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->model_setting_setting->editSetting('payment_'.$this->module_name, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
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
            'help_windowid',
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
            $field = 'payment_' . $this->module_name . '_' . $field;
            if (isset($this->request->post[$field])) {
                $this->data[$field] = $this->request->post[$field];
            } else {
                $this->data[$field] = $this->config->get($field);
            }
        }

        // Check if fields with required default data is set. If not, we will populate default data to them.
        foreach ($defaultValues as $field => $default_value) {
            $field = 'payment_' . $this->module_name . '_' . $field;
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
            $this->errors['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->errorFields as $error) {
            if (!$this->request->post['payment_'.$this->module_name . '_' . $error]) {
                $this->errors[$error] = $this->language->get('error_' . $error);
            }
        }

        return !$this->errors;
    }
}
