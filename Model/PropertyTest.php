<?php

namespace PO\Model;

require_once dirname(__FILE__) . '/Property.php';
require_once dirname(__FILE__) . '/Property/IConstraint.php';
require_once dirname(__FILE__) . '/../Exception.php';
require_once dirname(__FILE__) . '/Property/Exception.php';

class PropertyTest
extends \PHPUnit_Framework_TestCase {
	
	private $mProperty;
	private $mConstraint;
	private $mConstraint2;
	private $mConstraint3;
	
	public function setUp()
	{
		$this->mProperty = $this->getMockForAbstractClass('PO\Model\Property');
		$this->mConstraint = $this->getMock('PO\Model\Property\IConstraint');
		$this->mConstraint2 = $this->getMock('PO\Model\Property\IConstraint');
		$this->mConstraint3 = $this->getMock('PO\Model\Property\IConstraint');
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mProperty = null;
		$this->mConstraint = null;
		$this->mConstraint2 = null;
		$this->mConstraint3 = null;
		parent::tearDown();
	}
	
	public function testSettingPropertyPassesValueToEditInputMethod()
	{
		$this->mProperty
			->expects($this->once())
			->method('editInput')
			->with('Test Value');
		$this->mProperty->set('Test Value');
	}
	
	public function testGettingPropertyPassesAlteredValueToEditOutputMethod()
	{
		$this->mProperty
			->expects($this->once())
			->method('editInput')
			->will($this->returnValue('Altered Value'));
		$this->mProperty
			->expects($this->once())
			->method('editOutput')
			->with('Altered Value');
		$this->mProperty->set('Original Value');
		$this->mProperty->get();
	}
	
	public function testPropertyCanBeSetAndRetrieved()
	{
		$this->mProperty
			->expects($this->once())
			->method('editInput')
			->will($this->returnArgument(0));
		$this->mProperty
			->expects($this->once())
			->method('editOutput')
			->will($this->returnArgument(0));
		$this->mProperty->set('Test Value');
		$this->assertSame('Test Value', $this->mProperty->get());
	}
	
	public function testPropertyAcceptsSingleConstraint()
	{
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint]]
		);
	}
	
	public function testOnlyInstancesOfIConstraintAreAllowedAsConstraints()
	{
		$this->setExpectedException('\PO\Model\Property\Exception');
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[new \stdClass]]
		);
	}
	
	public function testConstraintIsPassedValueWhenSetting()
	{
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->with('Value')
			->will($this->returnValue(true));
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint]]
		);
		$mProperty->set('Value');
	}
	
	public function testPropertyCanBeSetWhenValueDoesMeetConstraint()
	{
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint]]
		);
		$mProperty->expects($this->once())
			->method('editInput')
			->will($this->returnArgument(0));
		$mProperty->expects($this->once())
			->method('editOutput')
			->will($this->returnArgument(0));
		$mProperty->set('Valid Value');
		$this->assertSame('Valid Value', $mProperty->get());
	}
	
	public function testPropertyExpectsConstraintToThrowExceptionWhenValueDoesNotMeetConstraint()
	{
		$this->setExpectedException('\PO\Model\Property\Exception');
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(false));
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint]]
		);
		$mProperty->set('Invalid Value');
	}
	
	public function testPropertyAcceptsMultipleConstraints()
	{
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint, $this->mConstraint2, $this->mConstraint3]]
		);
	}
	
	public function testAllConstraintsArePassedValueWhenSetting()
	{
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->with('Value')
			->will($this->returnValue(true));
		$this->mConstraint2
			->expects($this->once())
			->method('isValid')
			->with('Value')
			->will($this->returnValue(true));
		$this->mConstraint3
			->expects($this->once())
			->method('isValid')
			->with('Value')
			->will($this->returnValue(true));
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint, $this->mConstraint2, $this->mConstraint3]]
		);
		$mProperty->set('Value');
	}
	
	public function testPropertyCanBeSetWhenValueMeetsAllConstraints()
	{
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$this->mConstraint2
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$this->mConstraint3
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint, $this->mConstraint2, $this->mConstraint3]]
		);
		$mProperty->expects($this->once())
			->method('editInput')
			->will($this->returnArgument(0));
		$mProperty->expects($this->once())
			->method('editOutput')
			->will($this->returnArgument(0));
		$mProperty->set('Valid Value');
		$this->assertSame('Valid Value', $mProperty->get());
	}
	
	public function testPropertyThrowsExceptionWhenValueDoesNotMeetAConstraint()
	{
		$this->setExpectedException('\PO\Model\Property\Exception');
		$this->mConstraint
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$this->mConstraint2
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(true));
		$this->mConstraint3
			->expects($this->once())
			->method('isValid')
			->will($this->returnValue(false));
		$mProperty = $this->getMockForAbstractClass(
				'PO\Model\Property',
				[[$this->mConstraint, $this->mConstraint2, $this->mConstraint3]]
		);
		$mProperty->set('Invalid Value');
	}
	
	public function testPropertyCanBeSetToNullByDefault()
	{
		$this->mProperty->set(null);
	}
	
	public function testValidNullIsNotPassedToConstraints()
	{
		$this->mConstraint
			->expects($this->never())
			->method('isValid');
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[[$this->mConstraint]]
		);
		$mProperty->set(null);
	}
	
	public function testPropertyCannotBeSetToNullIfSpecified()
	{
		$this->setExpectedException('\PO\Model\Property\Exception');
		$mProperty = $this->getMockForAbstractClass(
			'PO\Model\Property',
			[null, false]
		);
		$mProperty->set(null);
	}
	
}
