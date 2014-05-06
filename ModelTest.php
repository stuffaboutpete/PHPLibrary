<?php

namespace Suburb;

require_once dirname(__FILE__) . '/Model.php';
require_once dirname(__FILE__) . '/Model/Property.php';
require_once dirname(__FILE__) . '/Exception.php';
require_once dirname(__FILE__) . '/Model/Exception.php';
require_once dirname(__FILE__) . '/Helper/ArrayType.php';

class ModelTest
extends \PHPUnit_Framework_TestCase {
	
	private $mProperty;
	private $mProperty2;
	
	public function setUp()
	{
		$this->mProperty = $this->getMockForAbstractClass(
			'Suburb\Model\Property',
			[],
			'',
			true,
			true,
			true,
			['set', 'get']
		);
		$this->mProperty2 = $this->getMockForAbstractClass(
			'Suburb\Model\Property',
			[],
			'',
			true,
			true,
			true,
			['set', 'get']
		);
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mProperty = null;
		$this->mProperty2 = null;
		parent::tearDown();
	}
	
	public function testModelCanBeInstantiated()
	{
		$model = new Model(['testProperty' => $this->mProperty]);
		$this->assertInstanceOf('Suburb\Model', $model);
	}
	
	public function testModelRequiresNonEmptyArrayOfProperties()
	{
		$this->setExpectedException('\Suburb\Model\Exception');
		$model = new Model([]);
	}
	
	public function testArrayOfPropertiesMustBeAssociative()
	{
		$this->setExpectedException('\Suburb\Model\Exception');
		$model = new Model([$this->mProperty]);
	}
	
	public function testModelAcceptsPropertyWhichCanBeSet()
	{
		$this->mProperty
			->expects($this->at(1)) // Zero-th call will pass default value of null
			->method('set')
			->with('Test value');
		$model = new Model(['testProperty' => $this->mProperty]);
		$model->setTestProperty('Test value');
	}
	
	public function testPropertyValueCanBeRecalled()
	{
		$this->mProperty
			->expects($this->once())
			->method('get')
			->will($this->returnValue('Test value'));
		$model = new Model(['testProperty' => $this->mProperty]);
		$this->assertEquals('Test value', $model->getTestProperty());
	}
	
	public function testExceptionIsThrownIfAttemptingToSetUnrecognisedProperty()
	{
		$this->setExpectedException('\Suburb\Model\Exception');
		$model = new Model(['testProperty' => $this->mProperty]);
		$model->setNotTestProperty('Value');
	}
	
	public function testExceptionIsThrownIfAttemptingToGetUnrecognisedProperty()
	{
		$this->setExpectedException('\Suburb\Model\Exception');
		$model = new Model(['testProperty' => $this->mProperty]);
		$model->getNotTestProperty();
	}
	
	public function testMultiplePropertiesCanBeSetAndRecalled()
	{
		$this->mProperty
			->expects($this->at(1))
			->method('set')
			->with('Value one');
		$this->mProperty
			->expects($this->once())
			->method('get')
			->will($this->returnValue('Value one'));
		$this->mProperty2
			->expects($this->at(1))
			->method('set')
			->with('Value two');
		$this->mProperty2
			->expects($this->once())
			->method('get')
			->will($this->returnValue('Value two'));
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		]);
		$model->setPropertyOne('Value one');
		$model->setPropertyTwo('Value two');
		$this->assertEquals('Value one', $model->getPropertyOne());
		$this->assertEquals('Value two', $model->getPropertyTwo());
	}
	
	public function testPropertyValuesCanBeSetAtConstruction()
	{
		$this->mProperty
			->expects($this->once())
			->method('set')
			->with('Value one');
		$this->mProperty2
			->expects($this->once())
			->method('set')
			->with('Value two');
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		],[
			'propertyOne' => 'Value one',
			'propertyTwo' => 'Value two'
		]);
	}
	
	public function testSomePropertyValuesCanBeSetAtConstruction()
	{
		$this->mProperty2
			->expects($this->once())
			->method('set')
			->with('Value');
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		],[
			'propertyTwo' => 'Value'
		]);
	}
	
	public function testMissingConstructorPropertiesAreSetWithNull()
	{
		$this->mProperty
			->expects($this->once())
			->method('set')
			->with(null);
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		],[
			'propertyTwo' => 'Value'
		]);
	}
	
	public function testUnrecognisedConstructorPropertyValuesResultInException()
	{
		$this->setExpectedException('\Suburb\Model\Exception');
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		],[
			'unknownProperty' => 'Value'
		]);
	}
	
	public function testSettingPropertyReturnsModelForChaining()
	{
		$model = new Model(['testProperty' => $this->mProperty]);
		$this->assertSame($model, $model->setTestProperty('Value'));
	}
	
	public function testListOfPropertiesCanBeRetrieved()
	{
		$model = new Model([
			'propertyOne' => $this->mProperty,
			'propertyTwo' => $this->mProperty2
		]);
		$this->assertEquals(['propertyOne', 'propertyTwo'], $model->propertyNames());
	}
	
}
