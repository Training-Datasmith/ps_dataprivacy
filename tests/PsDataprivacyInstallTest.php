<?php

class PsDataprivacyInstallTest extends PsDataprivacyTestCase
{
    public function testInstallRegistersHookAndSeedsActiveLanguages()
    {
        $module = $this->newModule();
        $this->assertTrue($module->install());

        foreach (Language::getLanguages(true) as $lang) {
            $value = Configuration::get('CUSTPRIV_MSG_AUTH', (int) $lang['id_lang']);
            $this->assertInternalType('string', $value);
            $this->assertContains('[[FIXTURE:', $value);
            $this->assertContains($lang['locale'], $value);
        }

        $this->assertTrue(Module::isInstalled('ps_dataprivacy'));
        $this->assertSame(
            array('additionalCustomerFormFields'),
            ModuleHarness::$hooks['ps_dataprivacy']
        );
    }

    public function testInstallReturnsFalseWhenParentInstallFails()
    {
        ModuleHarness::$failInstall = true;
        $module = $this->newModule();

        $this->assertFalse($module->install());
        $this->assertFalse(Configuration::get('CUSTPRIV_MSG_AUTH', 1));
        $this->assertFalse(isset(ModuleHarness::$hooks['ps_dataprivacy']));
    }

    public function testInstallReturnsFalseWhenHookRegistrationFails()
    {
        ModuleHarness::$failRegisterHook = true;
        $module = $this->newModule();

        $this->assertFalse($module->install());
        $this->assertFalse(Configuration::get('CUSTPRIV_MSG_AUTH', 1));
    }

    public function testInstallPreservesLegacyMessageAndUninstallsOldModule()
    {
        ModuleHarness::$installed['blockcustomerprivacy'] = true;
        $legacy = new Blockcustomerprivacy();
        ModuleHarness::$instances['blockcustomerprivacy'] = $legacy;

        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(1 => 'LEGACY-MESSAGE'));

        $module = $this->newModule();
        $this->assertTrue($module->install());

        $this->assertSame('LEGACY-MESSAGE', Configuration::get('CUSTPRIV_MSG_AUTH', 1));
        $this->assertFalse(Module::isInstalled('blockcustomerprivacy'));
        $this->assertTrue(Module::isInstalled('ps_dataprivacy'));
    }
}
