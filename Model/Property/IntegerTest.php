<?php

namespace PO\Model\Property;

require_once dirname(__FILE__) . '/../Property.php';
require_once dirname(__FILE__) . '/Integer.php';

class IntegerTest
extends \PHPUnit_Framework_TestCase {
	
	public function testPropertyCanBeInstantiated()
	{
		$property = new Integer();
		$this->assertInstanceOf('PO\\Model\\Property\\Integer', $property);
	}
	
	public function testIntegerValueIsUnchanged()
	{
		$property = new Integer();
		$property->set(1);
		$this->assertEquals(1, $property->get());
	}
	
	public function testNumericStringIsReturnedAsInteger()
	{
		$property = new Integer();
		$property->set('1');
		$this->assertEquals(1, $property->get());
	}
	
	public function testFloatingPointNumberReturnedAsInteger()
	{
		$property = new Integer();
		$property->set(1.123);
		$this->assertEquals(1, $property->get());
	}
	
	public function testFloatingPointNumericStringReturnedAsInteger()
	{
		$property = new Integer();
		$property->set('1.123');
		$this->assertEquals(1, $property->get());
	}
	
	public function testNegativeIntegerValueIsUnchanged()
	{
		$property = new Integer();
		$property->set(-10);
		$this->assertEquals(-10, $property->get());
	}
	
	public function testNonNumericThrowsException()
	{
		$property = new Integer();
		$this->setExpectedException('\InvalidArgumentException');
		$property->set('Not a Number');
	}
	
}