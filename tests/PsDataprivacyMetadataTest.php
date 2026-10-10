<?php

class PsDataprivacyMetadataTest extends PsDataprivacyTestCase
{
    public function testModuleNameMatchesManifest()
    {
        $module = $this->newModule();
        $xml = simplexml_load_file(dirname(__DIR__) . '/config.xml');

        $this->assertSame('ps_dataprivacy', $module->name);
        $this->assertSame('ps_dataprivacy', (string) $xml->name);
    }

    public function testModuleVersionMatchesManifest()
    {
        $module = $this->newModule();
        $xml = simplexml_load_file(dirname(__DIR__) . '/config.xml');

        $this->assertSame((string) $xml->version, $module->version);
        $this->assertNotSame('', $module->version);
    }
}
