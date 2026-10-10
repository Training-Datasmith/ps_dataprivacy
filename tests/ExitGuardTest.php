<?php

class ExitGuardTest extends PsDataprivacyTestCase
{
    public function guardScriptProvider()
    {
        $root = dirname(__DIR__);

        return array(
            array('ps_dataprivacy.php', "require " . var_export($root . '/ps_dataprivacy.php', true) . ';'),
            array('index.php', "require " . var_export($root . '/index.php', true) . ';'),
            array('tests/index.php', "require " . var_export($root . '/tests/index.php', true) . ';'),
            array('upgrade/index.php', "require " . var_export($root . '/upgrade/index.php', true) . ';'),
            array('tests/phpstan/index.php', "require " . var_export($root . '/tests/phpstan/index.php', true) . ';'),
        );
    }

    /**
     * @dataProvider guardScriptProvider
     */
    public function testGuardStopsExecutionWithoutEmittingSentinel($label, $requireLine)
    {
        $script = '<?php
' . $requireLine . '
echo ' . var_export(PsDataprivacyHarness::SENTINEL, true) . ';
';
        $result = PsDataprivacyHarness::runPhpScriptInContainer($script);

        $this->assertSame(0, $result['exit']);
        $this->assertNotContains(PsDataprivacyHarness::SENTINEL, $result['output']);
    }

    public function testModuleFileRequiresPsVersionConstant()
    {
        $path = dirname(__DIR__) . '/ps_dataprivacy.php';
        $script = '<?php
require ' . var_export($path, true) . ';
echo ' . var_export(PsDataprivacyHarness::SENTINEL, true) . ';
';
        $result = PsDataprivacyHarness::runPhpScriptInContainer($script);

        $this->assertSame(0, $result['exit']);
        $this->assertNotContains(PsDataprivacyHarness::SENTINEL, $result['output']);
    }
}
