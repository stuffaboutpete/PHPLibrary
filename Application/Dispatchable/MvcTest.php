<?php

namespace Suburb\Application\Dispatchable;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../../Application.php';
require_once dirname(__FILE__) . '/../IDispatchable.php';
require_once dirname(__FILE__) . '/../IErrorHandler.php';
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
	private $mApplication;
	private $mResponse;
	private $mErrorHandler;
	private $mException;
	
	public static function setUpBeforeClass()
	{
		spl_autoload_register(function($class){
			$classParts = explode('\\', $class);
			if (array_shift($classParts) != 'TestNamespace') return;
			$path = \vfsStream::url(
				'SuburbApplicationDispatchableMvcTest/' . implode('/', $classParts) . '.php'
			);
			if (file_exists($path)) include_once $path;
		});
	}
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('SuburbApplicationDispatchableMvcTest', null, array(
			//'Index.php' => $this->writeClass('Index')/*,
			'Test.php' => $this->writeClass('Test'),
			'IoCTest.php' => $this->writeClass('IoCTest'),
			'ExceptionTest.php' => $this->writeClass('ExceptionTest'),
			'ExceptionTemplate.phtml' => '<?php throw new \Exception();',
			'Test.phtml' => 'I\'m in a template',
			'TestWithContent.phtml' => '<?php echo $content;',
			'NotValid.php' => '<?php ' .
				'namespace TestNamespace;' .
				'class NotValid {}'
			
		));
		$this->mControllerIdentifier = $this->getMock(
			'\Suburb\Application\Dispatchable\Mvc\IControllerIdentifier'
		);
		$this->mApplication = $this->getMock(
			'\Suburb\Application',
			array(),
			array(
				$this->getMock('\Suburb\Application\IDispatchable'),
				$this->getMock('\Suburb\Http\Response')
			)
		);
		$this->mResponse = $this->getMock('\Suburb\Http\Response');
		$this->mErrorHandler = $this->getMock('\Suburb\Application\IErrorHandler');
		$this->mException = $this->getMock('\Suburb\Application\Dispatchable\MvcTestException');
		$_SERVER['REQUEST_URI'] = '/';
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->virtualDir = null;
		$this->mControllerIdentifier = null;
		$this->mApplication = null;
		$this->mResponse = null;
		$this->mErrorHandler = null;
		parent::tearDown();
	}
	
	public function testDispatchableCanBeInstantiated()
	{
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$this->assertInstanceOf('\Suburb\Application\Dispatchable\Mvc', $dispatchable);
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
			->will($this->returnValue('\TestNamespace\Test'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testIdentifiedControllerIsDispatched()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \TestNamespace\Test'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsInitialisedOnce()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mResponse
			->expects($this->once())
			->method('set200');
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo404IfNoControllerClassOrTemplateIsReturned()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}

	public function testExceptionIsThrownIfControllerClassDoesNotExist()
	{
		$this->setExpectedException('\Suburb\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\NonExistant'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}

	public function testExceptionIsThrownIfControllerClassIsNotInstanceOfController()
	{
		$this->setExpectedException('\Suburb\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\NotValid'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testApplicationIsPassedToController()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mApplication
			->expects($this->at(0))
			->method('hasExtension')
			->with($this->equalTo('outputApplicationClass'))
			->will($this->returnValue(true));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo(get_class($this->mApplication)));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testPathVariablesArePassedToController()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue(['testKey' => 'Test Value']));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('testKey => Test Value'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsThrownIfPathVariablesIsNotArray()
	{
		$this->setExpectedException('\Suburb\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue('string'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsThrownIfPathVariablesIsNotAssociative()
	{
		$this->setExpectedException('\Suburb\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue(['nonAssocArray']));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testPathVariablesMayBeEmptyArray()
	{
		$this->mControllerIdentifier
			->expects($this->any())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getPathVariables')
			->will($this->returnValue([]));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo(''));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testIdentifiedTemplateFileIsRendered()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/Test.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \TestNamespace\TestI\'m in a template'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsThrownIfTemplateFileDoesNotExist()
	{
		$this->setExpectedException('\Suburb\Application\Dispatchable\Mvc\Exception');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/NonExistant.phtml')
			));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testTemplateCanBeRenderedWithNoControllerClass()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/Test.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in a template'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testTemplateFileCanAccessTemplateVariables()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/TestWithContent.phtml')
			));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \TestNamespace\TestTest value'));
		$dispatchable = new Mvc($this->mControllerIdentifier);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testControllerIsResolvedThroughIoCContainerIfProvided()
	{
		$mIoCContainer = $this->getMock('\Suburb\IoCContainer');
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('\TestNamespace\Test')
			->will($this->returnValue(new \TestNamespace\IoCTest()));
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \TestNamespace\IoCTest'));
		$dispatchable = new Mvc($this->mControllerIdentifier, $mIoCContainer);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testControllerAcceptsOptionalIErrorHandler()
	{
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$this->assertInstanceOf('\Suburb\Application\Dispatchable\Mvc', $dispatchable);
	}
	
	public function testIErrorHandlerSetupMethodIsCalled()
	{
		$this->mErrorHandler
			->expects($this->once())
			->method('setup')
			->with(
				$this->equalTo($this->mApplication),
				$this->equalTo($this->mResponse)
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionThrownFromControllerIdentifierIsPassedToIErrorHandlerAfterSetup()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnCallback(function(){
				throw new MvcTestException();
			}));
		$this->mErrorHandler
			->expects($this->at(0))
			->method('setup');
		$this->mErrorHandler
			->expects($this->at(1))
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\MvcTestException'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionThrownFromControllerIsPassedToIErrorHandler()
	{
		$this->mApplication
			->expects($this->at(1))
			->method('hasExtension')
			->with('throwException')
			->will($this->returnValue(true));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\MvcTestException'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionThrownFromControllerTemplateIsPassedToIErrorHandler()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/ExceptionTemplate.phtml')
			));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Exception'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsPassedToIErrorHandlerIfIdentifiedClassDoesNotExist()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\InvalidClass'));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsPassedToIErrorHandlerIfControllerIsNotInstanceOfController()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\NotValid'));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsPassedToIErrorHandlerIfIdentifiedTemplateDoesNotExist()
	{
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getTemplatePath')
			->will($this->returnValue(
				\vfsStream::url('SuburbApplicationDispatchableMvcTest/NonExistant.phtml')
			));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsPassedToIErrorHandlerIfPathVariablesAreNotAssociativeArray()
	{
		$this->mApplication
			->expects($this->at(2))
			->method('hasExtension')
			->with('outputInvalidTemplateVariables')
			->will($this->returnValue(true));
		$this->mControllerIdentifier
			->expects($this->once())
			->method('getControllerClass')
			->will($this->returnValue('\TestNamespace\Test'));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with($this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionIsPassedToErrorHandlerIfNoControllerOrTemplateIsFound()
	{
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'),
				404
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo500ifErrorHandlerDoesNotSetReponseWhilstHandlingException()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->with(
				$this->isInstanceOf('\Suburb\Application\Dispatchable\Mvc\Exception'),
				404
			);
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testExceptionThrownFromErrorHandlerStillResultsInResponseBeingSet()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$this->mResponse
			->expects($this->once())
			->method('isInitialised')
			->will($this->returnValue(true));
		$this->mErrorHandler
			->expects($this->once())
			->method('handleException')
			->will($this->returnCallback(function(){
				throw new \Exception();
			}));
		$dispatchable = new Mvc($this->mControllerIdentifier, null, $this->mErrorHandler);
		$dispatchable->dispatch($this->mApplication, $this->mResponse);
	}
	
	/**
	 * @todo  Issue involving PHPUnit, handling fatal errors, @runInSeperateProcess and vfsstream
	 * stops us creating the following tests. Will have to come back to this...
	 */
	
	public function testFatalErrorTriggeredInControllerIdentifierIsPassedToIErrorHandlerAfterSetup()
	{
		
	}
	
	public function testFatalErrorTriggeredInControllerIsPassedToIErrorHandler()
	{
		
	}
	
	public function testFatalErrorTriggeredInControllerTemplateIsPassedToIErrorHandler()
	{
		
	}
	
	public function testResponseIsSetTo500ifErrorHandlerDoesNotSetReponseWhilstHandlingError()
	{
		;
	}
	
	private function writeClass($className)
	{
		
		return <<<CLASS
<?php
namespace TestNamespace;
		use \Suburb\Application;
		use \Suburb\Application\Dispatchable;
		use \Suburb\Application\Dispatchable\Mvc\Controller;
		class $className
		extends Controller
		{
			private \$application;
			public function dispatch(Application \$application, \$pathVariables = null)
			{
				\$this->application = \$application;
				if (\$application->hasExtension('outputApplicationClass')) {
					echo get_class(\$application);
					return;
				}
				if (\$application->hasExtension('throwException')) {
					throw new Dispatchable\MvcTestException('This is an exception');
				}
				if (is_array(\$pathVariables)) {
					foreach (\$pathVariables as \$key => \$value) echo "\$key => \$value";
					return;
				}
				echo 'I\'m in \TestNamespace\\$className';
			}
			public function getTemplateVariables(){
				if (\$this->application->hasExtension('outputInvalidTemplateVariables')) {
					return ['none', 'associative'];
				}
				return ['content' => 'Test value'];
			}
		}
		
CLASS;
	
	}
	
}

class MvcTestException extends \Exception {}
