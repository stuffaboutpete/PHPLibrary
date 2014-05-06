<?php

namespace PO\Model\Property\Constraint;

require_once dirname(__FILE__) . '/../IConstraint.php';
require_once dirname(__FILE__) . '/../../../Exception.php';
require_once dirname(__FILE__) . '/Exception.php';
require_once dirname(__FILE__) . '/NotEmpty.php';

class NotEmptyTest
extends \PHPUnit_Framework_TestCase {
	
	private $constraint;
	
	public function setUp()
	{
		$this->constraint = new NotEmpty();
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->constraint = null;
		parent::tearDown();
	}
	
	public function testNonEmptyStringPasses()
	{
		$this->assertTrue($this->constraint->isValid('string'));
	}
	
	public function testEmptyStringFails()
	{
		$this->setExpectedException('\PO\Model\Property\Constraint\Exception');
		$this->constraint->isValid('');
	}
	
	public function testNonEmptyArrayPasses()
	{
		$this->assertTrue($this->constraint->isValid(['value']));
	}
	
	public function testEmptyArrayFails()
	{
		$this->setExpectedException('\PO\Model\Property\Constraint\Exception');
		$this->constraint->isValid([]);
	}
	
	public function testArrayOfNullsFails()
	{
		$this->setExpectedException('\PO\Model\Property\Constraint\Exception');
		$this->constraint->isValid([null, null]);
	}
	
	public function testNonEmptyAssociativeArrayPassed()
	{
		$this->assertTrue($this->constraint->isValid(['key' => 'value']));
	}
	
	public function testEmptyAssociativeArrayFails()
	{
		$this->setExpectedException('\PO\Model\Property\Constraint\Exception');
		$this->constraint->isValid(['key' => null]);
	}
	
}
