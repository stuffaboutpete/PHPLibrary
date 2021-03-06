<?php

namespace PO;

require_once dirname(__FILE__) . '/Application.php';
require_once dirname(__FILE__) . '/Application/IDispatchable.php';
require_once dirname(__FILE__) . '/Application/IBootstrap.php';
require_once dirname(__FILE__) . '/Application/IExceptionHandler.php';
require_once dirname(__FILE__) . '/Http/Response.php';

class ApplicationTest
extends \PHPUnit_Framework_TestCase {
	
	private $mDispatchable;
	private $mResponse;
	private $mIoCContainer;
	private $mBootstrap;
	private $mBootstrap2;
	
	public function setUp()
	{
		$this->mDispatchable = $this->getMock('\PO\Application\IDispatchable');
		$this->mResponse = $this->getMock('\PO\Http\Response');
		$this->mIoCContainer = $this->getMock('\PO\IoCContainer');
		$this->mBootstrap = $this->getMock('\PO\Application\IBootstrap');
		$this->mBootstrap2 = $this->getMock('\PO\Application\IBootstrap');
		$this->mExceptionHandler = $this->getMock('\PO\Application\IExceptionHandler');
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->mDispatchable);
		unset($this->mResponse);
		unset($this->mIoCContainer);
		unset($this->mBootstrap);
		unset($this->mBootstrap2);
		unset($this->mExceptionHandler);
		parent::tearDown();
	}
	
	public function testApplicationCanBeInstantiated()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$this->assertInstanceOf('\PO\Application', $application);
	}
	
	public function testApplicationRequiresDispatchableObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$application = new Application(null, $this->mResponse, $this->mIoCContainer);
	}
	
	public function testApplicationRequiresResponseObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$application = new Application($this->mDispatchable, null, $this->mIoCContainer);
	}
	
	public function testApplicationRequiresIoCContainerObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$application = new Application($this->mDispatchable, $this->mResponse);
	}
	
	public function testCallingRunOnApplicationCallsDispatchOnDispatchable()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mDispatchable
			->expects($this->once())
			->method('dispatch');
		$application->run();
	}
	
	public function testRunMethodReturnsSelf()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->assertSame($application, $application->run());
	}
	
	public function testResponseObjectIsPassedToDispatchable()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mDispatchable
			->expects($this->once())
			->method('dispatch')
			->with($this->mResponse);
		$application->run();
	}
	
	public function testIoCContainerIsPassedToDispatchable()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mDispatchable
			->expects($this->once())
			->method('dispatch')
			->with(
				$this->anything(),
				$this->mIoCContainer
			);
		$application->run();
	}
	
	public function testResponseObjectIsInspectedOnceForStatus()
	{
		$this->mResponse
			->expects($this->once())
			->method('isInitialised')
			->will($this->returnValue(true));
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$application->run();
	}
	
	public function testApplicationThrowsIfResponseIsNotSetDuringDispatch()
	{
		$this->setExpectedException('\RuntimeException');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(false));
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$application->run();
	}
	
	public function testApplicationProcessesResponseOnDispatch()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mResponse
			->expects($this->once())
			->method('process');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$application->run();
	}
	
	public function testApplicationAcceptsArrayOfBootstraps()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			array($this->mBootstrap, $this->mBootstrap2)
		);
		$this->assertInstanceOf('\PO\Application', $application);
	}
	
	public function testApplicationDoesNotAcceptSingleBootstrap()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mBootstrap
		);
	}
	
	public function testApplicationRejectsNonIBootstraps()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			array($this->mBootstrap, new \stdClass())
		);
	}
	
	public function testEachBootstrapIsRunOnceWhenApplicationIsRun()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mBootstrap
			->expects($this->once())
			->method('run');
		$this->mBootstrap2
			->expects($this->once())
			->method('run');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			array($this->mBootstrap, $this->mBootstrap2)
		);
		$application->run();
	}
	
	public function testEachBootstrapIsPassedIoCContainer()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			array($this->mBootstrap, $this->mBootstrap2)
		);
		$this->mBootstrap
			->expects($this->atLeastOnce())
			->method('run')
			->with($this->mIoCContainer);
		$this->mBootstrap2
			->expects($this->atLeastOnce())
			->method('run')
			->with($this->mIoCContainer);
		$application->run();
	}
	
	public function testResponseIsSetTo500IfExceptionIsThrownWhilstDispatching()
	{
		$this->setExpectedException('\PO\TestException');
		$this->mDispatchable
			->expects($this->any())
			->method('dispatch')
			->will($this->returnCallback(function(){
				throw new \PO\TestException();
			}));
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer
		);
		$application->run();
	}
	
	public function testResponseIsSetTo500IfExceptionIsThrownWhilstBootstrapping()
	{
		$this->setExpectedException('\PO\TestException');
		$this->mBootstrap
			->expects($this->any())
			->method('run')
			->will($this->returnCallback(function(){
				throw new \PO\TestException();
			}));
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			[$this->mBootstrap]
		);
		$application->run();
	}
	
	public function testApplicationAcceptsOptionalIExceptionHandler()
	{
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			[],
			$this->mExceptionHandler
		);
		$this->assertInstanceOf('\PO\Application', $application);
	}
	
	public function testExceptionThrownFromBootstrapRunMethodIsPassedToExceptionHandler()
	{
		$exception = new \Exception('Something went wrong');
		$this->mBootstrap
			->expects($this->once())
			->method('run')
			->will($this->throwException($exception));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with($exception);
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			[$this->mBootstrap],
			$this->mExceptionHandler
		);
		$application->run();
	}
	
	public function testResponseIsPassedToExceptionHandler()
	{
		$exception = new \Exception('Something went wrong');
		$this->mBootstrap
			->expects($this->once())
			->method('run')
			->will($this->throwException($exception));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$exception,
				$this->mResponse
			);
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			[$this->mBootstrap],
			$this->mExceptionHandler
		);
		$application->run();
	}
	
	public function testExceptionThrownFromDispatchableRunMethodIsPassedToExceptionHandler()
	{
		$exception = new \Exception('Something went wrong');
		$this->mDispatchable
			->expects($this->once())
			->method('dispatch')
			->will($this->throwException($exception));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$exception,
				$this->mResponse
			);
		$application = new Application(
			$this->mDispatchable,
			$this->mResponse,
			$this->mIoCContainer,
			[],
			$this->mExceptionHandler
		);
		$application->run();
	}
	
}

class TestException extends \Exception {}
