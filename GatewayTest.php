<?php

namespace PO;

require_once dirname(__FILE__) . '/Gateway.php';
require_once dirname(__FILE__) . '/Gateway/IFactory.php';
require_once dirname(__FILE__) . '/Gateway/IQueryProvider.php';
require_once dirname(__FILE__) . '/Gateway/Collection.php';
require_once dirname(__FILE__) . '/Gateway/Collection/Factory.php';
require_once dirname(__FILE__) . '/Exception.php';
require_once dirname(__FILE__) . '/Gateway/Exception.php';
require_once dirname(__FILE__) . '/Helper/ArrayType.php';

class GatewayTest
extends \PHPUnit_Framework_TestCase {
	
	private $mPdo;
	private $mPdoStatement;
	private $mFactory;
	private $mQueryProvider;
	private $mCollection;
	private $mCollectionFactory;
	private $dumpingGround;
	
	/**
	 * Todo
	 * 
	 * Ensure you can't re-register a class type
	 * Make gateway not re-look at queries (as they wont change)
	 * Allow chaining where relevant
	 * Ability to pause/unpause/flush save and delete queries
	 * Ensure fetchBySomethingInvalid triggers an exceptions
	 * Emphasise that order of parameters to fetch match the pdo query parameters
	 * Think about whether to catch pdo exceptions
	 * Handle no data when expecting single
	 */
	
	
	public function setUp()
	{
		
		$this->mPdo = $this->getMock('\PO\GatewayTestPDO');
		$this->mPdoStatement = $this->getMock('\PDOStatement');
		$this->mFactory = $this->getMock('\PO\Gateway\IFactory');
		$this->mQueryProvider = $this->getMock('\PO\Gateway\IQueryProvider');
		$this->mCollection = $this->getMockBuilder('\PO\Gateway\Collection')
			->disableOriginalConstructor()
			->getMock();
		$this->mCollectionFactory = $this->getMock('\PO\Gateway\Collection\Factory');
		$this->dumpingGround = [];
		
		$this->mFactory
			->expects($this->any())
			->method('approveClass')
			->will($this->returnValue(true));
		
		$this->mQueryProvider
			->expects($this->any())
			->method('approveClass')
			->will($this->returnValue(true));
		
		parent::setUp();
		
	}
	
	public function tearDown()
	{
		$this->mPdo = null;
		$this->mPdoStatement = null;
		$this->mFactory = null;
		$this->mQueryProvider = null;
		$this->mCollection = null;
		$this->mCollectionFactory = null;
		parent::tearDown();
	}
	
	public function testGatewayCanBeInstantiated()
	{
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$this->assertInstanceOf('PO\Gateway', $gateway);
	}
	
	public function testGatewayRequiresPdoInstance()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new Gateway(null, $this->mCollectionFactory);
	}
	
	public function testGatewayRequiresCollectionFactoryInstance()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new Gateway($this->mPdo);
	}
	
	public function testGatewayAcceptsNewTypeWithClassNameAndFactoryAndQueryProvider()
	{
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
	}
	
	public function testClassNamePassedToAddTypeMustBeValidClassName()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\InvalidClass', $this->mFactory, $this->mQueryProvider);
	}
	
	public function testFactoryIsRequiredInCallToAddType()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', null, $this->mQueryProvider);
	}
	
	public function testQueryProviderIsRequiredInCallToAddType()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory);
	}
	
	public function testFactoryMustApproveClassName()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$mFactory = $this->getMock('\PO\Gateway\IFactory');
		$mFactory
			->expects($this->once())
			->method('approveClass')
			->with('PO\GatewayTestObject')
			->will($this->returnValue(false));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('PO\GatewayTestObject', $mFactory, $this->mQueryProvider);
	}
	
	public function testQueryProviderMustApproveClassName()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$mQueryProvider = $this->getMock('\PO\Gateway\IQueryProvider');
		$mQueryProvider
			->expects($this->once())
			->method('approveClass')
			->with('PO\GatewayTestObject')
			->will($this->returnValue(false));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('PO\GatewayTestObject', $this->mFactory, $mQueryProvider);
	}
	
	public function testCallToFetchWillGetQueryAndRetrieveDataFromPdoAndPassToFactory()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->with('SQL query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key' => 'value']))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->key = 'value';
				return $object;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$this->assertEquals('value', $gateway->fetch('\PO\GatewayTestObject')->key);
	}
	
	public function testFactoryMustReturnInstanceOfNamedClass()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->will($this->returnValue(new \stdClass()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testRegisteredClassNameMustBePassedToFetch()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\stdClass');
	}
	
	public function testReturnedSingleQueryMustBeArray()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue('SQL query'));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testReturnedSingleQueryArrayMustBeAssociative()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['SQL query']));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testReturnedMultipleQueryMustBeArray()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue('SQL query'));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testReturnedMultipleQueryArrayMustBeAssociative()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['SQL query']));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testCallToFetchByWillGetQueryAndRetrieveDataFromPdoAndPassToFactory()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['example' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->with('SQL query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key' => 'value']))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->key = 'value';
				return $object;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$this->assertEquals('value', $gateway->fetchByExample('\PO\GatewayTestObject')->key);
	}
	
	public function testCallToFetchByWithUnrecognisedQueryKeyWillTriggerException()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetchByInvalid('\PO\GatewayTestObject');
	}
	
	public function testCallToFetchAllWillGetQueryAndRetrieveDataFromPdoAndReturnCollection()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->with('SQL query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue([
				['key1' => 'value 1'],
				['key2' => 'value 2']
			]));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->with(
				$this->isInstanceOf('\PO\Gateway'),
				$this->equalTo('PO\GatewayTestObject'),
				$this->equalTo([
					['key1' => 'value 1'],
					['key2' => 'value 2']
				])
			)
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
	}
	
	public function testCollectionFactoryMustReturnCollection()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->will($this->returnValue([
				['key1' => 'value 1'],
				['key2' => 'value 2']
			]));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->will($this->returnValue(new GatewayTestObject));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
	}
	
	public function testObjectCanBeCreatedByGatewaySoCollectionCanRetrieveObjects()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->with('SQL query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue([
				['key1' => 'value 1'],
				['key2' => 'value 2']
			]));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key1' => 'value 1']))
			->will($this->returnValue(new GatewayTestObject()));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->with(
				$this->isInstanceOf('\PO\Gateway'),
				$this->equalTo('PO\GatewayTestObject'),
				$this->equalTo([
					['key1' => 'value 1'],
					['key2' => 'value 2']
				])
			)
			->will($this->returnCallback(function($gateway, $className, $data){
				$gateway->getObject($className, $data[0]);
				return $this->mCollection;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
	}
	
	public function testClassTypeMustBeRegisteredWhenCallingGetObject()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->will($this->returnValue([
				['key1' => 'value 1'],
				['key2' => 'value 2']
			]));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->will($this->returnCallback(function($gateway, $className, $data){
				$gateway->getObject('\stdClass', $data[0]);
				return $this->mCollection;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
	}
	
	public function testPreparedQueryReturnedFromQueryProviderWillBePreparedWithProvidedData()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query with :parameter1, :parameter2']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->with('SQL query with :parameter1, :parameter2')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute')
			->with($this->equalTo([
				'parameter1' => 'value 1',
				'parameter2' => 'value 2'
			]))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key' => 'value']))
			->will($this->returnValue(new GatewayTestObject()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$this->assertInstanceOf(
			'\PO\GatewayTestObject',
			$gateway->fetch('\PO\GatewayTestObject', 'value 1', 'value 2')
		);
	}
	
	public function testParameterCountMustNotBeGreaterThanArgumentCountPassedToFetch()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query with :parameter1, :parameter2']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject', 'value');
	}
	
	public function testParameterCountMustNotBeLessThanArgumentCountPassedToFetch()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query with :parameter']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject', 'value 1', 'value 2');
	}
	
	public function testPreparedQueryForMultipleSelectWillBePreparedWithProvidedData()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query with :parameter']));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->with('SQL query with :parameter')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute')
			->with($this->equalTo(['parameter' => 'value']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->with($this->equalTo(\PDO::FETCH_ASSOC))
			->will($this->returnValue([
				['key1' => 'value 1'],
				['key2' => 'value 2']
			]));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->with(
				$this->isInstanceOf('\PO\Gateway'),
				$this->equalTo('PO\GatewayTestObject'),
				$this->equalTo([
					['key1' => 'value 1'],
					['key2' => 'value 2']
				])
			)
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject', 'value');
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
	}
	
	public function testUsingSameSingleQueryTwiceWillNotResultInTwoDatabaseCalls()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->atLeastOnce())
			->method('build')
			->with($this->equalTo(['key' => 'value']))
			->will($this->returnValue(new GatewayTestObject()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$object = $gateway->fetch('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\GatewayTestObject', $object);
		$this->assertSame($object, $gateway->fetch('\PO\GatewayTestObject'));
	}
	
	public function testUsingSameSingleQueryTwiceWillNotResultInTwoFactoryCalls()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->any())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->any())
			->method('fetch')
			->will($this->returnValue(['key' => 'value']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key' => 'value']))
			->will($this->returnValue(new GatewayTestObject()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$object = $gateway->fetch('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\GatewayTestObject', $object);
		$this->assertSame($object, $gateway->fetch('\PO\GatewayTestObject'));
	}
	
	public function testUsingSameMultipleQueryTwiceWillNotResultInTwoDatabaseCalls()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetchAll')
			->will($this->returnValue([['key' => 'value']]));
		$this->mCollectionFactory
			->expects($this->any())
			->method('build')
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
		$this->assertSame($collection, $gateway->fetchAll('\PO\GatewayTestObject'));
	}
	
	public function testUsingSameMultipleQueryTwiceWillNotResultInTwoCollectionFactoryCalls()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'SQL query']));
		$this->mPdo
			->expects($this->any())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->any())
			->method('fetchAll')
			->will($this->returnValue([['key' => 'value']]));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$collection = $gateway->fetchAll('\PO\GatewayTestObject');
		$this->assertInstanceOf('\PO\Gateway\Collection', $collection);
		$this->assertSame($collection, $gateway->fetchAll('\PO\GatewayTestObject'));
	}
	
	public function testMultipleDatabaseRequestsAreMadeIfPreparedStatementValuesChange()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query with :parameter']));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['parameter' => 'parameter 1']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue(['id' => 1]));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->equalTo(['parameter' => 'parameter 2']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('fetch')
			->will($this->returnValue(['id' => 2]));
		$this->mFactory
			->expects($this->at(1))
			->method('build')
			->with($this->equalTo(['id' => 1]))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->id = 1;
				return $object;
			}));
		$this->mFactory
			->expects($this->at(2))
			->method('build')
			->with($this->equalTo(['id' => 2]))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->id = 2;
				return $object;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$this->assertEquals(1, $gateway->fetch('\PO\GatewayTestObject', 'parameter 1')->id);
		$this->assertEquals(2, $gateway->fetch('\PO\GatewayTestObject', 'parameter 2')->id);
	}
	
	public function testObjectDataRetrievedFromTwoDatabaseCallsResultsInSameObjectInMemory()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([
				'first'		=> 'SQL query',
				'second'	=> 'Other SQL query'
			]));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->at(1))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdo
			->expects($this->at(2))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('fetch')
			->will($this->returnValue(['id' => 1]));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue(['id' => 1]));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['id' => 1]))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->id = 1;
				return $object;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$object = $gateway->fetchByFirst('\PO\GatewayTestObject');
		$this->assertSame($object, $gateway->fetchBySecond('\PO\GatewayTestObject'));
	}
	
	public function testMultiKeyDataRetrievedFromTwoDatabaseCallsResultsInSameObjectInMemory()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue([
				'first'		=> 'SQL query',
				'second'	=> 'Other SQL query'
			]));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->at(1))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdo
			->expects($this->at(2))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('fetch')
			->will($this->returnValue(['key1' => 1, 'key2' => 2, 'nonkey' => 3]));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue(['key1' => 1, 'key2' => 2, 'nonkey' => 4]));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key1' => 1, 'key2' => 2, 'nonkey' => 3]))
			->will($this->returnCallback(function(){
				$object = new GatewayTestObject();
				$object->key1 = 1;
				$object->key2 = 2;
				return $object;
			}));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(
			'\PO\GatewayTestObject',
			$this->mFactory,
			$this->mQueryProvider,
			['key1', 'key2']
		);
		$object = $gateway->fetchByFirst('\PO\GatewayTestObject');
		$this->assertSame($object, $gateway->fetchBySecond('\PO\GatewayTestObject'));
	}
	
	public function testCachedObjectIsPassedToCollection()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'Single SQL query']));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'All SQL query']));
		$this->mPdo
			->expects($this->at(1))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdo
			->expects($this->at(2))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('fetch')
			->will($this->returnValue(['id' => 2]));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetchAll')
			->will($this->returnValue([
				['id' => 1],
				['id' => 2]
			]));
		$this->dumpingGround['gatewayTestObject'] = new GatewayTestObject();
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['id' => 2]))
			->will($this->returnCallback(function(){
				$this->dumpingGround['gatewayTestObject']->id = 2;
				return $this->dumpingGround['gatewayTestObject'];
			}));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->with(
				$this->isInstanceOf('\PO\Gateway'),
				$this->equalTo('PO\GatewayTestObject'),
				$this->equalTo([
					['id' => 1],
					['id' => 2]
				]),
				$this->identicalTo([null, $this->dumpingGround['gatewayTestObject']])
			)
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->fetch('\PO\GatewayTestObject');
		$gateway->fetchAll('\PO\GatewayTestObject');
	}
	
	public function testMultiKeyCachedObjectIsPassedToCollection()
	{
		$this->mQueryProvider
			->expects($this->any())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'Single SQL query']));
		$this->mQueryProvider
			->expects($this->any())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue(['all' => 'All SQL query']));
		$this->mPdo
			->expects($this->at(1))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdo
			->expects($this->at(2))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('fetch')
			->will($this->returnValue(['key1' => 1, 'key2' => 2]));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetchAll')
			->will($this->returnValue([
				['key1' => 1, 'key2' => 2],
				['key1' => 2, 'key2' => 2]
			]));
		$this->dumpingGround['gatewayTestObject'] = new GatewayTestObject();
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key1' => 1, 'key2' => 2]))
			->will($this->returnCallback(function(){
				$this->dumpingGround['gatewayTestObject']->key1 = 1;
				$this->dumpingGround['gatewayTestObject']->key2 = 2;
				return $this->dumpingGround['gatewayTestObject'];
			}));
		$this->mCollectionFactory
			->expects($this->once())
			->method('build')
			->with(
				$this->isInstanceOf('\PO\Gateway'),
				$this->equalTo('PO\GatewayTestObject'),
				$this->equalTo([
					['key1' => 1, 'key2' => 2],
					['key1' => 2, 'key2' => 2]
				]),
				$this->identicalTo([$this->dumpingGround['gatewayTestObject'], null])
			)
			->will($this->returnValue($this->mCollection));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(
			'\PO\GatewayTestObject',
			$this->mFactory,
			$this->mQueryProvider,
			['key1', 'key2']
		);
		$gateway->fetch('\PO\GatewayTestObject');
		$gateway->fetchAll('\PO\GatewayTestObject');
	}
	
	public function testObjectCanBeSavedAndUpdateQueryWillBeSentToPdo()
	{
		$object = new GatewayTestObject();
		$object->id = 1;
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute')
			->with(['id' => 1]);
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save($object);
	}
	
	public function testSavedObjectMustBeOfARegisteredType()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save(new \stdClass());
	}
	
	public function testSaveSQLQueryMustIncludeINSERTKeywords()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('Invalid SQL ON DUPLICATE KEY UPDATE'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue(['id' => 1]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save(new GatewayTestObject());
	}
	
	public function testSaveSQLQueryMustIncludeONDUPLICATEKEYUPDATEKeywords()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT query'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue(['id' => 1]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save(new GatewayTestObject());
	}
	
	public function testSavedObjectIsAddedToObjectCache()
	{
		$object = new GatewayTestObject();
		$object->id = 1;
		$this->mQueryProvider
			->expects($this->at(1))
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with(['id' => 1]);
		$this->mQueryProvider
			->expects($this->at(2))
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->at(3))
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->at(2))
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->will($this->returnValue(['id' => 1]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save($object);
		$this->assertSame($object, $gateway->fetch('\PO\GatewayTestObject'));
	}
	
	public function testFactoryMustReturnArrayWhichMatchesPreparedStatementVariables()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$object = new GatewayTestObject();
		$object->id = 1;
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id, :name'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save($object);
	}
	
	public function testNestedObjectFromMethodCallCanBeSaved()
	{
		$object = $this->getMock('\stdClass', ['getSubObject']);
		$subObject = new \stdClass();
		$object->id = 1;
		$subObject->id = 2;
		$object
			->expects($this->once())
			->method('getSubObject')
			->will($this->returnValue($subObject));
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$mQueryProvider = $this->getMock('PO\Gateway\IQueryProvider');
		$mQueryProvider
			->expects($this->once())
			->method('approveClass')
			->will($this->returnValue(true));
		$mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2'));
		$mFactory = $this->getMock('PO\Gateway\IFactory');
		$mFactory
			->expects($this->once())
			->method('approveClass')
			->will($this->returnValue(true));
		$mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($subObject))
			->will($this->returnValue(['id' => 2]));
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with(['id' => 1]);
		$this->mPdo
			->expects($this->at(2))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('execute')
			->with(['id' => 2]);
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(get_class($object), $this->mFactory, $this->mQueryProvider);
		$gateway->addType('\stdClass', $mFactory, $mQueryProvider);
		$gateway->save(
			$object,
			['getSubObject']
		);
	}
	
	public function testArrayOfNestedObjectsFromMethodCallCanBeSaved()
	{
		$object = $this->getMock('\stdClass', ['getSubObjects']);
		$object->id = 1;
		$subObject1 = new \stdClass();
		$subObject1->id = 1;
		$subObject2 = new \stdClass();
		$subObject2->id = 2;
		$subObject3 = new \stdClass();
		$subObject3->id = 3;
		$object
			->expects($this->once())
			->method('getSubObjects')
			->will($this->returnValue([$subObject1, $subObject2, $subObject3]));
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$mQueryProvider = $this->getMock('PO\Gateway\IQueryProvider');
		$mQueryProvider
			->expects($this->once())
			->method('approveClass')
			->will($this->returnValue(true));
		$mQueryProvider
			->expects($this->exactly(3))
			->method('getSavePreparedStatement')
			->will($this->returnValue('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2'));
		$mFactory = $this->getMock('PO\Gateway\IFactory');
		$mFactory
			->expects($this->once())
			->method('approveClass')
			->will($this->returnValue(true));
		$mFactory
			->expects($this->at(1))
			->method('dismantle')
			->with($this->equalTo($subObject1))
			->will($this->returnValue(['id' => 1]));
		$mFactory
			->expects($this->at(2))
			->method('dismantle')
			->with($this->equalTo($subObject2))
			->will($this->returnValue(['id' => 2]));
		$mFactory
			->expects($this->at(3))
			->method('dismantle')
			->with($this->equalTo($subObject3))
			->will($this->returnValue(['id' => 3]));
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with(['id' => 1]);
		$this->mPdo
			->expects($this->at(2))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('execute')
			->with(['id' => 1]);
		$this->mPdo
			->expects($this->at(3))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with(['id' => 2]);
		$this->mPdo
			->expects($this->at(4))
			->method('prepare')
			->with('INSERT ... ON DUPLICATE KEY UPDATE ... :id 2')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('execute')
			->with(['id' => 3]);
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(get_class($object), $this->mFactory, $this->mQueryProvider);
		$gateway->addType('\stdClass', $mFactory, $mQueryProvider);
		$gateway->save(
			$object,
			['getSubObjects']
		);
	}
	
	// @todo Test multiple sub save-methods can be called
	// @todo Test sub maps can be used eg...
	// [
	//   'getSubObjects' => [
	//     'getSubSubObjects'
	//   ]
	// ]
	
	public function testObjectCanBeDeletedAndDeleteQueryWillBeSentToPdo()
	{
		$object = new GatewayTestObject();
		$object->id = 1;
		$this->mQueryProvider
			->expects($this->once())
			->method('getDeletePreparedStatement')
			->will($this->returnValue('DELETE SQL query :id'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->with($this->equalTo($object))
			->will($this->returnValue(['id' => 1]));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->with('DELETE SQL query :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute')
			->with(['id' => 1]);
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->delete($object);
	}
	
	public function testDeletedObjectMustBeOfARegisteredType()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->delete(new \stdClass());
	}
	
	public function testDeleteSQLQueryMustIncludeDELETEKeywords()
	{
		$this->setExpectedException('\PO\Gateway\Exception');
		$this->mQueryProvider
			->expects($this->once())
			->method('getDeletePreparedStatement')
			->will($this->returnValue('Invalid deeelete statement'));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->delete(new GatewayTestObject());
	}
	
	public function testQueryProviderIsPassedAllKeysWhenGettingSingleSelectStatements()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->with($this->equalTo(['key1', 'key2']))
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->will($this->returnValue(['key1' => 'value 1', 'key2' => 'value 2']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key1' => 'value 1', 'key2' => 'value 2']))
			->will($this->returnValue(new GatewayTestObject()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(
			'\PO\GatewayTestObject',
			$this->mFactory,
			$this->mQueryProvider,
			['key1', 'key2']
		);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testQueryProviderIsPassedAllKeysWhenGettingMultipleSelectStatements()
	{
		$this->mQueryProvider
			->expects($this->once())
			->method('getSingleSelectPreparedStatements')
			->will($this->returnValue(['single' => 'SQL query']));
		$this->mQueryProvider
			->expects($this->once())
			->method('getMultipleSelectPreparedStatements')
			->with($this->equalTo(['key1', 'key2']))
			->will($this->returnValue([]));
		$this->mPdo
			->expects($this->once())
			->method('query')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('fetch')
			->will($this->returnValue(['key1' => 'value 1', 'key2' => 'value 2']));
		$this->mFactory
			->expects($this->once())
			->method('build')
			->with($this->equalTo(['key1' => 'value 1', 'key2' => 'value 2']))
			->will($this->returnValue(new GatewayTestObject()));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(
			'\PO\GatewayTestObject',
			$this->mFactory,
			$this->mQueryProvider,
			['key1', 'key2']
		);
		$gateway->fetch('\PO\GatewayTestObject');
	}
	
	public function testQueryProviderIsPassedKeysAndAllPropertiesWhenGettingSaveStatement()
	{
		$object = new GatewayTestObject();
		$this->mQueryProvider
			->expects($this->once())
			->method('getSavePreparedStatement')
			->with(
				$this->equalTo(['id']),
				$this->equalTo(['id', 'property1', 'property2'])
			)
			->will($this->returnValue(
				'INSERT ... ON DUPLICATE KEY UPDATE ... :id, :property1, :property2'
			));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue([
				'id'		=> 1,
				'property1'	=> 'value 1',
				'property2'	=> 'value 2'
			]));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->will($this->returnValue($this->mPdoStatement));
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType('\PO\GatewayTestObject', $this->mFactory, $this->mQueryProvider);
		$gateway->save($object);
	}
	
	public function testQueryProviderIsPassedAllKeysWhenGettingDeleteStatement()
	{
		$object = new GatewayTestObject();
		$object->id = 1;
		$this->mQueryProvider
			->expects($this->once())
			->method('getDeletePreparedStatement')
			->with($this->equalTo(['key1', 'key2']))
			->will($this->returnValue('DELETE SQL query :key1, :key2'));
		$this->mFactory
			->expects($this->once())
			->method('dismantle')
			->will($this->returnValue(['key1' => 'value 1', 'key2' => 'value2']));
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute');
		$gateway = new Gateway($this->mPdo, $this->mCollectionFactory);
		$gateway->addType(
			'\PO\GatewayTestObject',
			$this->mFactory,
			$this->mQueryProvider,
			['key1', 'key2']
		);
		$gateway->delete($object);
	}
	
}

class GatewayTestPDO extends \PDO
{
	public function __construct(){}
}

class GatewayTestObject extends \stdClass {}
