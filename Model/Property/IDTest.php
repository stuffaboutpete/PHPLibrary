<?php

namespace Suburb\Model\Property;

require_once dirname(__FILE__) . '/../Property.php';
require_once dirname(__FILE__) . '/Integer.php';
require_once dirname(__FILE__) . '/ID.php';

class IDTest
extends \PHPUnit_Framework_TestCase {
	
	public function testPropertyCanBeInstantiated()
	{
		$property = new ID();
		$this->assertInstanceOf('Suburb\\Model\\Property\\ID', $property);
	}
	
	public function testNegativeIntegerThrowsInteger()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new ID();
		$property->set(-10);
	}
	
	public function testZeroThrowsInteger()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new ID();
		$property->set(0);
	}
	
	public function testPositiveIntegerValueIsUnchanged()
	{
		$property = new ID();
		$property->set(10);
		$this->assertEquals(10, $property->get());
	}
	
}