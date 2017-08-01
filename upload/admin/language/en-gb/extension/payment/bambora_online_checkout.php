<?php
// Heading
$_['heading_title'] = 'Bambora Online Checkout';

//Text
$_['text_edit'] = 'Edit Bambora Online Checkout';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
//$_['text_all_zones'] = '';
$_['text_windowstate_fullscreen'] = 'Full Screen';
$_['text_windowstate_overlay'] = 'Overlay';
$_['text_roundingmode_default'] = 'Default';
$_['text_roundingmode_alwaysup'] = 'Always up';
$_['text_roundingmode_alwaysdown'] = 'Always down';

//Entry
$_['entry_status'] = 'Status:';
$_['entry_merchant'] = 'Merchant:';
$_['entry_accesstoken'] = 'Access token:';
$_['entry_secrettoken'] = 'Secret token:';
$_['entry_md5'] = 'MD5 Key:';
$_['entry_windowstate'] = 'Payment window state:';
$_['entry_windowid'] = 'Payment window id:';
$_['entry_surcharge'] = 'Surcharge fee:';
$_['entry_instantcapture'] = 'Instant capture:';
$_['entry_immediateredirecttoaccept'] = 'Immediate redirect to accept page:';
$_['entry_roundingmode'] = 'Rounding mode:';
$_['entry_paymentmethodtitle'] = 'Payment method title:';
$_['entry_total'] = 'Total:';
$_['entry_order_status_completed'] = 'Payment status completed:';
$_['entry_geo_zone'] = 'Geo Zone:';
$_['entry_sort_order'] = 'Sort Order:';

// Help
$_['help_status'] = 'Enable / Disable the Bambora Online Checkout payment gateway';
$_['help_merchant'] = 'The number identifying your Bambora merchant account.';
$_['help_accesstoken'] = 'The Access token for the API user received from the Bambora administration.';
$_['help_secrettoken'] = 'The Secret token for the API user received from the Bambora administration.';
$_['help_md5'] = 'The MD5 key is used to stamp data sent between Magento and Bambora to prevent it from being tampered with. The MD5 key is optional but if used here, must be the same as in the Bambora administration.';
$_['help_windowstate'] = 'Set to Overlay for the Payment Window to open as a overlayed window on top of the store. The store will be visible behind the payment window. Set to Full Screen to open the payment window in the same window but in full-screen. The store will not be visible in this setting.';
$_['help_windowid'] = 'The ID of the payment window to use.';
$_['help_surcharge'] = 'Enable to add surcharge to the order';
$_['help_instantcapture'] = 'Capture the payments at the same time they are authorized. In some countries, this is only permitted if the consumer receives the products right away Ex. digital products.';
$_['help_immediateredirecttoaccept'] = 'Immediately redirect your customer back to you shop after the payment completed.';
$_['help_roundingmode'] = 'Please select how you want the rounding of the amount sendt to the payment system';
$_['help_paymentmethodtitle'] = 'The title of the payment method displayed to the customers.';
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';
$_['help_order_status_completed'] = 'Choose order state on paid orders.';

//Error
$_['error_permissions'] = 'Warning: You do not have permission to modify Bambora Online Checkout!';
$_['error_merchant'] = 'Merchant:';
$_['error_accesstoken'] = 'Access token:';
$_['error_secrettoken'] = 'Secret token:';