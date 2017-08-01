<?php

/**
 * bambora_online_checkout short summary.
 *
 * bambora_online_checkout description.
 *
 * @version 1.0
 * @author al0830228
 */
class ControllerExtensionPaymentBamboraOnlineCheckout extends Controller
{
    /**
     * @var string
     */
    protected $module_name = '';

    /**
     * @var array
     */
    protected $data = array();

    protected $errorFields = array('permissions', 'merchant', 'accesstoken', 'secrettoken');

    public function index()
    {
        $this->module_name = 'bambora_online_checkout';
        $this->language->load('extension/payment/'.$this->module_name);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->model_setting_setting->editSetting($this->module_name, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment/' . $this->module_name, 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->initSettings();
        $this->initSettingsContent();
        $this->populateBreadcrums();
        $this->populateSettingValues();
        $this->populateErrorMessages();

        $this->response->setOutput($this->load->view('extension/payment/'.$this->module_name.'.twig', $this->data));
    }

    protected function populateSettingValues()
    {
        $fields = array(
            'status',
            'merchant',
            'accesstoken',
            'secrettoken',
            'md5',
            'windowstate',
            'windowid',
            'surcharge',
            'instantcapture',
            'immediateredirecttoaccept',
            'roundingmode',
            'paymentmethodtitle',
            'total',
            'order_status_completed',
            'geo_zone',
            'sort_order',
         );
        $defaultValues = array(
            'status' => '0',
            'windowstate' => '1',
            'windowid' => '1',
            'surcharge' => '0',
            'instantcapture' => '0',
            'immediateredirecttoaccept' => '0',
            'roundingmode' => '1',
            'paymentmethodtitle' => 'Bambora Online Checkout',
            'order_status_completed' => '5'
        );

        // Loop through configuration fields and populate them
        foreach ($fields as $field) {
            $field = $this->module_name . '_' . $field;
            if (isset($this->request->post[$field])) {
                $this->data[$field] = $this->request->post[$field];
            } else {
                $this->data[$field] = $this->config->get($field);
            }
        }

        // Check if fields with required default data is set. If not, we will populate default data to them.
        foreach ($defaultValues as $field => $default_value) {
            $field = $this->module_name . '_' . $field;
            if (!isset($this->data[$field])) {
                $this->data[$field] = $default_value;
            }
        }

    }

    protected function initSettings()
    {
        $keys = array(
            //Entry
            'entry_status',
            'entry_merchant',
            'entry_accesstoken',
            'entry_secrettoken',
            'entry_md5',
            'entry_windowstate',
            'entry_windowid',
            'entry_surcharge',
            'entry_instantcapture',
            'entry_immediateredirecttoaccept',
            'entry_roundingmode',
            'entry_paymentmethodtitle',
            'entry_total',
            'entry_order_status_completed',
            'entry_geo_zone',
            'entry_sort_order',
           //Help
            'help_status',
            'help_merchant',
            'help_accesstoken',
            'help_secrettoken',
            'help_md5',
            'help_windowstate',
            'help_windowid',
            'help_surcharge',
            'help_instantcapture',
            'help_immediateredirecttoaccept',
            'help_roundingmode',
            'help_paymentmethodtitle',
            'help_total',
            'help_order_status_completed',
            'help_geo_zone',
            'help_sort_order',
            ////Error
            //'error_merchant',
            //'error_accesstoken',
            //'error_secrettoken',
        );
        foreach ($keys as $key) {
            $this->data[$key] = $this->language->get($key);
        }
    }


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
            'text_windowstate_fullscreen',
            'text_windowstate_overlay',
            'text_roundingmode_default',
            'text_roundingmode_alwaysup',
            'text_roundingmode_alwaysdown'
        );
        foreach ($keys as $key) {
            $this->data[$key] = $this->language->get($key);
        }

        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');
    }

    protected function populateBreadcrums()
    {
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/' . $this->module_name, 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['action'] = $this->url->link('extension/payment/' . $this->module_name, 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL');
    }

    protected function populateErrorMessages()
    {
        foreach ($this->errorFields as $error) {
            if (isset($this->error[$error])) {
                $this->data['error_' . $error] = $this->error[$error];
            } else {
                $this->data['error_' . $error] = '';
            }
        }
    }


    protected function validate()
    {
        if(!$this->user->hasPermission('modify', "extension/payment/{$this->module_name}")) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->errorFields as $error) {
            if (!$this->request->post[$this->module_name . '_' . $error]) {
                $this->error[$error] = $this->language->get('error_' . $error);
            }
        }

        return !$this->error;
    }

}