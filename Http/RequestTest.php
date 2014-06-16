<?php

namespace PO\Application;

class RequestTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testRequestCanBeInstantiated()
	{
		$request = new Request();
		$this->assertInstanceOf('\PO\Http\Request', $request);
	}
	
}