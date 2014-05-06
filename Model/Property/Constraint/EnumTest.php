<?php

namespace Suburb\Model\Property\Constraint;

require_once dirname(__FILE__) . '/Positive.php';

class PositiveTest
extends \PHPUnit_Framework_TestCase {
	
	private $constraint;
	
	public function setUp()
	{
		$this->constraint = new Positive();
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->constraint = null;
		parent::tearDown();
	}
	
	public function testPositiveNumericPasses()
	{
		$this->assertTrue($this->constraint->isValid(1));
	}
	
	public function testLargePositiveNumericPasses()
	{
		$this->assertTrue($this->constraint->isValid(100));
	}
	
	public function testNegativeNumericFails()
	{
		$this->assertFalse($this->constraint->isValid(-1));
	}
	
	public function testLargeNegativeNumericFails()
	{
		$this->assertFalse($this->constraint->isValid(-100));
	}
	
	public function testZeroFails()
	{
		$this->assertFalse($this->constraint->isValid(0));
	}
	
	public function testPositiveNumericStringPasses()
	{
		$this->assertTrue($this->constraint->isValid('1'));
	}
	
	public function testNegativeNumericStringFails()
	{
		$this->assertFalse($this->constraint->isValid('-1'));
	}
	
	public function testNonNumericThrowsException()
	{
		$this->setExpectedException('\InvalidArgumentException');
		// @todo Should throw better error
		$this->assertFalse($this->constraint->isValid('Not a Number'));
	}
	
}