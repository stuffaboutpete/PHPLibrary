<?php

namespace PO\Application\Bootstrap;

require_once dirname(__FILE__) . '/../IBootstrap.php';
require_once dirname(__FILE__) . '/Pdo.php';

class PdoTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testPdoBootstrapCanBeInstantiated()
	{
		$pdoBootstrap = new Pdo();
		$this->assertInstanceOf('\\PO\\Application\\Bootstrap\\Pdo', $pdoBootstrap);
	}
	
}