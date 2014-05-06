<?php

namespace PO\Application\Dispatchable;

require_once dirname(__FILE__) . '/../../Application.php';
require_once dirname(__FILE__) . '/../IDispatchable.php';
require_once dirname(__FILE__) . '/PreRouter.php';

class PreRouterTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testPreRouterCanBeInstantiated()
	{
		$router = new PreRouter();
		$this->assertInstanceOf('PO\Application\Dispatchable\PreRouter', $router);
	}
	
	/**
	 * Should
	 * 
	 * Accept any number of routers
	 * Accept path patterns and associated router
	 */
	
	/*$router = new PreRouter([
		'/api'				=> 'PO\\Application\\Router\\Rest',
		'/'					=> 'PO\\Application\\Router\\Mvc',
		'/api/this/that'	=> 'PO\\Application\\Router\\Mvc'
	]);*/
	
}