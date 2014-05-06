<?php

namespace Suburb\Application\Dispatchable\Mvc;

require_once dirname(__FILE__) . '/Controller.php';
require_once dirname(__FILE__) . '/../../../Application.php';

class ControllerTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testControllerCanBeInstantiated()
	{
		$controller = $this->getMockForAbstractClass(
			'Suburb\Application\Dispatchable\Mvc\Controller'
		);
		$this->assertInstanceOf(
			'Suburb\Application\Dispatchable\Mvc\Controller',
			$controller
		);
	}
	
	// Should this be an instance of a View?
	// Or at least should this be abstract and have a view implementation?
	
}
