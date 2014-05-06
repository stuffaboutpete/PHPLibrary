<?php

namespace Suburb\Application\Bootstrap;

require_once dirname(__FILE__) . '/../IBootstrap.php';
require_once dirname(__FILE__) . '/Config.php';

class ConfigTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testConfigBootstrapCanBeInstantiated()
	{
		$configBootstrap = new Config();
		$this->assertInstanceOf('\\Suburb\\Application\\Bootstrap\\Config', $configBootstrap);
	}
	
}