<?php

namespace PO\Gateway\Factory;

require_once dirname(__FILE__) . '/../IFactory.php';
require_once dirname(__FILE__) . '/Model.php';
require_once dirname(__FILE__) . '/Model/IBuildMapContributor.php';
require_once dirname(__FILE__) . '/Model/IDismantleContributor.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/Model/Exception.php';
require_once dirname(__FILE__) . '/../../Helper/ArrayType.php';
require_once dirname(__FILE__) . '/../../Helper/StringType.php';

class ModelTest
extends \PHPUnit_Framework_TestCase {
	
	private $mObject;
	
	public function setUp()
	{
		$this->mBuildMapContributor = $this->getMock(
			'\PO\Gateway\Factory\Model\IBuildMapContributor'
		);
		$this->mDismantleContributor = $this->getMock(
			'\PO\Gateway\Factory\Model\IDismantleContributor'
		);
		$this->mObject = $this->getMock('\PO\Gateway\Factory\ModelTestObject');
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mBuildMapContributor = null;
		$this->mDismantleContributor = null;
		$this->mObject = null;
		parent::tearDown();
	}
	
	// @todo Should only work with instances of PO\Model but there are issues testing that
	
	public function testModelFactoryCanBeInstantiated()
	{
		$factory = new Model('\PO\Gateway\Factory\ModelTestObject');
		$this->assertInstanceOf('\PO\Gateway\Factory\Model', $factory);
	}
	
	public function testFactoryRequiresClassName()
	{
		;
	}
	
	public function testDataIsPassedToObjectAsItIsCreated()
	{
		$factory = new Model('\PO\Gateway\Factory\ModelTestObject');
		$object = $factory->build(['key' => 'value']);
		$this->assertEquals(['key' => 'value'], $object->data);
	}
	
	public function testProvidedBuildMapContributorIsAccessedOnBuild()
	{
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap');
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$factory->build(['id' => 1]);
	}
	
	public function testBuildMapMustReturnArrayOrNull()
	{
		$this->setExpectedException('\PO\Gateway\Factory\Model\Exception');
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue('string'));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$factory->build(['id' => 1]);
	}
	
	public function testBuildMapMustReturnAssociativeArray()
	{
		$this->setExpectedException('\PO\Gateway\Factory\Model\Exception');
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue(['one', 'two']));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$factory->build(['id' => 1]);
	}
	
	public function testMappedKeysAreReplacedInOriginalData()
	{
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue(['originalKey' => 'newKey']));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$object = $factory->build(['originalKey' => 'value']);
		$this->assertEquals(['newKey' => 'value'], $object->data);
	}
	
	public function testMultipleBuildMapContributorsCanBeSupplied()
	{
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue(['originalKeyOne' => 'newKeyOne']));
		$mBuildMapContributor2 = $this->getMock(
			'\PO\Gateway\Factory\Model\IBuildMapContributor'
		);
		$mBuildMapContributor2
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue(['originalKeyTwo' => 'newKeyTwo']));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor, $mBuildMapContributor2]
		);
		$object = $factory->build(['originalKeyOne' => 'value', 'originalKeyTwo' => 'value']);
		$this->assertEquals(['newKeyOne' => 'value', 'newKeyTwo' => 'value'], $object->data);
	}
	
	public function testDuplicateTargetKeysAreAllowed()
	{
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue([
				'originalKey'		=> 'newKey',
				'otherOriginalKey'	=> 'newKey'
			]));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$object = $factory->build(['otherOriginalKey' => 'value']);
		$this->assertEquals(['newKey' => 'value'], $object->data);
	}
	
	public function testExceptionIsThrownIfDuplicateTargetKeysAreUsed()
	{
		$this->setExpectedException('\PO\Gateway\Factory\Model\Exception');
		$this->mBuildMapContributor
			->expects($this->once())
			->method('getMap')
			->will($this->returnValue([
				'originalKey'		=> 'newKey',
				'otherOriginalKey'	=> 'newKey'
			]));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$object = $factory->build([
			'originalKey'		=> 'value',
			'otherOriginalKey'	=> 'value'
		]);
	}
	
	public function testUnderscoredKeysAreConvertedToCamelCase()
	{
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			[$this->mBuildMapContributor]
		);
		$object = $factory->build(['under_scored_key' => 'value']);
		$this->assertEquals(['underScoredKey' => 'value'], $object->data);
	}
	
	public function testListOfPropertiesIsRetrievedOnDismantle()
	{
		$this->mObject
			->expects($this->once())
			->method('propertyNames')
			->will($this->returnValue(['propertyOne', 'propertyTwo']));
		$factory = new Model('\PO\Gateway\Factory\ModelTestObject');
		$factory->dismantle($this->mObject);
	}
	
	public function testSimplePropertiesCanBeConvertedIntoAnArrayWithUnderscoredKeys()
	{
		$this->mObject
			->expects($this->at(0))
			->method('propertyNames')
			->will($this->returnValue(['propertyOne', 'propertyTwo']));
		$this->mObject
			->expects($this->at(1))
			->method('getPropertyOne')
			->will($this->returnValue('value 1'));
		$this->mObject
			->expects($this->at(2))
			->method('getPropertyTwo')
			->will($this->returnValue('value 2'));
		$factory = new Model('\PO\Gateway\Factory\ModelTestObject');
		$this->assertEquals(
			['property_one' => 'value 1', 'property_two' => 'value 2'],
			$factory->dismantle($this->mObject)
		);
	}
	
	public function testComplexPropertiesAreOverWrittenByDismantleContributor()
	{
		$stdObject = new \stdClass();
		$this->mObject
			->expects($this->at(0))
			->method('propertyNames')
			->will($this->returnValue(['propertyOne', 'propertyTwo']));
		$this->mObject
			->expects($this->at(1))
			->method('getPropertyOne')
			->will($this->returnValue('value 1'));
		$this->mObject
			->expects($this->at(2))
			->method('getPropertyTwo')
			->will($this->returnValue($stdObject));
		$this->mDismantleContributor
			->expects($this->once())
			->method('dismantle')
			->with([
				'property_one' => 'value 1',
				'property_two' => $stdObject
			])
			->will($this->returnValue([
				'property_two' => 'std object derived value'
			]));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			null,
			[$this->mDismantleContributor]
		);
		$this->assertEquals(
			['property_one' => 'value 1', 'property_two' => 'std object derived value'],
			$factory->dismantle($this->mObject)
		);
	}
	
	public function testMultipleDismantleContributorsCanBeSupplied()
	{
		$stdObject = new \stdClass();
		$this->mObject
			->expects($this->at(0))
			->method('propertyNames')
			->will($this->returnValue(['propertyOne', 'propertyTwo']));
		$this->mObject
			->expects($this->at(1))
			->method('getPropertyOne')
			->will($this->returnValue($stdObject));
		$this->mObject
			->expects($this->at(2))
			->method('getPropertyTwo')
			->will($this->returnValue($stdObject));
		$this->mDismantleContributor
			->expects($this->once())
			->method('dismantle')
			->with([
				'property_one' => $stdObject,
				'property_two' => $stdObject
			])
			->will($this->returnValue([
				'property_one' => 'value 1'
			]));
		$dismantleContributor2 = $this->getMock(
			'\PO\Gateway\Factory\Model\IDismantleContributor'
		);
		$dismantleContributor2
			->expects($this->once())
			->method('dismantle')
			->with([
				'property_one' => 'value 1',
				'property_two' => $stdObject
			])
			->will($this->returnValue([
				'property_two' => 'value 2'
			]));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			null,
			[$this->mDismantleContributor, $dismantleContributor2]
		);
		$this->assertEquals(
			['property_one' => 'value 1', 'property_two' => 'value 2'],
			$factory->dismantle($this->mObject)
		);
	}
	
	public function testExceptionIsThrownIfObjectIsNotWrittenByDismantleContributor()
	{
		$this->setExpectedException('\PO\Gateway\Factory\Model\Exception');
		$this->mObject
			->expects($this->at(0))
			->method('propertyNames')
			->will($this->returnValue(['propertyOne', 'propertyTwo']));
		$this->mObject
			->expects($this->at(1))
			->method('getPropertyOne')
			->will($this->returnValue('value 1'));
		$this->mObject
			->expects($this->at(2))
			->method('getPropertyTwo')
			->will($this->returnValue(new \stdClass()));
		$this->mDismantleContributor
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue(null));
		$factory = new Model(
			'\PO\Gateway\Factory\ModelTestObject',
			null,
			[$this->mDismantleContributor]
		);
		$factory->dismantle($this->mObject);
	}
	
}

class ModelTestObject
extends \stdClass
{
	public function __construct($data = null){ $this->data = $data; }
	public function propertyNames(){}
	public function getPropertyOne(){}
	public function getPropertyTwo(){}
}
