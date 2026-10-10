<?php

class PsDataprivacyHookTest extends PsDataprivacyTestCase
{
    public function testHookReturnsRequiredPrivacyCheckboxForCurrentLanguage()
    {
        ContextHarness::getContext()->language->id = 2;
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(
            1 => 'SENTINEL-EN',
            2 => 'SENTINEL-FR',
        ));

        $module = $this->newModule();
        $fields = $module->hookAdditionalCustomerFormFields(array());

        $this->assertCount(1, $fields);
        $this->assertInstanceOf('FormField', $fields[0]);
        $this->assertSame('customer_privacy', $fields[0]->getName());
        $this->assertSame('checkbox', $fields[0]->getType());
        $this->assertTrue($fields[0]->isRequired());
        $this->assertContains('SENTINEL-FR', $fields[0]->getLabel());
        $this->assertContains('[[HOOK_LABEL:', $fields[0]->getLabel());
    }

    public function testHookPreservesRawHtmlInConfiguredMessage()
    {
        $html = '<a href="https://shop.example/privacy">policy</a>';
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(1 => $html), true);

        $module = $this->newModule();
        $fields = $module->hookAdditionalCustomerFormFields(array());

        $this->assertContains($html, $fields[0]->getLabel());
    }
}
