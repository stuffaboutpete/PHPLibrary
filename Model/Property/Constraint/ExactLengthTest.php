<?php

namespace Suburb\Model\Property;

require_once dirname(__FILE__) . '/../Property.php';
require_once dirname(__FILE__) . '/Boolean.php';

class BooleanTest
extends \PHPUnit_Framework_TestCase {
	
	public function testPropertyCanBeInstantiated()
	{
		$property = new Boolean();
		$this->assertInstanceOf('Suburb\\Model\\Property\\Boolean', $property);
	}
	
	public function testTrueCanBeSetAndRetrieved()
	{
		$property = new Boolean();
		$property->set(true);
		$this->assertTrue($property->get());
	}
	
	public function testFalseCanBeSetAndRetrieved()
	{
		$property = new Boolean();
		$property->set(false);
		$this->assertFalse($property->get());
	}
	
	public function testTruthyValuesAreConvertedToTrue()
	{
		$property = new Boolean();
		$property->set(1);
		$this->assertTrue($property->get());
		$this->assertTrue(is_bool($property->get()));
		$property->set(['value']);
		$this->assertTrue($property->get());
		$this->assertTrue(is_bool($property->get()));
		$property->set(new \stdClass());
		$this->assertTrue($property->get());
		$this->assertTrue(is_bool($property->get()));
	}
	
	public function testFalseyValuesAreConvertedToFalse()
	{
		$property = new Boolean();
		$property->set(0);
		$this->assertFalse($property->get());
		$this->assertTrue(is_bool($property->get()));
		$property->set([]);
		$this->assertFalse($property->get());
		$this->assertTrue(is_bool($property->get()));
	}
	
}