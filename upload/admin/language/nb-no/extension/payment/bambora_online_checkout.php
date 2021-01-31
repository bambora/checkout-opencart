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

// Heading
$_['heading_title'] = 'Bambora Online Checkout';

// Image
$_['text_bambora_online_checkout'] = '<a href="https://bambora.com" target="blank"><img src="https://d3r1pwhfz7unl9.cloudfront.net/bambora/bambora-logo.svg" alt="Bambora" title="Bambora" style="max-width:100px; max-height:46px;" /></a>';

//Text
$_['text_edit'] = 'Rediger \'Bambora Online Checkout\'';
$_['text_extension'] = 'Utvidelser';
$_['text_home'] = 'Hjem';
$_['text_success'] = 'Vellykket: Innstillinger for \'Bambora Online Checkout\' ble lagret.';
$_['text_enabled'] = 'Aktivert';
$_['text_disabled'] = 'Deaktivert';
$_['text_window_state_fullscreen'] = 'Fullskjerm';
$_['text_window_state_overlay'] = 'Overliggende';
$_['text_rounding_mode_default'] = 'Standard';
$_['text_rounding_mode_always_up'] = 'Alltid opp';
$_['text_rounding_mode_always_down'] = 'Alltid ned';
$_['text_payment_info']    = 'Betalingsinformasjon';
$_['text_all_zones'] = 'Alle soner';
$_['text_transaction_id'] = 'Transaksjonsnummer';
$_['text_transaction_authorized'] = 'Beløp';
$_['text_transaction_date'] = 'Betalingsdato';
$_['text_transaction_payment_type'] = 'Betalingsmåte';
$_['text_transaction_card_number'] = 'Kortnummer';
$_['text_transaction_surcharge_fee'] = 'Transaksjonsgebyr';
$_['text_transaction_captured'] = 'Belastet';
$_['text_transaction_refunded'] = 'Refundert';
$_['text_transaction_acquirer'] = 'Innløser';
$_['text_transaction_status'] = 'Status';
$_['text_transaction_operations'] = 'Transaksjonslogg';
$_['text_transaction_operations_date'] = 'Dato';
$_['text_transaction_operations_action'] = 'Handling';
$_['text_transaction_operations_amount'] = 'Beløp';
$_['text_transaction_operations_eci'] = 'Sikkerhetsnivå';
$_['text_transaction_operations_id'] = 'Loggnummer';
$_['text_transaction_operations_parent_id'] = 'Opprinnelig loggnummer';
$_['text_btn_capture'] = 'Belast';
$_['text_btn_refund'] = 'Refunder';
$_['text_btn_void'] = 'Annuller';
$_['text_tooltip'] = 'Eksempel: 1234.56';
$_['text_capture_payment_header'] = 'Belast betalingen?';
$_['text_capture_payment_body'] = 'Er du sikker på at du vil belaste';
$_['text_refund_payment_header'] = 'Refunder betaling?';
$_['text_refund_payment_body'] = 'Er du sikker på at du vil refundere';
$_['text_void_payment_header'] = 'Annuller betalingen?';
$_['text_void_payment_body'] = 'Er du sikker på at du vil annullere betalingen';
$_['text_no'] = 'Nei';
$_['text_yes'] = 'Ja';
$_['text_goto_bambora_admin'] = 'Gå til \'Bambora Online Merchant\'-administrasjonen';
$_['text_capture_info_collector'] = 'Med Collector bank er bare full belast mulig her. For delvis belast, bruk Bambora Online Merchant Portal.';
$_['text_refund_info_collector'] = 'Med Collector bank er bare full refunder mulig her. For delvis refunder, bruk Bambora Online Merchant Portal.';

//Entry
$_['entry_status'] = 'Status';
$_['entry_merchant'] = 'Merchant number';
$_['entry_access_token'] = 'Access token';
$_['entry_secret_token'] = 'Secret token';
$_['entry_md5'] = 'MD5 Key';
$_['entry_window_state'] = 'Betalingsvinduets status';
$_['entry_window_id'] = 'Betalingsvinduets id';
$_['entry_surcharge'] = 'Transaksjonsgebyr';
$_['entry_instant_capture'] = 'Direktebelastning';
$_['entry_immediate_redirect_to_accept'] = 'Direkte viderekobling til bekreftelseside';
$_['entry_rounding_mode'] = 'Avrunding';
$_['entry_payment_method_title'] = 'Betalingsvalgets tittel';
$_['entry_payment_method_update'] = 'Oppdater betalingsvalgets tittel';
$_['entry_total'] = 'Totalt';
$_['entry_order_status_completed'] = 'Ordrestatus etter fullføring';
$_['entry_geo_zone'] = 'Geografisk sone';
$_['entry_sort_order'] = 'Sortering';

// Help
$_['help_status'] = 'Aktiver/Deaktiver \'Bambora Online Checkout\'-betalingsmodul.';
$_['help_merchant'] = 'Nummer som identifiserer deres Bambora-konto.';
$_['help_access_token'] = 'Tilgangskode for API-bruker, generert i \'Bambora Online Merchant\'-administrasjonen.';
$_['help_secret_token'] = 'Hemmelig kode API-bruker, generert i \'Bambora Online Merchant\'-administrasjonen.';
$_['help_md5'] = 'MD5-nøkkel brukes til å «stemple» data som sendes mellom OpenCart og Bambora, for å forhindre manipulering.  MD5-nøkkel er valgfri, men når den brukes må den være identisk med den i \'Bambora Online Merchant\'-administrasjonen.';
$_['help_window_state'] = 'Velg \'Overliggende\' for å åpne betalingsvinduet som et overliggende vindu, «flytende» over nettbutikken. Nettbutikken vil da være synlig bak betalingsvinduet. Velg \'Fullskjerm\' for å åpne betalingsvinduet i samme nettleservindu, men bruker da hele vinduet. Nettbutikken vil ikke vises med denne innstillingen.';
$_['help_window_id'] = 'Id-nummer for betalingsvinduet som skal brukes.';
$_['help_surcharge'] = 'Aktiver for å legge til transaksjonsgebyr på bestillingen.';
$_['help_instant_capture'] = 'Belast betalinger samtidig som de autoriseres. I noen land (som Norge) er dette bare lov dersom kundene mottar produktene med det samme, som f.eks. digitale produkter.';
$_['help_immediate_redirect_to_accept'] = 'Sender kundene direkte tilbake til nettbutikken etter at betalingen er fullført.';
$_['help_rounding_mode'] = 'Velg hvilken avrunding som ønskes brukt på beløp som sendes til betalingssystemet.';
$_['help_payment_method_title'] = 'Tittel på betalingsvalget, som vises til kundene i kassen m.m.';
$_['help_payment_method_update'] = 'Oppdater tittel på betalingsvalget på ordren med det som er valgt i betalingsvinduet (f.eks. til \'Bambora - Visa (Debit / Domestic)\'), ved å overskrive tittel som er angitt ovenfor.';
$_['help_total'] = 'Ordretotal som må innfris før betalingsvalget vises.';
$_['help_order_status_completed'] = 'Velg ordrestatus for ordrer som er bekreftet av Bambora.';
$_['help_geo_zone'] = 'Angi geografiske soner som modulen skal vises for.';
$_['help_sort_order'] = 'Visningsrekkefølge for betalingsvalget.';

//Error
$_['error_permission'] = 'Advarsel: Du har ikke rettigheter til å endre på Bambora Online Checkout.';
$_['error_merchant'] = '\'Merchant number\' må fylles inn.';
$_['error_access_token'] = '\'Access token\' må fylles inn.';
$_['error_secret_token'] = '\'Secret token\' må fylles inn.';
$_['error_get_transaction_db'] = 'Transaksjonen ble ikke funnet i databasen.';
$_['error_get_api_error'] = 'Kunne ikke koble til Bambora.';
$_['error_amount_format'] = 'Beløpet er i ugyldig format. Forsøk igjen.';
$_['error_action_base'] = 'Handlingen kunne ikke utføres.';
$_['error_module_not_loaded'] = 'Modulen må aktiveres.';
$_['error_order_id_not_supplied'] = 'Ordre-id mangler.';

//Format
$_['date_format'] = 'd.m.Y';
$_['currency_decimal_point'] = ',';
$_['currency_thousand_separator'] = '.';

