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
    /**
     * @var string
     */
    private $module_name = 'bambora_online_checkout';

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

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get($this->module_name . '_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get($this->module_name . '_total') > 0 && $this->config->get($this->module_name . '_total') > $total) {
            $status = false;
        } elseif (!$this->config->get($this->module_name . '_geo_zone_id')) {
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
                'title'      => $this->config->get('payment_'.$this->module_name .'_payment_method_title'),
                'terms'      => '',
                'sort_order' => $this->config->get($this->module_name . '_sort_order')
            );
        }

        return $method_data;
    }
}
