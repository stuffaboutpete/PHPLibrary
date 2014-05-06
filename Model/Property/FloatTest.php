<?php

namespace PO\Model\Property;

require_once dirname(__FILE__) . '/../Property.php';
require_once dirname(__FILE__) . '/Float.php';

class FloatTest
extends \PHPUnit_Framework_TestCase {
	
	// @todo Support scientific notation - eg 12.2e4 should be allowable value (=12,200)
	
	public function testPropertyCanBeInstantiated()
	{
		$property = new Float(6, 2);
		$this->assertInstanceOf('PO\\Model\\Property\\Float', $property);
	}
	
	public function testFloatValueCanBeSetAndRetrieved()
	{
		$property = new Float(6, 2);
		$property->set(1.23);
		$this->assertEquals(1.23, $property->get());
	}
	
	public function testIntegerValueCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set(123);
		$this->assertEquals(123, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testNumericStringAsIntegerCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set('123');
		$this->assertEquals(123, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testNumericStringAsFloatCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set('1.23');
		$this->assertEquals(1.23, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testZeroFloatCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set(0.0);
		$this->assertEquals(0, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testZeroIntegerCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set(0);
		$this->assertEquals(0, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testZeroStringAsIntegerCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set('0');
		$this->assertEquals(0, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testZeroStringAsFloatCanBeSetAndIsReturnedAsFloat()
	{
		$property = new Float(6, 2);
		$property->set('0.0');
		$this->assertEquals(0, $property->get());
		$this->assertTrue(is_float($property->get()));
	}
	
	public function testExceptionIsThrownIfObjectIsSet()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set(new \stdClass());
	}
	
	public function testExceptionIsThrownIfArrayIsSet()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set([]);
	}
	
	public function testExceptionIsThrownIfTrueIsSet()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set(true);
	}
	
	public function testExceptionIsThrownIfFalseIsSet()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set(false);
	}
	
	public function testExceptionIsThrownIfNonNumericStringIsSet()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set('not a number');
	}
	
	public function testDecimalPlacesAreLimited()
	{
		$property = new Float(6, 2);
		$property->set(1.234);
		$this->assertEquals(1.23, $property->get());
	}
	
	public function testDecimalPlaceLimitionIsBasedOnConstructorValue()
	{
		$property = new Float(6, 1);
		$property->set(1.23);
		$this->assertEquals(1.2, $property->get());
	}
	
	public function testExceptionIsThrownIfNumberIsTooLarge()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set(1234567);
	}
	
	public function testLengthLimitationIsBasedOnConstructorValue()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$property = new Float(6, 2);
		$property->set(12345);
		$this->assertEquals(12345, $property->get());
		$property2 = new Float(4, 2);
		$property2->set(12345);
	}
	
	public function testDecimalValuesAreLostIfLengthLimitIsReached()
	{
		$property = new Float(6, 2);
		$property->set(12345.12);
		$this->assertEquals(12345.1, $property->get());
	}
	
	public function testAllDecimalValuesAreLostIfLengthLimitIsReached()
	{
		$property = new Float(4, 2);
		$property->set(1234.12);
		$this->assertEquals(1234, $property->get());
	}
	
	public function testDecimalsAreRoundedUpWhereRelevant()
	{
		$property = new Float(6, 2);
		$property->set(1234.567);
		$this->assertEquals(1234.57, $property->get());
	}
	
	public function testSetMethodReturnsSelfForChaining()
	{
		$property = new Float(6, 2);
		$this->assertSame($property, $property->set(123));
	}
	
}