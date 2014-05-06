<?php

namespace Suburb\Application\Dispatchable;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../../Application.php';
require_once dirname(__FILE__) . '/../IDispatchable.php';
require_once dirname(__FILE__) . '/Rest.php';
require_once dirname(__FILE__) . '/Rest/IEndpoint.php';
require_once dirname(__FILE__) . '/Rest/Exception.php';
require_once dirname(__FILE__) . '/../../Config.php';
require_once dirname(__FILE__) . '/../../Http/Response.php';

class RestTest
extends \PHPUnit_Framework_TestCase {
	
	private $mRoutesConfig;
	private $mApplication;
	private $mResponse;
	
	private $routes = [
		'GET /'						=> 'Suburb\Application\Dispatchable\RestTest\Index',
		'GET /test'					=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'GET /nested/path'			=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'GET /noclass'				=> 'Suburb\Application\Dispatchable\RestTest\NoClass',
		'GET /notiendpoint'			=> 'Suburb\Application\Dispatchable\RestTest\NotIEndpoint',
		'GET /camelcased'			=> 'Suburb\Application\Dispatchable\RestTest\CamelCased',
		'GET /global'				=> 'GlobalTest',
		'POST /posttest'			=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'DELETE /deletetest'		=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'PUT /puttest'				=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'GET /test/{testkey}'		=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'GET /test/{key1}/{key2}'	=> 'Suburb\Application\Dispatchable\RestTest\Test',
		'POST /test'				=> 'Suburb\Application\Dispatchable\RestTest\PostTest'
	];
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('SuburbApplicationDispatchableRestTest', null, array(
			'Index.php'			=> $this->writeClass('Index'),
			'Test.php'			=> $this->writeClass('Test'),
			'CamelCased.php'	=> $this->writeClass('CamelCased'),
			'Global.php'		=> $this->writeClass('GlobalTest', ''),
			'Notiendpoint.php'	=> '<?php namespace Suburb\Application\Dispatchable\RestTest; ' .
								   'class NotIEndpoint {}',
			'PostTest.php'		=> $this->writeClass('PostTest')
		));
		foreach (scandir(\vfsStream::url('SuburbApplicationDispatchableRestTest')) as $file) {
			include_once \vfsStream::url("SuburbApplicationDispatchableRestTest\\$file");
		}
		$this->mRoutesConfig = $this->getMock(
			'\Suburb\Config',
			array(),
			array(array('key' => 'value'))
		);
		$this->mRoutesConfig
			->expects($this->any())
			->method('getKeys')
			->will($this->returnValue(array_keys($this->routes)));
		$this->mRoutesConfig
			->expects($this->any())
			->method('get')
			->will($this->returnCallback([$this, 'getCallback']));
		$this->mApplication = $this->getMock(
			'\Suburb\Application',
			array(),
			array(
				$this->getMock('\Suburb\Application\IDispatchable'),
				$this->getMock('\Suburb\Http\Response')
			)
		);
		$this->mResponse = $this->getMock('\Suburb\Http\Response');
		$_SERVER['REQUEST_METHOD'] = 'GET';
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mRoutesConfig = null;
		$this->mApplication = null;
		$this->mResponse = null;
		parent::tearDown();
	}
	
	// @todo Debug mode
	// @todo Logging errors
	
	public function testDispatchableCanBeInstantiated()
	{
		$router = new Rest($this->mRoutesConfig);
		$this->assertInstanceOf('\Suburb\Application\Dispatchable\Rest', $router);
	}
	
	public function testDispatchableRequiresConfigObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$router = new Rest();
	}
	
	public function testEndpointIsIdentifiedAndRunFromRequestPath()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testEndpointIsRunOnlyOnce()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mResponse
			->expects($this->once())
			->method('set200');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo404IfNoControllerCanBeIdentified()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/doesnotexist';
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo500IfNoEndpointClassExists()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/noclass';
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo500IfEndpointClassIsNotIEndpoint()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/notiendpoint';
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestPathCanBeEmptyIfEndpointIsAssociated()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Index'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestPathCanContainMultipleSlashes()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/nested/path';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testEndpointCanExistInGlobalNamespace()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/global';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \GlobalTest'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testDispatchableCanHaveRewriteBaseToApplyWhenIdentifyingRoutes()
	{
		$router = new Rest($this->mRoutesConfig, 'rewritebase');
		$_SERVER['REQUEST_URI'] = '/rewritebase/test';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testDispatchableDoesNotFallBackToSimpleRouteIfRewriteBaseIsSupplied()
	{
		$router = new Rest($this->mRoutesConfig, 'rewritebase');
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set404');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRewriteBaseCanContainForwardSlashes()
	{
		$router = new Rest($this->mRoutesConfig, 'rewrite/base');
		$_SERVER['REQUEST_URI'] = '/rewrite/base/test';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRewriteBaseMayBeginOrEndWithAForwardSlashAndTheyWillBeIgnored()
	{
		$router = new Rest($this->mRoutesConfig, '/rewritebase/');
		$_SERVER['REQUEST_URI'] = '/rewritebase/test';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testUpperCasedCharactersInRequestPathDoNotAffectEndpoint()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/tESt';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\Test'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testUpperCasedCharactersInControllerFileAndTemplateDoNotAffectDispatch()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/camelcased';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\CamelCased'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testApplicationIsPassedToEndpoint()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mApplication
			->expects($this->any())
			->method('hasExtension')
			->will($this->returnCallback(function($key){
				return ($key == 'outputApplicationClass');
			}));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($this->equalTo(get_class($this->mApplication)));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testApplicationIsPassedResponse()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mApplication
			->expects($this->any())
			->method('hasExtension')
			->will($this->returnCallback(function($key){
				return ($key == 'outputResponseClass');
			}));
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($this->equalTo(get_class($this->mResponse)));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestMethodCanBePOST()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/posttest';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m a POST request'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestMethodCanBePUT()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/puttest';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m a PUT request'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestMethodCanBeDELETE()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/deletetest';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m a DELETE request'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRequestPathIsOnlyMatchedIfRequestMethodAlsoMatches()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m a POST request'));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testResponseIsSetTo500IfEndpointThrowsException()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mApplication
			->expects($this->any())
			->method('hasExtension')
			->will($this->returnCallback(function($key){
				return ($key == 'throwException');
			}));
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testEndpointCannotOutputToTheScreen()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mApplication
			->expects($this->any())
			->method('hasExtension')
			->will($this->returnCallback(function($key){
				return ($key == 'outputToScreen');
			}));
		ob_start();
		$router->dispatch($this->mApplication, $this->mResponse);
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($output, '');
	}
	
	public function testRouteCanBeDefinedWithARouteVariableAndRouteDataIsPassedToEndpoint()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test/testvalue';
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($this->identicalTo(['testkey' => 'testvalue']));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testRouteCanHaveMultipleRouteVariables()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test/value1/value2';
		$this->mResponse
			->expects($this->once())
			->method('set200')
			->with($this->identicalTo([
				'key1' => 'value1',
				'key2' => 'value2'
			]));
		$router->dispatch($this->mApplication, $this->mResponse);
	}
	
	public function testDispatchableCanBeDispatchedUsingStringRequestPathAndMethod()
	{
		$router = new Rest($this->mRoutesConfig);
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set200')
			->with($this->equalTo('I\'m in \Suburb\Application\Dispatchable\RestTest\PostTest'));
		$router->dispatch($this->mApplication, $this->mResponse, 'POST /test');
	}
	
	public function getCallback($key){
		return $this->routes[$key];
	}
	
	private function writeClass($className, $namespace = 'Suburb\Application\Dispatchable\RestTest')
	{
		
		$content = '<?php';
		if ($namespace != '') {
			$content .= "\r\nnamespace $namespace;";
		}
		return $content . <<<CLASS
		
		use Suburb\Application;
		use Suburb\Http\Response;
		use Suburb\Application\Dispatchable\Rest\IEndpoint;
		class $className
		implements IEndpoint
		{
			public function dispatch(
				Application	\$application,
				Response	\$response,
				array		\$routeVariables
			)
			{
				if (\$_SERVER['REQUEST_METHOD'] != 'GET') {
					\$response->set200("I'm a \$_SERVER[REQUEST_METHOD] request");
					return;
				}
				if (\$application->hasExtension('outputApplicationClass')) {
					\$response->set200(get_class(\$application));
					return;
				}
				if (\$application->hasExtension('outputResponseClass')) {
					\$response->set200(get_class(\$response));
					return;
				}
				if (\$application->hasExtension('throwException')) {
					throw new \Exception();
				}
				if (\$application->hasExtension('outputToScreen')) {
					echo 'I\m in \$namespace\$className';
				}
				if (!empty(\$routeVariables)) {
					\$response->set200(\$routeVariables);
					return;
				}
				\$response->set200('I\'m in \\$namespace\\$className');
			}
		}
		
CLASS;
	
	}
	
}