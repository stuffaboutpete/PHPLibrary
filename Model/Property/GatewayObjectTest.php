<?php

namespace Suburb\Model\Property;

require_once dirname(__FILE__) . '/../Property.php';
require_once dirname(__FILE__) . '/../../Model.php';
require_once dirname(__FILE__) . '/GatewayObject.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/GatewayObject/Exception.php';

class GatewayObjectTest
extends \PHPUnit_Framework_TestCase {
	
	private $mProperty;
	private $mModel;
	private $mGateway;
	
	public function setUp()
	{
		$this->mProperty = $this->getMockForAbstractClass(
			'\Suburb\Model\Property',
			[],
			'',
			true,
			true,
			true,
			['set', 'get']
		);
		$this->mModel = $this->getMockBuilder('\Suburb\Model')
			->disableOriginalConstructor()
			->getMock();
		$this->mGateway = $this->getMock('\Suburb\Gateway', ['fetch']);
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->mPropery);
		unset($this->mModel);
		unset($this->mGateway);
		parent::tearDown();
	}
	
	/**
	 * Todo
	 * 
	 * Be able to work with multiple keys
	 * Be able to work with custom gateway fetch methods
	 *   eg for both above, allow gateway method: fetchBySomething('\ClassName', 10, 2)
	 * Handle gateway returning collections
	 */
	
	public function testPropertyCanBeInstantiated()
	{
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$this->assertInstanceOf('Suburb\Model\Property\GatewayObject', $property);
	}
	
	public function testPropertyRequiresClassName()
	{
		$this->setExpectedException('\Suburb\Model\Property\GatewayObject\Exception');
		$property = new GatewayObject(
			'\InvalidClass',
			$this->mProperty,
			$this->mGateway
		);
	}
	
	public function testPropertyRequiresChildPropertyForKeyValidation()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$property = new GatewayObject(
			'\Suburb\Model',
			null,
			$this->mGateway
		);
	}
	
	public function testPropertyRequiresGatewayInstance()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty
		);
	}
	
	public function testSimpleValueCanBeSetAndItIsSetOnChildProperty()
	{
		$this->mProperty
			->expects($this->once())
			->method('set')
			->with(10)
			->will($this->returnValue($this->mProperty));
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set(10);
	}
	
	public function testExceptionThrownFromChildPropertyIsNotCaught()
	{
		$this->setExpectedException('\Exception');
		$this->mProperty
			->expects($this->once())
			->method('set')
			->will($this->throwException(new \Exception('Value not valid')));
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set('Some invalid value');
	}
	
	public function testInstanceOfNominatedClassCanBeSetAndItIsNotPassedToChildProperty()
	{
		$this->mProperty
			->expects($this->never())
			->method('set');
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set($this->mModel);
	}
	
	public function testInstanceOfNominatedClassCanBeRetrieved()
	{
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set($this->mModel);
		$this->assertSame($this->mModel, $property->get());
	}
	
	public function testExceptionIsThrownIfObjectNotOfNominatedClassIsProvided()
	{
		$this->setExpectedException('\Suburb\Model\Property\GatewayObject\Exception');
		$this->mProperty
			->expects($this->never())
			->method('set');
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set(new \DateTime());
	}
	
	public function testGatewayIsUsedToRetrieveObjectValueIfSimpleValueHasBeenSet()
	{
		$this->mProperty
			->expects($this->once())
			->method('set')
			->with(10)
			->will($this->returnValue($this->mProperty));
		$this->mProperty
			->expects($this->once())
			->method('get')
			->will($this->returnValue(10));
		$this->mGateway
			->expects($this->once())
			->method('fetch')
			->with('Suburb\Model', 10)
			->will($this->returnValue($this->mModel));
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set(10);
		$this->assertSame($this->mModel, $property->get());
	}
	
	public function testGatewayIsNotContactedTwiceIfValueHasNotChanged()
	{
		$this->mProperty
			->expects($this->any())
			->method('set')
			->with(10)
			->will($this->returnValue($this->mProperty));
		$this->mProperty
			->expects($this->any())
			->method('get')
			->will($this->returnValue(10));
		$this->mGateway
			->expects($this->once())
			->method('fetch')
			->with('Suburb\Model', 10)
			->will($this->returnValue($this->mModel));
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set(10);
		$property->get();
		$property->get();
	}
	
	public function testGatewayIsContactedAgainIfValueChanges()
	{
		$this->mProperty
			->expects($this->at(0))
			->method('set')
			->with(10)
			->will($this->returnValue($this->mProperty));
		$this->mProperty
			->expects($this->at(1))
			->method('get')
			->will($this->returnValue(10));
		$this->mProperty
			->expects($this->at(2))
			->method('set')
			->with(11)
			->will($this->returnValue($this->mProperty));
		$this->mProperty
			->expects($this->at(3))
			->method('get')
			->will($this->returnValue(11));
		$this->mGateway
			->expects($this->at(0))
			->method('fetch')
			->with('Suburb\Model', 10)
			->will($this->returnValue($this->mModel));
		$this->mGateway
			->expects($this->at(1))
			->method('fetch')
			->with('Suburb\Model', 11)
			->will($this->returnValue($this->mModel));
		$property = new GatewayObject(
			'\Suburb\Model',
			$this->mProperty,
			$this->mGateway
		);
		$property->set(10);
		$property->get();
		$property->set(11);
		$property->get();
	}
	
}
