<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_dataprivacy PrestaShop module.
 *
 * ps_dataprivacy adds a GDPR/data privacy consent checkbox to registration
 * and contact forms. It hooks into the displayGDPRConsent hook and validates
 * that customers explicitly consent before submitting personal data.
 *
 * This file documents common usage patterns.
 */

// --- Hook: displayGDPRConsent ---
// The module renders a consent checkbox on forms that collect personal data.
// This hook is called by other modules (ps_emailsubscription, registration, etc.)
// to inject the consent requirement.
//
// Other modules invoke it like this to get the consent block:
//
// $gdprBlock = Hook::exec(
//     'displayGDPRConsent',
//     ['id_module' => $this->id],
//     null,
//     true,
// );

// --- Back Office configuration ---
// Modules > Data Privacy:
//   - Consent message text (configurable per language, supports HTML)
//   - Link to Privacy Policy page
//   - Choose which forms require consent (registration, newsletter, contact)

// --- Reading consent message programmatically ---
// $idLang    = (int) Context::getContext()->language->id;
// $message   = Configuration::get('PS_DATA_PRIVACY_CONSENT_MESSAGE', $idLang);
// $policyUrl = Context::getContext()->link->getCMSLink(
//     Configuration::get('PS_DATA_PRIVACY_CMS_PAGE')
// );

// --- Hook: actionCustomerAccountAdd ---
// Validate that GDPR consent was given during registration:
//
// Hook::register('actionCustomerAccountAdd', 'MyModule', 'onRegistration');
//
// public function onRegistration(array $params): void
// {
//     if (empty($_POST['customer_privacy'])) {
//         // Consent not given — log or flag the account
//     }
// }

// --- Template override ---
// themes/{theme}/modules/ps_dataprivacy/views/templates/hook/gdpr_consent.tpl
