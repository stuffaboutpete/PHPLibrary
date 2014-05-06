<?php

namespace PO;

require_once dirname(__FILE__) . '/IoCContainer.php';
require_once dirname(__FILE__) . '/IoCContainer/IContainment.php';

class IoCContainerTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testContainerCanBeInstantiated()
	{
		$container = new IoCContainer();
		$this->assertInstanceOf('PO\IoCContainer', $container);
	}
	
	public function testContainerInstantiatesSimpleClass()
	{
		$container = new IoCContainer();
		$object = $container->resolve('stdClass');
		$this->assertInstanceOf('stdClass', $object);
	}
	
	public function testContainerThrowsExceptionIfNoClassCanBeFound()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$container->resolve('NotAClass');
	}
	
	public function testContainerPassesSingleDependencyUponInstantiatingObject()
	{
		$container = new IoCContainer();
		$object = $container->resolve('PO\IoCContainerTestA');
		$this->assertInstanceOf('stdClass', $object->arg);
	}
	
	public function testContainerCannotCreateAnObjectWithANonObjectDependency()
	{
		$this->setExpectedException('\RuntimeException');
		$container = new IoCContainer();
		$container->resolve('PO\IoCContainerTestB');
	}
	
	public function testContainerPassesMultipleDependenciesUponInstantiatingObject()
	{
		$container = new IoCContainer();
		$object = $container->resolve('PO\IoCContainerTestC');
		$this->assertInstanceOf('stdClass', $object->arg1);
		$this->assertInstanceOf('stdClass', $object->arg2);
	}
	
	public function testDependeciesAreResolvedUsingContainerAndReceiveTheirOwnDependencies()
	{
		$container = new IoCContainer();
		$object = $container->resolve('PO\IoCContainerTestD');
		$this->assertInstanceOf('PO\IoCContainerTestA', $object->arg);
		$this->assertInstanceOf('stdClass', $object->arg->arg);
	}
	
	public function testDependenciesCanBePartiallyProvidedAtResolveTime()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = $container->resolve(
			'PO\IoCContainerTestC',
			[
				null,
				$stdObject
			]
		);
		$this->assertEquals('value', $object->arg2->key);
	}
	
	public function testProvidedDependenciesMustBeInsideAnArray()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$object = $container->resolve(
			'PO\IoCContainerTestC',
			new \stdClass()
		);
	}
	
	public function testDownstreamDependenciesCanBePartiallyProvidedAtResolveTime()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = $container->resolve(
			'PO\IoCContainerTestD',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertEquals('value', $object->arg->arg->key);
	}
	
	public function testProvidedDownstreamDependenciesMustBeInsideAnArray()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$object = $container->resolve(
			'PO\IoCContainerTestD',
			[],
			new \stdClass()
		);
	}
	
	public function testDownstreamDependenciesArePassedToNestedClasses()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = $container->resolve(
			'PO\IoCContainerTestE',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertEquals('value', $object->arg->arg->arg->key);
	}
	
	public function testExceptionIsThrownIfNonObjectDependencyHasNoUserProvidedValue()
	{
		$this->setExpectedException('\RuntimeException');
		$container = new IoCContainer();
		$object = $container->resolve('PO\IoCContainerTestF');
	}
	
	public function testObjectCanBeResolvedStatically()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$object = IoCContainer::resolve(
			'PO\IoCContainerTestE',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertInstanceOf('PO\IoCContainerTestE', $object);
		$this->assertInstanceOf('PO\IoCContainerTestD', $object->arg);
		$this->assertInstanceOf('PO\IoCContainerTestA', $object->arg->arg);
		$this->assertEquals('value', $object->arg->arg->arg->key);
	}
	
	public function testContainerWillReturnSingletonWhenResolved()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = $container->resolve('stdClass');
		$this->assertSame($stdObject, $object);
	}
	
	public function testContainerWillUseRegisteredSingletonAsDependency()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = $container->resolve('PO\IoCContainerTestC');
		$this->assertSame($stdObject, $object->arg1);
		$this->assertSame($stdObject, $object->arg2);
	}
	
	public function testNestedDependencyWillUseRegisteredSingletonAsDependency()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = $container->resolve('PO\IoCContainerTestD');
		$this->assertSame($stdObject, $object->arg->arg);
	}
	
	public function testRegisterSingletonThrowsExceptionForNonObject()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$container->registerSingleton('string');
	}
	
	public function testSingletonCannotBeReplaced()
	{
		$this->setExpectedException('\RuntimeException');
		$container = new IoCContainer();
		$container->registerSingleton(new \stdClass());
		$container->registerSingleton(new \stdClass());
	}
	
	public function testSingletonCannotBeRegisteredStatically()
	{
		$this->setExpectedException('BadMethodCallException');
		IoCContainer::registerSingleton(new \stdClass());
	}
	
	public function testSingletonIsNotUsedWhenObjectIsResolvedStatically()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = IoCContainer::resolve('stdClass');
		$this->assertNotSame($stdObject, $object);
	}
	
	public function testRegisteredCallbackWillBeRunWhenResolved()
	{
		$container = new IoCContainer();
		$container->registerCallback('alias', function(){
			$stdObject = new \stdClass();
			$stdObject->key = 'value';
			return $stdObject;
		});
		$object = $container->resolve('alias');
		$this->assertEquals('value', $object->key);
	}
	
	public function testRegisteredCallbackHasAccessToContainer()
	{
		$container = new IoCContainer();
		$container->registerCallback('alias', function($container){
			return $container->resolve('PO\IoCContainerTestA');
		});
		$object = $container->resolve('alias');
		$this->assertInstanceOf('stdClass', $object->arg);
	}
	
	// @todo These aren't key/values, they're just values. Change it.
	public function testRegisteredCallbackHasAccessToResolveTimeArguments()
	{
		$container = new IoCContainer();
		$container->registerCallback('alias', function($container, $key, $value){
			$stdObject = new \stdClass();
			$stdObject->$key = $value;
			return $stdObject;
		});
		$object = $container->resolve('alias', 'testKey', 'testValue');
		$this->assertEquals('testValue', $object->testKey);
	}
	
	public function testRegisteredCallbackHasAccessToManyResolveTimeArguments()
	{
		$container = new IoCContainer();
		$container->registerCallback('alias', function($container, $key1, $value1, $key2, $value2){
			$stdObject = new \stdClass();
			$stdObject->$key1 = $value1;
			$stdObject->$key2 = $value2;
			return $stdObject;
		});
		$object = $container->resolve('alias', 'testKey1', 'testValue1', 'testKey2', 'testValue2');
		$this->assertEquals('testValue1', $object->testKey1);
		$this->assertEquals('testValue2', $object->testKey2);
	}
	
	public function testCallbackCannotBeRegisteredStatically()
	{
		$this->setExpectedException('BadMethodCallException');
		IoCContainer::registerCallback('alias', function(){});
	}
	
	public function testCallbackIsNotUsedWhenObjectIsResolvedStatically()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$container->registerCallback('alias', function(){return new \stdClass(); });
		IoCContainer::resolve('alias');
	}
	
	public function testRegisteredInterfaceImplementationCanBeResolved()
	{
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = $container->resolve('PO\IoCContainerTestInterface');
		$this->assertInstanceOf('PO\IoCContainerTestG', $object);
	}
	
	public function testRegisteredInterfaceExampleMustImplementInterface()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestA'
		);
	}
	
	public function testRegisteredInterfaceImplementationIsUsedAsADependency()
	{
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = $container->resolve('PO\IoCContainerTestH');
		$this->assertInstanceOf('PO\IoCContainerTestG', $object->arg);
	}
	
	public function testResolvedSingletonCanBeRegisteredInterfaceImplementation()
	{
		$container = new IoCContainer();
		$singleton = new IoCContainerTestG();
		$container->registerSingleton($singleton);
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = $container->resolve('PO\IoCContainerTestH');
		$this->assertSame($singleton, $object->arg);
	}
	
	public function testInterfaceImplementationCannotBeRegisteredStatically()
	{
		$this->setExpectedException('BadMethodCallException');
		IoCContainer::registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
	}
	
	public function testInterfaceImplementationCannotBeResolvedStatically()
	{
		$this->setExpectedException('BadMethodCallException');
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		IoCContainer::resolve('PO\IoCContainerTestInterface');
	}
	
	public function testObjectMethodCanBeCalledThroughAContainerAndItsDependenciesAreResolved()
	{
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod');
		$this->assertInstanceOf('stdClass', $object->arg);
	}
	
	public function testExceptionIsThrownIfNonObjectIsPassedToCall()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$container->call('non object', 'someMethod');
	}
	
	public function testExceptionIsThrownIfObjectPassedToCallDoesNotHaveGivenMethod()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call($object, 'invalidMethod');
	}
	
	public function testCannotCallAMethodWithANonObjectDependency()
	{
		$this->setExpectedException('\RuntimeException');
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod2');
	}
	
	public function testMethodDependeciesAreResolvedUsingContainerAndReceiveTheirOwnDependencies()
	{
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod3');
		$this->assertInstanceOf('PO\IoCContainerTestA', $object->arg);
		$this->assertInstanceOf('stdClass', $object->arg->arg);
	}
	
	public function testMethodDependenciesCanBePartiallyProvidedAtResolveTime()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call(
			$object,
			'testMethod4',
			[
				null,
				$stdObject
			]
		);
		$this->assertEquals('value', $object->arg2->key);
	}
	
	public function testProvidedMethodDependenciesMustBeInsideAnArray()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call(
			$object,
			'testMethod4',
			new \stdClass()
		);
	}
	
	public function testDownstreamMethodDependenciesCanBePartiallyProvidedAtResolveTime()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call(
			$object,
			'testMethod3',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertEquals('value', $object->arg->arg->key);
	}
	
	public function testProvidedMethodDownstreamDependenciesMustBeInsideAnArray()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call(
			$object,
			'testMethod3',
			[],
			new \stdClass()
		);
	}
	
	public function testMethodDownstreamDependenciesArePassedToNestedClasses()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call(
			$object,
			'testMethod5',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertEquals('value', $object->arg->arg->arg->key);
	}
	
	public function testExceptionIsThrownIfNonObjectMethodDependencyHasNoUserProvidedValue()
	{
		$this->setExpectedException('\RuntimeException');
		$container = new IoCContainer();
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod2');
	}
	
	public function testMethodCanBeCalledStatically()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$object = new IoCContainerTestI();
		IoCContainer::call(
			$object,
			'testMethod5',
			[],
			[
				'PO\IoCContainerTestA' => [$stdObject]
			]
		);
		$this->assertInstanceOf('PO\IoCContainerTestD', $object->arg);
		$this->assertInstanceOf('PO\IoCContainerTestA', $object->arg->arg);
		$this->assertEquals('value', $object->arg->arg->arg->key);
	}
	
	public function testContainerWillUseRegisteredSingletonAsMethodDependency()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod4');
		$this->assertSame($stdObject, $object->arg);
		$this->assertSame($stdObject, $object->arg2);
	}
	
	public function testNestedMethodDependencyWillUseRegisteredSingletonAsDependency()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod3');
		$this->assertSame($stdObject, $object->arg->arg);
	}
	
	public function testSingletonIsNotUsedWhenMethodIsCalledStatically()
	{
		$stdObject = new \stdClass();
		$container = new IoCContainer();
		$container->registerSingleton($stdObject);
		$object = new IoCContainerTestI();
		IoCContainer::call($object, 'testMethod');
		$this->assertNotSame($stdObject, $object);
	}
	
	public function testRegisteredInterfaceImplementationIsUsedAsMethodDependency()
	{
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod6');
		$this->assertInstanceOf('PO\IoCContainerTestG', $object->arg);
	}
	
	public function testRegisteredInterfaceImplementationIsUsedAsNestedMethodDependency()
	{
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = new IoCContainerTestI();
		$container->call($object, 'testMethod7');
		$this->assertInstanceOf('PO\IoCContainerTestG', $object->arg->arg);
	}
	
	public function testInterfaceImplementationIsNotUsedWhenMethodIsCalledStatically()
	{
		$this->setExpectedException('BadMethodCallException');
		$container = new IoCContainer();
		$container->registerInterface(
			'PO\IoCContainerTestInterface',
			'PO\IoCContainerTestG'
		);
		$object = new IoCContainerTestI();
		IoCContainer::call($object, 'testMethod6');
	}
	
	public function testContainerAcceptsIContainmentWhichIsCalledWithContainerAsArgument()
	{
		$mContainment = $this->getMock('\PO\IoCContainer\IContainment');
		$container = new IoCContainer();
		$mContainment->expects($this->once())
			->method('register')
			->with($this->identicalTo($container));
		$container->addContainment($mContainment);
	}
	
	public function testMultipleIContainmentsCanBePassedToConstructorAndEachIsCalledWithContainer()
	{
		$mContainment1 = $this->getMock('\PO\IoCContainer\IContainment');
		$mContainment2 = $this->getMock('\PO\IoCContainer\IContainment');
		$mContainment3 = $this->getMock('\PO\IoCContainer\IContainment');
		$mContainment1->expects($this->once())
			->method('register')
			->with($this->isInstanceOf('PO\IoCContainer'));
		$mContainment2->expects($this->once())
			->method('register')
			->with($this->isInstanceOf('PO\IoCContainer'));
		$mContainment3->expects($this->once())
			->method('register')
			->with($this->isInstanceOf('PO\IoCContainer'));
		new IoCContainer(
			$mContainment1,
			$mContainment2,
			$mContainment3
		);
	}
	
}

class IoCContainerTestA
{
	public $arg;
	public function __construct(\stdClass $arg){ $this->arg = $arg; }
}

class IoCContainerTestB
{
	public function __construct($arg){}
}

class IoCContainerTestC
{
	public $arg1;
	public $arg2;
	public function __construct(\stdClass $arg1, \stdClass $arg2){
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}
}

class IoCContainerTestD
{
	public $arg;
	public function __construct(IoCContainerTestA $arg){ $this->arg = $arg; }
}

class IoCContainerTestE
{
	public $arg;
	public function __construct(IoCContainerTestD $arg){ $this->arg = $arg; }
}

class IoCContainerTestF
{
	public $arg1;
	public $arg2;
	public function __construct(\stdClass $arg1, $arg2){
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}
}

interface IoCContainerTestInterface
{
	public function test();
}

class IoCContainerTestG
implements IoCContainerTestInterface
{
	public function test(){}
}

class IoCContainerTestH
{
	public $arg;
	public function __construct(IoCContainerTestInterface $arg){ $this->arg = $arg; }
}

class IoCContainerTestI
{
	public $arg;
	public $arg2;
	public function testMethod(\stdClass $arg){ $this->arg = $arg; }
	public function testMethod2($arg){}
	public function testMethod3(IoCContainerTestA $arg){ $this->arg = $arg; }
	public function testMethod4(\stdClass $arg, \stdClass $arg2){
		$this->arg = $arg;
		$this->arg2 = $arg2;
	}
	public function testMethod5(IoCContainerTestD $arg){ $this->arg = $arg; }
	public function testMethod6(IoCContainerTestInterface $arg){ $this->arg = $arg; }
	public function testMethod7(IoCContainerTestH $arg){ $this->arg = $arg; }
}
