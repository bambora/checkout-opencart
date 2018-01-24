<?php
/**
 * @package     Bambora Online Checkout for OpenCart
 * @author      Bambora Online (https://bambora.com)
 * @copyright   Copyright (C) 2017-2018 Bambora Online. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE.txt
 */

// Heading
$_['heading_title'] = 'Bambora Online Checkout';

// Image
$_['text_bambora_online_checkout'] = '<a href="https://bambora.com" target="blank"><img src="https://d3r1pwhfz7unl9.cloudfront.net/bambora/bambora-logo.svg" alt="Bambora" title="Bambora" style="max-width:100px; max-height:46px;" /></a>';

//Text
$_['text_edit'] = 'Edit Bambora Online Checkout';
$_['text_extension'] = 'Extensions';
$_['text_home'] = 'Home';
$_['text_success'] = 'Success: You have modified Bambora Online Checkout settings!';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_window_state_fullscreen'] = 'Full Screen';
$_['text_window_state_overlay'] = 'Overlay';
$_['text_rounding_mode_default'] = 'Default';
$_['text_rounding_mode_always_up'] = 'Always up';
$_['text_rounding_mode_always_down'] = 'Always down';
$_['text_payment_info']    = 'Payment information';
$_['text_all_zones'] = 'All Zones';
$_['text_transaction_id'] = 'Transaction ID';
$_['text_transaction_authorized'] = 'Amount';
$_['text_transaction_date'] = 'Transaction date';
$_['text_transaction_payment_type'] = 'Payment type';
$_['text_transaction_card_number'] = 'Card number';
$_['text_transaction_surcharge_fee'] = 'Surcharge fee';
$_['text_transaction_captured'] = 'Captured';
$_['text_transaction_refunded'] = 'Refunded';
$_['text_transaction_acquirer'] = 'Acquirer';
$_['text_transaction_status'] = 'Status';
$_['text_transaction_operations'] = 'Transaction Operations';
$_['text_transaction_operations_date'] = 'Date';
$_['text_transaction_operations_action'] = 'Action';
$_['text_transaction_operations_amount'] = 'Amount';
$_['text_transaction_operations_eci'] = 'ECI';
$_['text_transaction_operations_id'] = 'Operation ID';
$_['text_transaction_operations_parent_id'] = 'Parent Operation ID';
$_['text_btn_capture'] = 'Capture';
$_['text_btn_refund'] = 'Refund';
$_['text_btn_void'] = 'Void';
$_['text_tooltip'] = 'Example: 1234.56';
$_['text_capture_payment_header'] = 'Capture payment?';
$_['text_capture_payment_body'] = 'Are you sure you want to capture';
$_['text_refund_payment_header'] = 'Refund payment?';
$_['text_refund_payment_body'] = 'Are you sure you want to refund';
$_['text_void_payment_header'] = 'Void payment?';
$_['text_void_payment_body'] = 'Are you sure you want to void the payment';
$_['text_no'] = 'No';
$_['text_yes'] = 'Yes';
$_['text_goto_bambora_admin'] = 'Go to Bambora Online Merchant Administration';

//Entry
$_['entry_status'] = 'Status';
$_['entry_merchant'] = 'Merchant number';
$_['entry_access_token'] = 'Access token';
$_['entry_secret_token'] = 'Secret token';
$_['entry_md5'] = 'MD5 Key';
$_['entry_window_state'] = 'Payment window state';
$_['entry_window_id'] = 'Payment window id';
$_['entry_surcharge'] = 'Surcharge fee';
$_['entry_instant_capture'] = 'Instant capture';
$_['entry_immediate_redirect_to_accept'] = 'Immediate redirect to accept page';
$_['entry_rounding_mode'] = 'Rounding mode';
$_['entry_payment_method_title'] = 'Payment method title';
$_['entry_total'] = 'Total';
$_['entry_order_status_completed'] = 'Order status after completion';
$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_sort_order'] = 'Sort Order';

// Help
$_['help_status'] = 'Enable / Disable the Bambora Online Checkout payment gateway';
$_['help_merchant'] = 'The number identifying your Bambora merchant account.';
$_['help_access_token'] = 'The Access token for the API user received from the Bambora administration.';
$_['help_secret_token'] = 'The Secret token for the API user received from the Bambora administration.';
$_['help_md5'] = 'The MD5 key is used to stamp data sent between OpenCart and Bambora to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.';
$_['help_window_state'] = 'Set to Overlay for the Payment Window to open as a overlayed window on top of the store. The store will be visible behind the payment window. Set to Full Screen to open the payment window in the same window but in full-screen. The store will not be visible in this setting.';
$_['help_window_id'] = 'The ID of the payment window to use.';
$_['help_surcharge'] = 'Enable to add surcharge to the order';
$_['help_instant_capture'] = 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.';
$_['help_immediate_redirect_to_accept'] = 'Immediately redirect your customer back to you shop after the payment completed.';
$_['help_rounding_mode'] = 'Please select how you want the rounding of the amount sendt to the payment system';
$_['help_payment_method_title'] = 'The title of the payment method displayed to the customers.';
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';
$_['help_order_status_completed'] = 'Choose order state on paid orders.';
$_['help_geo_zone'] = 'Define the geographic zones the payment module is displayed';
$_['help_sort_order'] = 'The displayed order of the payment method';

//Error
$_['error_permission'] = 'Warning You do not have permission to modify Bambora Online Checkout!';
$_['error_merchant'] = 'The Merchant number is required!';
$_['error_access_token'] = 'The Access token is required!';
$_['error_secret_token'] = 'The Secret token is required!';
$_['error_get_transaction_db'] = 'Could not retrive the transaction from the database!';
$_['error_get_api_error'] = 'No connection to Bambora';
$_['error_amount_format'] = 'The amount you entered is in the wrong format. Please try again!';
$_['error_action_base'] = 'The action could not be perform';
$_['error_module_not_loaded'] = 'Please enable the module';
$_['error_order_id_not_supplied'] = 'Order id not supplied to method';


//Format
$_['date_format'] = 'm/d/Y';
$_['currency_decimal_point'] = '.';
$_['currency_thousand_separator'] = ',';
