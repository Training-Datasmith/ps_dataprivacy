<?php

class UpgradeModule210Test extends PsDataprivacyTestCase
{
    public function testUpgradeUnregistersLegacySubmitHook()
    {
        $module = $this->newModule();
        ModuleHarness::$hooks['ps_dataprivacy'] = array(
            'additionalCustomerFormFields',
            'actionSubmitAccountBefore',
        );

        $this->assertTrue(upgrade_module_2_1_0($module));
        $this->assertSame(
            array('additionalCustomerFormFields'),
            ModuleHarness::$hooks['ps_dataprivacy']
        );
    }

    public function testUpgradeReturnsFalseWhenUnregisterFails()
    {
        ModuleHarness::$failUnregisterHook = true;
        $module = $this->newModule();
        ModuleHarness::$hooks['ps_dataprivacy'] = array(
            'additionalCustomerFormFields',
            'actionSubmitAccountBefore',
        );

        $this->assertFalse(upgrade_module_2_1_0($module));
        $this->assertSame(
            array('additionalCustomerFormFields', 'actionSubmitAccountBefore'),
            ModuleHarness::$hooks['ps_dataprivacy']
        );
    }
}
