<?php

abstract class PsDataprivacyTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        PsDataprivacyHarness::reset();
    }

    protected function newModule()
    {
        return new Ps_Dataprivacy();
    }
}
