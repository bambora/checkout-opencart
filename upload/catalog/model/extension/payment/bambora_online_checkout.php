<?php

/**
 * bambora_online_checkout short summary.
 *
 * bambora_online_checkout description.
 *
 * @version 1.0
 * @author al0830228
 */
class ModelExtensionPaymentBamboraOnlineCheckout extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/bambora_online_checkout');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('bambora_online_checkout_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('bambora_online_checkout_total') > 0 && $this->config->get('bambora_online_checkout_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('bambora_online_checkout_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'bambora_online_checkout',
				'title'      => $this->config->get('bambora_online_checkout_payment_name'),
				'terms'      => '',
				'sort_order' => $this->config->get('bambora_online_checkout_sort_order')
			);
		}

		return $method_data;
    }


}