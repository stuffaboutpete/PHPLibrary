<?php

namespace PO\Application\Dispatchable;

use PO\Application\Dispatchable\Mvc\Controller;
use PO\Application\Dispatchable\Mvc\RouteVariables;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../IDispatchable.php';
require_once dirname(__FILE__) . '/../IExceptionHandler.php';
require_once dirname(__FILE__) . '/Mvc.php';
require_once dirname(__FILE__) . '/Mvc/Controller.php';
require_once dirname(__FILE__) . '/Mvc/IControllerIdentifier.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/Mvc/Exception.php';
require_once dirname(__FILE__) . '/../../Http/Response.php';
require_once dirname(__FILE__) . '/../../IoCContainer.php';
require_once dirname(__FILE__) . '/../../IoCContainer/IContainment.php';
require_once dirname(__FILE__) . '/../../Helper/ArrayType.php';

class MvcTest
extends \PHPUnit_Framework_TestCase {
	
	private $virtualDir;
	private $mResponse;
	private $mIoCContainer;
	private $mController;
	private $mExceptionHandler;
	private $mException;
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('POApplicationDispatchableMvcTest', null, array(
			'Test.phtml' => 'I\'m in a template',
			'TestWithContent.phtml' => '<?php echo $content;'
		));
		$this->mControllerIdentifier = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\IControllerIdentifier'
		);
		$this->mResponse = $this->getMock('\PO\Http\Response');
		$this->mIoCContainer = $this->getMock('\PO\IoCContainer');
		$this->mController = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\Controller',
			['dispatch']
		);
		$this->mExceptionHandler = $this->getMock('\PO\Application\IExceptionHandler');
		$this->mException = $this->getMock('\PO\Application\Dispatchable\MvcTestException');
		$_SERVER['REQUEST_URI'] = '/';
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->virtualDir);
		unset($this->mControllerIdentifier);
		unset($this->mResponse);
		unset($this->mIoCContainer);
		unset($this->mController);
		unset($this->mExceptionHandler);
		parent::tearDown();
	}
	
	public function testDispatchableCanBeInstantiated()
	{
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$this->assertInstanceOf('\PO\Application\Dispatchable\Mvc', $dispatchable);
	}
	
	public function testDispatchableRequiresInstanceOfIControllerIdentifier()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		new Mvc();
	}
	
	public function testControllerIdentifierIsPassedPathOnDispatch()
	{
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mControllerIdentifier
			->expects($this->once())
			->method('receivePath')
			->with('/test');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testIdentifiedControllerIsDispatched()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mController
			->expects($this->once())
			->method('dispatch');
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->will($this->returnCallback(function($controller, $method){
				$controller->dispatch();
			}));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsInitialisedOnce()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mResponse
			->expects($this->once())
			->method('set200');
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsSetTo404IfNoControllerClassOrTemplateIsReturned()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}

	public function testExceptionIsThrownIfControllerClassDoesNotExist()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\NonExistant'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}

	public function testExceptionIsThrownIfControllerClassIsNotInstanceOfController()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\NotValid'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRouteVariablesAreMadeAvailableToController()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue(['testKey' => 'Test Value']));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mController,
				'dispatch',
				[],
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (!($argument[0] instanceof RouteVariables)) return false;
					if ($argument[0]['testKey'] != 'Test Value') return false;
					return true;
				})
			);
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsThrownIfPathVariablesIsNotArray()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue('string'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsThrownIfPathVariablesIsNotAssociative()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue(['nonAssocArray']));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testPathVariablesMayBeEmptyArray()
	{
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue([]));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testIdentifiedTemplateFileIsRendered()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/Test.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in a template'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsThrownIfTemplateFileDoesNotExist()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/NonExistant.phtml')
			));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testTemplateCanBeRenderedWithNoControllerClass()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/Test.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in a template'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testTemplateFileCanAccessTemplateVariables()
	{
		$mController = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\Controller',
			['dispatch', 'getTemplateVariables']
		);
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($mController));
		$mController
			->expects($this->any())
			->method('getTemplateVariables')
			->will($this->returnValue(['content' => 'Test value']));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/TestWithContent.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('Test value'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testControllerIsResolvedThroughIoCContainer()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsThrownIfIoCContainerDoesNotReturnInstanceOfController()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue(new \stdClass()));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsThrownIfControllerDoesNotHaveDispatchMethod()
	{
		$this->setExpectedException('\PO\Application\Dispatchable\Mvc\Exception');
		$mController = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\Controller',
			[]
		);
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($mController));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testControllerIsDispatchedThroughIoCContainer()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mController,
				'dispatch'
			);
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testControllerAcceptsOptionalIExceptionHandler()
	{
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$this->assertInstanceOf('\PO\Application\Dispatchable\Mvc', $dispatchable);
	}
	
	public function testExceptionThrownFromControllerIdentifierIsPassedToIExceptionHandler()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnCallback(function(){
				throw new MvcTestException();
			}));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\MvcTestException'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionThrownFromControllerIsPassedToIExceptionHandler()
	{
		$this->mController
			->expects($this->any())
			->method('dispatch')
			->will($this->throwException(new MvcTestException()));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->will($this->returnCallback(function($controller, $method){
				$controller->dispatch();
			}));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\MvcTestException'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionThrownFromControllerTemplateIsPassedToIExceptionHandler()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/ExceptionTemplate.phtml')
			));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToIExceptionHandlerIfIdentifiedClassDoesNotExist()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\InvalidClass'));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToIExceptionHandlerIfControllerIsNotInstanceOfController()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestException'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestException')
			->will($this->returnValue(new \PO\Application\Dispatchable\MvcTestException()));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToIExceptionHandlerIfControllerDoesNotHaveDispatchMethod()
	{
		$mController = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\Controller',
			[]
		);
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($mController));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToIExceptionHandlerIfIdentifiedTemplateDoesNotExist()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($this->mController));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('POApplicationDispatchableMvcTest/NonExistant.phtml')
			));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToIExceptionHandlerIfPathVariablesAreNotAssociativeArray()
	{
		$mController = $this->getMock(
			'\PO\Application\Dispatchable\Mvc\Controller',
			['dispatch', 'getTemplateVariables']
		);
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\PO\Application\Dispatchable\MvcTestController'));
		$this->mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\PO\Application\Dispatchable\MvcTestController')
			->will($this->returnValue($mController));
		$mController
			->expects($this->atLeastOnce())
			->method('getTemplateVariables')
			->will($this->returnValue(['one', 'two']));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				500
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionIsPassedToExceptionHandlerIfNoControllerOrTemplateIsFound()
	{
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				404
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsStillSetIfExceptionHandlerDoesNotSetItWhilstHandlingException()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\PO\Application\Dispatchable\Mvc\Exception'),
				$this->mResponse,
				404
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testExceptionThrownFromExceptionHandlerStillResultsInResponseBeingSet()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$this->mResponse
			->expects($this->once())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mExceptionHandler
			->expects($this->once())
			->method('handleException')
			->will($this->returnCallback(function(){
				throw new \Exception();
			}));
		$dispatchable = new Mvc($this->mControllerIdentifier, $this->mExceptionHandler);
		$dispatchable->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	/**
	 * @todo  Issue involving PHPUnit, handling fatal errors, @runInSeperateProcess and vfsstream
	 * stops us creating the following tests. Will have to come back to this...
	 */
	
	public function testFatalErrorTriggeredInControllerIdentifierIsPassedToIExceptionHandlerAfterSetup()
	{
		
	}
	
	public function testFatalErrorTriggeredInControllerIsPassedToIExceptionHandler()
	{
		
	}
	
	public function testFatalErrorTriggeredInControllerTemplateIsPassedToIExceptionHandler()
	{
		
	}
	
	public function testResponseIsSetTo500IfExceptionHandlerDoesNotSetReponseWhilstHandlingError()
	{
		;
	}
	
}

class MvcTestController extends Controller {}
class MvcTestException extends \Exception {}
