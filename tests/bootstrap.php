<?php

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.2.0');
}

require_once __DIR__ . '/support/PrestaShopHarness.php';
require_once __DIR__ . '/PsDataprivacyTestCase.php';

require_once dirname(__DIR__) . '/ps_dataprivacy.php';
require_once dirname(__DIR__) . '/upgrade/upgrade-2.1.0.php';
