<?php

namespace PO\Gateway;

require_once dirname(__FILE__) . '/Collection.php';
require_once dirname(__FILE__) . '/../Gateway.php';
require_once dirname(__FILE__) . '/../Exception.php';
require_once dirname(__FILE__) . '/Collection/Exception.php';
require_once dirname(__FILE__) . '/../Helper/ArrayType.php';

class CollectionTest
extends \PHPUnit_Framework_TestCase {
	
	private $mGateway;
	
	public function setUp()
	{
		$this->mGateway = $this->getMockBuilder('\PO\Gateway')
			->disableOriginalConstructor()
			->getMock();
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mGateway = null;
		parent::tearDown();
	}
	
	public function testCollectionCanBeInstantiated()
	{
		$collection = new Collection($this->mGateway, '\stdClass', []);
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
	}
	
	public function testCollectionRequiresGateway()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new Collection(null, '\stdClass', []);
	}
	
	public function testCollectionRequiresClassName()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, null, []);
	}
	
	public function testClassNameMustRepresentAnExistingClass()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, '\InvalidClass', []);
	}
	
	public function testCollectionRequiresRawDataArray()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new Collection($this->mGateway, '\stdClass');
	}
	
	public function testDataMustBeAnArrayOfArrays()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, '\stdClass', ['id' => 1]);
	}
	
	public function testDataArrayMustBeAssociative()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, '\stdClass', [['id', 'value']]);
	}
	
	public function testObjectIsRequestedFromGatewayOnAccessAndReturned()
	{
		$this->mGateway
			->expects($this->at(0))
			->method('getObject')
			->with('stdClass', ['id' => 1])
			->will($this->returnCallback(function(){
				$object = new \stdClass();
				$object->id = 1;
				return $object;
			}));
		$this->mGateway
			->expects($this->at(1))
			->method('getObject')
			->with('stdClass', ['id' => 2])
			->will($this->returnCallback(function(){
				$object = new \stdClass();
				$object->id = 2;
				return $object;
			}));
		$collection = new Collection($this->mGateway, '\stdClass', [
			['id' => 1],
			['id' => 2]
		]);
		$this->assertEquals(1, $collection[0]->id);
		$this->assertEquals(2, $collection[1]->id);
	}
	
	public function testObjectCannotBeRetrievedWithInvalidIndex()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$this->mGateway
			->expects($this->never())
			->method('getObject');
		$collection = new Collection($this->mGateway, '\stdClass', [
			['id' => 1],
			['id' => 2]
		]);
		$collection[2];
	}
	
	public function testObjectIsNotRequestedFromGatewayTwice()
	{
		$this->mGateway
			->expects($this->once())
			->method('getObject')
			->with('stdClass', ['id' => 1])
			->will($this->returnCallback(function(){
				$object = new \stdClass();
				$object->id = 1;
				return $object;
			}));
		$collection = new Collection($this->mGateway, '\stdClass', [['id' => 1]]);
		$this->assertEquals(1, $collection[0]->id);
		$this->assertEquals(1, $collection[0]->id);
	}
	
	public function testObjectReturnedFromGatewayMustBeOfNominatedClass()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		$this->mGateway
			->expects($this->once())
			->method('getObject')
			->will($this->returnValue($this->mGateway));
		$collection = new Collection($this->mGateway, '\stdClass', [['id' => 1]]);
		$collection[0];
	}
	
	public function testObjectCanBeCountedWithoutCreatingObjects()
	{
		$this->mGateway
			->expects($this->never())
			->method('getObject');
		$collection = new Collection($this->mGateway, '\stdClass', [
			['id' => 1],
			['id' => 2],
			['id' => 3]
		]);
		$this->assertEquals(3, count($collection));
	}
	
	public function testCollectionEntryCannotBeChanged()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		$collection = new Collection($this->mGateway, '\stdClass', [['id' => 1]]);
		$collection[0] = 'new value';
	}
	
	public function testCollectionEntryCannotBeUnset()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		$collection = new Collection($this->mGateway, '\stdClass', [['id' => 1]]);
		unset($collection[0]);
	}
	
	public function testCollectionCanBeIterated()
	{
		$this->mGateway
			->expects($this->at(0))
			->method('getObject')
			->with('stdClass', ['id' => 1])
			->will($this->returnCallback(function(){
				$object = new \stdClass();
				$object->id = 1;
				return $object;
			}));
		$this->mGateway
			->expects($this->at(1))
			->method('getObject')
			->with('stdClass', ['id' => 2])
			->will($this->returnCallback(function(){
				$object = new \stdClass();
				$object->id = 2;
				return $object;
			}));
		$collection = new Collection($this->mGateway, '\stdClass', [
			['id' => 1],
			['id' => 2]
		]);
		$objects = [];
		foreach ($collection as $object) {
			array_push($objects, $object);
		}
		$this->assertEquals(1, $objects[0]->id);
		$this->assertEquals(2, $objects[1]->id);
	}
	
	public function testObjectCanBeSuppliedToConstructorAndIsNotRetrievedFromGateway()
	{
		$object = new \stdClass();
		$this->mGateway
			->expects($this->never())
			->method('getObject');
		$collection = new Collection($this->mGateway, '\stdClass', [['id' => 1]], [$object]);
		$this->assertSame($object, $collection[0]);
	}
	
	public function testObjectsMustBeInstanceOfSuppliedClassName()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, '\stdClass', [['id' => 1], ['id' => 2]], [
			new \stdClass(),
			$this->mGateway
		]);
	}
	
	public function testObjectArrayMustBeSameLengthAsDataArrayIfProvided()
	{
		$this->setExpectedException('\PO\Gateway\Collection\Exception');
		new Collection($this->mGateway, '\stdClass', [['id' => 1]], [
			new \stdClass(),
			new \stdClass()
		]);
	}
	
}
