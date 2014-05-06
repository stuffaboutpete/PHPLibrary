<?php

namespace Suburb;

require_once dirname(__FILE__) . '/Autoloader.php';

class AutoloaderTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testAutoloaderCanBeInstantiated()
	{
		$autoloader = new Autoloader();
		$this->assertInstanceOf('sR\\Autoloader', $autoloader);
	}
	
	// @todo Erm... The tests.
	
}