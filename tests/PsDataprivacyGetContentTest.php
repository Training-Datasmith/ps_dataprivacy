<?php

class PsDataprivacyGetContentTest extends PsDataprivacyTestCase
{
    public function testGetContentWithoutSubmitLeavesConfiguration()
    {
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(1 => 'STORED'));
        $module = $this->newModule();

        $output = $module->getContent();

        $this->assertNotContains(PsDataprivacyHarness::CONFIRMATION_MARKER, $output);
        $this->assertSame('STORED', Configuration::get('CUSTPRIV_MSG_AUTH', 1));
    }

    public function testGetContentSavesEachLanguageAndKeepsHtmlLink()
    {
        $_POST['submitCustPrivMess'] = '1';
        $_POST['CUSTPRIV_MSG_AUTH_1'] = 'English <a href="https://shop.example/privacy">policy</a>';
        $_POST['CUSTPRIV_MSG_AUTH_2'] = 'French notice';

        $module = $this->newModule();
        $output = $module->getContent();

        $this->assertContains(PsDataprivacyHarness::CONFIRMATION_MARKER, $output);
        $this->assertContains('href="https://shop.example/privacy"', Configuration::get('CUSTPRIV_MSG_AUTH', 1));
        $this->assertSame('French notice', Configuration::get('CUSTPRIV_MSG_AUTH', 2));
    }

    public function testGetContentIgnoresUnrelatedPostKeys()
    {
        Configuration::updateValue('CUSTPRIV_MSG_AUTH', array(1 => 'STORED'));
        $_POST['submitCustPrivMess'] = '1';
        $_POST['OTHER'] = 'noise';
        $_POST['CUSTPRIV_MSG_AUTH'] = 'no-lang-suffix';

        $module = $this->newModule();
        $module->getContent();

        $this->assertSame('STORED', Configuration::get('CUSTPRIV_MSG_AUTH', 1));
        $this->assertFalse(Configuration::get('CUSTPRIV_MSG_AUTH', 9));
    }
}
