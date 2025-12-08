<?php

// Heading
$_['heading_title'] = 'Worldline Online Checkout';

// Image
$_['text_worldlineonlinecheckout'] = '<a href="https://worldline.com" target="blank"><img src="https://static.bambora.com/assets/bambora/worldline-logo.svg" alt="Worldline" title="Worldline" style="max-width:100px; max-height:46px;" /></a>';

//Text
$_['text_edit'] = 'Rediger \'Worldline Checkout\'';
$_['text_extension'] = 'Utvidelser';
$_['text_home'] = 'Hjem';
$_['text_success'] = 'Vellykket: Innstillinger for \'Worldline Checkout\' ble lagret.';
$_['text_window_state_fullscreen'] = 'Fullskjerm';
$_['text_window_state_overlay'] = 'Overliggende';
$_['text_rounding_mode_default'] = 'Standard';
$_['text_rounding_mode_always_up'] = 'Alltid opp';
$_['text_rounding_mode_always_down'] = 'Alltid ned';
$_['text_payment_info'] = 'Betalingsinformasjon';
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
$_['text_transaction_acquirer_reference'] = 'Innløser referens';
$_['text_transaction_status'] = 'Status';
$_['text_transaction_operations'] = 'Transaksjonslogg';
$_['text_transaction_operations_date'] = 'Dato';
$_['text_transaction_operations_action'] = 'Handling';
$_['text_transaction_operations_amount'] = 'Beløp';
$_['text_transaction_operations_eci'] = 'Sikkerhetsnivå';
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
$_['text_goto_worldlineonlinecheckout_admin'] = 'Gå til \'Merchant\'-administrasjonen';
$_['text_capture_info_collector'] = 'Med Walley er bare full belast mulig her. For delvis belast, bruk Merchant Portal.';
$_['text_refund_info_collector'] = 'Med Walley er bare full refunder mulig her. For delvis refunder, bruk Merchant Portal.';
$_['text_info_worldlineonlinecheckout'] = 'Bambora vil nå bli kjent som Worldline. Sammen skaper vi digitale betalinger for en verden vi stoler på.';

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
$_['entry_order_status_completed'] = 'Ordrestatus etter fullføring';
$_['entry_geo_zone'] = 'Geografisk sone';
$_['entry_sort_order'] = 'Sortering';
$_['entry_allow_low_value_exemptions'] = 'Aktiver Low Value Exemption';
$_['entry_limit_for_low_value_exemption'] = 'Maksimalt beløp for Low Value Exemption';
// Help
$_['help_status'] = 'Aktiver/Deaktiver \'Worldline Checkout\'-betalingsmodul.';
$_['help_merchant'] = 'Nummer som identifiserer deres Worldline-konto.';
$_['help_access_token'] = 'Tilgangskode for API-bruker, generert i \'Merchant\'-administrasjonen.';
$_['help_secret_token'] = 'Hemmelig kode API-bruker, generert i \'Merchant\'-administrasjonen.';
$_['help_md5'] = 'MD5-nøkkel brukes til å «stemple» data som sendes mellom OpenCart og Worldline, for å forhindre manipulering.  MD5-nøkkel er valgfri, men når den brukes må den være identisk med den i \'Merchant\'-administrasjonen.';
$_['help_window_state'] = 'Velg \'Overliggende\' for å åpne betalingsvinduet som et overliggende vindu, «flytende» over nettbutikken. Nettbutikken vil da være synlig bak betalingsvinduet. Velg \'Fullskjerm\' for å åpne betalingsvinduet i samme nettleservindu, men bruker da hele vinduet. Nettbutikken vil ikke vises med denne innstillingen.';
$_['help_window_id'] = 'Id-nummer for betalingsvinduet som skal brukes.';
$_['help_surcharge'] = 'Aktiver for å legge til transaksjonsgebyr på bestillingen.';
$_['help_instant_capture'] = 'Belast betalinger samtidig som de autoriseres. I noen land (som Norge) er dette bare lov dersom kundene mottar produktene med det samme, som f.eks. digitale produkter.';
$_['help_immediate_redirect_to_accept'] = 'Sender kundene direkte tilbake til nettbutikken etter at betalingen er fullført.';
$_['help_rounding_mode'] = 'Velg hvilken avrunding som ønskes brukt på beløp som sendes til betalingssystemet.';
$_['help_payment_method_title'] = 'Tittel på betalingsvalget, som vises til kundene i kassen m.m.';
$_['help_payment_method_update'] = 'Oppdater tittel på betalingsvalget på ordren med det som er valgt i betalingsvinduet (f.eks. til \'Worldline Online Checkout - Visa (Debit / Domestic)\').';
$_['help_order_status_completed'] = 'Velg ordrestatus for ordrer som er bekreftet av Worldline.';
$_['help_geo_zone'] = 'Angi geografiske soner som modulen skal vises for.';
$_['help_sort_order'] = 'Visningsrekkefølge for betalingsvalget.';
$_['help_allow_low_value_exemptions'] = 'Tillat deg som selger å la kunden prøve å hoppe over sterk kundeautentisering (SCA) når verdien av bestillingen er under den definerte grensen din.';
$_['help_limit_for_low_value_exemption'] = 'Ethvert beløp under dette maksimale beløpet kan hoppe over SCA hvis utstederen tillater det. Anbefalt beløp er omtrent €30 i din lokale valuta.';

//Error
$_['error_permission'] = 'Advarsel: Du har ikke rettigheter til å endre på Worldline Checkout.';
$_['error_merchant'] = '\'Merchant number\' må fylles inn.';
$_['error_access_token'] = '\'Access token\' må fylles inn.';
$_['error_secret_token'] = '\'Secret token\' må fylles inn.';
$_['error_md5'] = '\'MD5 Key\' må fylles inn.';
$_['error_get_transaction_db'] = 'Transaksjonen ble ikke funnet i databasen.';
$_['error_amount_format'] = 'Beløpet er i ugyldig format. Forsøk igjen.';
$_['error_action_base'] = 'Handlingen kunne ikke utføres.';
$_['error_module_not_loaded'] = 'Modulen må aktiveres.';
$_['error_order_id_not_supplied'] = 'Ordre-id mangler.';
$_['error_limit_for_low_value_exemption'] = 'Beløpet du anga for fritak for Low Value Exemptions er i feil format. Prøv på nytt!';
$_['error_limit_for_low_value_exemption_disabled'] = 'Du må aktivere unntak for Low Value Exemptions for å bruke dette feltet';
$_['error_sort_order'] = 'Tallet du skrev inn for sorteringsrekkefølge er i feil format. Prøv på nytt!';

//Format
$_['date_format'] = 'd.m.Y';
$_['currency_decimal_point'] = ',';
$_['currency_thousand_separator'] = '.';
