<?php

// Heading
$_['heading_title'] = 'Worldline Online Checkout';

// Image
$_['text_worldlineonlinecheckout'] = '<a href="https://worldline.com" target="blank"><img src="https://static.bambora.com/assets/bambora/worldline-logo.svg" alt="Worldline" title="Worldline" style="max-width:100px; max-height:46px;" /></a>';

//Text
$_['text_edit'] = 'Rediger Worldline-betaling';
$_['text_extension'] = 'Udvidelser';
$_['text_success'] = 'Succes: Du har &aelig;ndret indstillingerne for Worldline Checkout!';
$_['text_window_state_fullscreen'] = 'Fuld sk&aelig;rm';
$_['text_window_state_overlay'] = 'Overlay';
$_['text_rounding_mode_default'] = 'Standard';
$_['text_rounding_mode_always_up'] = 'Altid op';
$_['text_rounding_mode_always_down'] = 'Altid ned';
$_['text_payment_info'] = 'Betalingsoplysninger';
$_['text_transaction_id'] = 'Transaktions-ID';
$_['text_transaction_authorized'] = 'Bel&oslashb';
$_['text_transaction_date'] = 'Betalingsdato';
$_['text_transaction_payment_type'] = 'Betalingstype';
$_['text_transaction_card_number'] = 'Kortnummer';
$_['text_transaction_surcharge_fee'] = 'Betalings gebyr';
$_['text_transaction_captured'] = 'H&aelig;vet';
$_['text_transaction_refunded'] = 'Refunderet';
$_['text_transaction_acquirer'] = 'Indl&oslashser';
$_['text_transaction_acquirer_reference'] = 'Indl&oslashser referens';
$_['text_transaction_status'] = 'Status';
$_['text_transaction_operations'] = 'Transaktions operationer';
$_['text_transaction_operations_date'] = 'Dato';
$_['text_transaction_operations_action'] = 'Handling';
$_['text_transaction_operations_amount'] = 'Bel&oslashb';
$_['text_transaction_operations_eci'] = 'Sikkerhedsniveau';
$_['text_btn_capture'] = 'H&aelig;v';
$_['text_btn_refund'] = 'Krediter';
$_['text_btn_void'] = 'Annuller';
$_['text_capture_payment_header'] = 'H&aelig;v betalingen?';
$_['text_capture_payment_body'] = 'Er du sikker p&aring; at du vil h&aelig;ve';
$_['text_refund_payment_header'] = 'Krediter betalingen?';
$_['text_refund_payment_body'] = 'Er du sikker p&aring; at du vil kreditere';
$_['text_void_payment_header'] = 'Annuller betalingen?';
$_['text_void_payment_body'] = 'Er du sikker p&aring; at du vil annullere betalingen';
$_['text_no'] = 'Nej';
$_['text_yes'] = 'Ja';
$_['text_tooltip'] = 'Eksempel: 1234.56';
$_['text_goto_worldlineonlinecheckout_admin'] = 'G&aring; til Merchant Administration';
$_['text_capture_info_collector'] = 'Med Walley er kun fuld h&aelig;vning muligt. For delvis h&aelig;vning, benyt Merchant Portal.';
$_['text_refund_info_collector'] = 'Med Walley er kun fuld kreditering mulig. For delvis kreditering, benyt Merchant Portal.';
$_['text_info_worldlineonlinecheckout'] = 'Bambora vil fremover v&aelig;re kendt som Worldline. Sammen sikrer vi tilliden til digitale betalinger.';

//Entry
$_['entry_status'] = 'Status';
$_['entry_merchant'] = 'Merchant number';
$_['entry_access_token'] = 'Access token';
$_['entry_secret_token'] = 'Secret token';
$_['entry_md5'] = 'MD5 Key';
$_['entry_window_state'] = 'Betalingsvinduets tilstand';
$_['entry_window_id'] = 'Betalingsvindue-id';
$_['entry_surcharge'] = 'Till&aelig;gsgebyr';
$_['entry_instant_capture'] = '&oslashjeblikkelig h&aelig;vning';
$_['entry_immediate_redirect_to_accept'] = '&oslashjeblikkelig omdirigering til accept sidem';
$_['entry_rounding_mode'] = 'Afrundingstilstand';
$_['entry_payment_method_title'] = 'Betalingsmetodetitel';
$_['entry_payment_method_update'] = 'Payment method update';
$_['entry_order_status_completed'] = 'Ordrestatus efter afslutning';
$_['entry_geo_zone'] = 'Geografisk zone';
$_['entry_sort_order'] = 'Sorteringsr&aelig;kkef&oslashlge';
$_['entry_allow_low_value_exemptions'] = 'Tillad Low Value Exemptions';
$_['entry_limit_for_low_value_exemption'] = 'Gr&aelig;nse for Low Value Exemptions';
// Help
$_['help_status'] = 'Aktiver/deaktiver Worldline Checkout-betalingsgatewayen';
$_['help_merchant'] = 'Merchant nummeret, der identificerer din Worldline-forhandlerkonto.';
$_['help_access_token'] = 'Access token for API brugeren fra Worldline administrationen.';
$_['help_secret_token'] = 'Secret token for API brugeren fra Worldline administrationen.';
$_['help_md5'] = 'MD5-n&oslashglen bruges til at stemple data sendt mellem OpenCart og Worldline for at forhindre manipulation.';
$_['help_window_state'] = 'Indstil til Overlay for at betalingsvinduet åbnes som et overlejret vindue oven på butikken. Butikken vil v&aelig;re synlig bag betalingsvinduet. Indstil til Fuld sk&aelig;rm for at åbne betalingsvinduet i det samme vindue, men i fuld sk&aelig;rm. Butikken vil ikke v&aelig;re synlig i denne indstilling.';
$_['help_window_id'] = 'ID\'et for det betalingsvindue, der skal bruges.';
$_['help_surcharge'] = 'Aktivér muligheden for at tilf&oslashje kort genyr til ordren';
$_['help_instant_capture'] = 'H&aelig;v betalingerne samtidig med, at de godkendes. I nogle lande er dette kun tilladt, hvis forbrugeren modtager produkterne med det samme, f.eks. digitale produkter.';
$_['help_immediate_redirect_to_accept'] = 'Omdiriger din kunde tilbage til din butik med det samme, når betalingen er gennemf&oslashrt.';
$_['help_rounding_mode'] = 'V&aelig;lg venligst, hvordan du &oslashnsker, at bel&oslashbet, der sendes til betalingssystemet, skal afrundes.';
$_['help_payment_method_title'] = 'Titlen på den betalingsmetod, der vises til kunderne.';
$_['help_payment_method_title'] = 'The title of the payment method displayed to the customers.';
$_['help_payment_method_update'] = 'Opdater betalingsmetodetitlen på ordren med den, der er valgt i betalingsvinduet (f.eks. \'Worldline Online Checkout - Visa (Debit / Indenlandsk)\').';
$_['help_order_status_completed'] = 'V&aelig;lg ordrestatus på betalte ordrer.';
$_['help_geo_zone'] = 'Definer de geografiske zoner, hvor betalingsmodulet vises';
$_['help_sort_order'] = 'Den viste r&aelig;kkef&oslashlge for betalingsmetoden';
$_['help_allow_low_value_exemptions'] = 'Giv dig som forhandler mulighed for at lade kunden fors&oslashge at springe st&aelig;rk kundegodkendelse (SCA) over, når ordrens v&aelig;rdi er under din definerede gr&aelig;nse.';
$_['help_limit_for_low_value_exemption'] = 'Ethvert bel&oslashb under dette maksimumbel&oslashb kan springe SCA over, hvis udstederen tillader det. Det anbefalede bel&oslashb er omkring €30 i din lokale valuta.';

//Error
$_['error_permission'] = 'Advarsel Du har ikke tilladelse til at &aelig;ndre Worldline Checkout!';
$_['error_merchant'] = 'Forhandlernummeret er påkr&aelig;vet!';
$_['error_access_token'] = 'Access token er påkr&aelig;vet!';
$_['error_secret_token'] = 'Secret token er påkr&aelig;vet!';
$_['error_md5'] = 'MD5 Key er påkr&aelig;vet!';
$_['error_get_transaction_db'] = 'Transaktionen kunne ikke findes i databasen';
$_['error_amount_format'] = 'Bel&oslashbet du har indtastet er forkert formateret. Pr&oslashv igen!';
$_['error_action_base'] = 'Handlingen kunne ikke udf&oslashres';
$_['error_module_not_loaded'] = 'Aktiver venligst modulet';
$_['error_order_id_not_supplied'] = 'Order id mangler';
$_['error_limit_for_low_value_exemption'] = 'Det bel&oslashb, du har indtastet for Low Value Exemptions, er i det forkerte format. Pr&oslashv igen!The amount you entered for low value exemptions is in the wrong format. Please try again!';
$_['error_limit_for_low_value_exemption_disabled'] = 'Du skal aktivere Low Value Exemptions for at bruge dette felt';
$_['error_sort_order'] = 'Det tal, du indtastede for sorteringsr&aelig;kkef&oslashlge, er i forkert format. Pr&oslashv igen!';

//Format
$_['date_format'] = 'd/m/Y';
$_['currency_decimal_point'] = ',';
$_['currency_thousand_separator'] = '.';
