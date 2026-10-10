<?php

class PsDataprivacyConfigFormTest extends PsDataprivacyTestCase
{
    public function testGetConfigFieldsValuesUsesStoredMessagesForSeededLanguages()
    {
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(
            1 => 'stored-one',
            2 => 'stored-two',
        ));

        $module = $this->newModule();
        $values = $module->getConfigFieldsValues();

        $this->assertSame('stored-one', $values['CUSTPRIV_MSG_AUTH'][1]);
        $this->assertSame('stored-two', $values['CUSTPRIV_MSG_AUTH'][2]);
    }

    public function testRenderFormExposesMultilingualTextareaContract()
    {
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(1 => 'stored-msg'));
        Configuration::updateValue('PS_LANG_DEFAULT', array(0 => '2'));
        Configuration::updateValue('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', array(0 => '1'));

        $module = $this->newModule();
        $raw = $module->renderForm();
        $this->assertContains('FORMJSON:', $raw);

        $json = substr($raw, strlen('FORMJSON:'));
        $payload = json_decode($json, true);
        $this->assertInternalType('array', $payload);
        $this->assertSame('submitCustPrivMess', $payload['submit_action']);

        $input = $payload['forms'][0]['form']['input'][0];
        $this->assertSame('textarea', $input['type']);
        $this->assertSame('CUSTPRIV_MSG_AUTH', $input['name']);
        $this->assertTrue($input['lang']);
        $this->assertTrue($input['autoload_rte']);
        $this->assertSame('stored-msg', $payload['tpl_vars']['fields_value']['CUSTPRIV_MSG_AUTH'][1]);
    }
}
