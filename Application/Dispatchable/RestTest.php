<?php

namespace PO\Application\Dispatchable;

use PO\Application\Dispatchable\Rest\IEndpoint;
use PO\Application\Dispatchable\Rest\RouteVariables;

require_once 'vfsStream/vfsStream.php';
require_once dirname(__FILE__) . '/../IDispatchable.php';
require_once dirname(__FILE__) . '/Rest.php';
require_once dirname(__FILE__) . '/Rest/IEndpoint.php';
require_once dirname(__FILE__) . '/Rest/RouteVariables.php';
require_once dirname(__FILE__) . '/Rest/Exception.php';
require_once dirname(__FILE__) . '/../../Config.php';
require_once dirname(__FILE__) . '/../../Http/Response.php';
require_once dirname(__FILE__) . '/../../IoCContainer.php';

class RestTest
extends \PHPUnit_Framework_TestCase {
	
	private $mRoutesConfig;
	private $mResponse;
	private $mIoCContainer;
	private $mEndpoint;
	
	private $routes = [
		'GET /'						=> 'PO\Application\Dispatchable\RestTestIndex',
		'GET /test'					=> 'PO\Application\Dispatchable\RestTestTest',
		'GET /nested/path'			=> 'PO\Application\Dispatchable\RestTestNestedPath',
		'GET /noclass'				=> 'PO\Application\Dispatchable\RestTest\NoClass',
		'GET /notiendpoint'			=> 'stdClass',
		'POST /posttest'			=> 'PO\Application\Dispatchable\RestTestTest',
		'DELETE /deletetest'		=> 'PO\Application\Dispatchable\RestTestTest',
		'PUT /puttest'				=> 'PO\Application\Dispatchable\RestTestTest',
		'GET /test/{testkey}'		=> 'PO\Application\Dispatchable\RestTestTest',
		'GET /test/{key1}/{key2}'	=> 'PO\Application\Dispatchable\RestTestTest'
	];
	
	public function setUp()
	{
		$this->mRoutesConfig = $this->getMock(
			'\PO\Config',
			array(),
			array(array('key' => 'value'))
		);
		$this->mRoutesConfig
			->expects($this->any())
			->method('getKeys')
			->will($this->returnValue(array_keys($this->routes)));
		$routes = $this->routes;
		$this->mRoutesConfig
			->expects($this->any())
			->method('get')
			->will($this->returnCallback(function($key) use ($routes){
				return $routes[$key];
			}));
		$this->mResponse = $this->getMock('\PO\Http\Response');
		$this->mIoCContainer = $this->getMock('\PO\IoCContainer');
		$this->mEndpoint = $this->getMock('\PO\Application\Dispatchable\Rest\IEndpoint');
		$this->mIoCContainer
			->expects($this->any())
			->method('resolve')
			->with('PO\Application\Dispatchable\RestTestTest')
			->will($this->returnValue($this->mEndpoint));
		$_SERVER['REQUEST_METHOD'] = 'GET';
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->mRoutesConfig);
		unset($this->mResponse);
		unset($this->mIoCContainer);
		unset($this->mEndpoint);
		parent::tearDown();
	}
	
	// @todo Debug mode
	// @todo Logging errors
	
	public function testDispatchableCanBeInstantiated()
	{
		$router = new Rest($this->mRoutesConfig);
		$this->assertInstanceOf('\PO\Application\Dispatchable\Rest', $router);
	}
	
	public function testDispatchableRequiresConfigObject()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$router = new Rest();
	}
	
	public function testEndpointIsResolvedThroughIoCContainer()
	{
		$_SERVER['REQUEST_URI'] = '/test';
		$mIoCContainer = $this->getMock('PO\IoCContainer');
		$mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('PO\Application\Dispatchable\RestTestTest');
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $mIoCContainer);
	}
	
	public function testEndpointIsDispatchedThroughIoCContainer()
	{
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testEndpointIsRunOnlyOnce()
	{
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/test';
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsSetTo404IfNoControllerCanBeIdentified()
	{
		$this->mResponse
			->expects($this->once())
			->method('set404');
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/doesnotexist';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsSetTo500IfNoEndpointClassExists()
	{
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/noclass';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsSetTo500IfEndpointClassIsNotIEndpoint()
	{
		$mIoCContainer = $this->getMock('PO\IoCContainer');
		$mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('stdClass')
			->will($this->returnValue(new \stdClass()));
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/notiendpoint';
		$router->dispatch($this->mResponse, $mIoCContainer);
	}
	
	public function testRequestPathCanBeEmptyIfEndpointIsAssociated()
	{
		$mIoCContainer = $this->getMock('PO\IoCContainer');
		$mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('PO\Application\Dispatchable\RestTestIndex');
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/';
		$router->dispatch($this->mResponse, $mIoCContainer);
	}
	
	public function testRequestPathCanContainMultipleSlashes()
	{
		$mIoCContainer = $this->getMock('PO\IoCContainer');
		$mIoCContainer
			->expects($this->once())
			->method('resolve')
			->with('PO\Application\Dispatchable\RestTestNestedPath');
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/nested/path';
		$router->dispatch($this->mResponse, $mIoCContainer);
	}
	
	public function testDispatchableCanHaveRewriteBaseToApplyWhenIdentifyingRoutes()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig, 'rewritebase');
		$_SERVER['REQUEST_URI'] = '/rewritebase/test';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testDispatchableDoesNotFallBackToSimpleRouteIfRewriteBaseIsSupplied()
	{
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set404');
		$router = new Rest($this->mRoutesConfig, 'rewritebase');
		$_SERVER['REQUEST_URI'] = '/test';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRewriteBaseCanContainForwardSlashes()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig, 'rewrite/base');
		$_SERVER['REQUEST_URI'] = '/rewrite/base/test';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRewriteBaseMayBeginOrEndWithAForwardSlashAndTheyWillBeIgnored()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig, '/rewritebase/');
		$_SERVER['REQUEST_URI'] = '/rewritebase/test';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testUpperCasedCharactersInRequestPathDoNotAffectEndpoint()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig);
		$_SERVER['REQUEST_URI'] = '/tESt';
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsMadeAvailableToEndpoint()
	{
		$mResponse = $this->mResponse;
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch',
				[],
				$this->callback(function($argument) use ($mResponse){
					if (!is_array($argument)) return false;
					if ($argument[0] !== $mResponse) return false;
					return true;
				})
			);
		$_SERVER['REQUEST_URI'] = '/test';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRequestMethodCanBePOST()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$_SERVER['REQUEST_URI'] = '/posttest';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRequestMethodCanBePUT()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$_SERVER['REQUEST_URI'] = '/puttest';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRequestMethodCanBeDELETE()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$_SERVER['REQUEST_URI'] = '/deletetest';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRequestPathIsOnlyMatchedIfRequestMethodAlsoMatches()
	{
		$this->mResponse
			->expects($this->atLeastOnce())
			->method('set404');
		$_SERVER['REQUEST_URI'] = '/test';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testResponseIsSetTo500IfEndpointThrowsException()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->will($this->throwException(new \Exception()));
		$this->mResponse
			->expects($this->once())
			->method('set500');
		$_SERVER['REQUEST_URI'] = '/test';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testEndpointCannotOutputToTheScreen()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->will($this->returnCallback(function(){
				echo 'I shouldn\'t be output';
			}));
		$_SERVER['REQUEST_URI'] = '/test';
		$router = new Rest($this->mRoutesConfig);
		ob_start();
		$router->dispatch($this->mResponse, $this->mIoCContainer);
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($output, '');
	}
	
	public function testRouteCanBeDefinedWithARouteVariableAndRouteDataIsPassedToEndpoint()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch',
				[],
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (!($argument[1] instanceof RouteVariables)) return false;
					if ($argument[1]['testkey'] != 'testvalue') return false;
					return true;
				})
			);
		$_SERVER['REQUEST_URI'] = '/test/testvalue';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testRouteCanHaveMultipleRouteVariables()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch',
				[],
				$this->callback(function($argument){
					if (!is_array($argument)) return false;
					if (!($argument[1] instanceof RouteVariables)) return false;
					if ($argument[1]['key1'] != 'value1') return false;
					if ($argument[1]['key2'] != 'value2') return false;
					return true;
				})
			);
		$_SERVER['REQUEST_URI'] = '/test/value1/value2';
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer);
	}
	
	public function testDispatchableCanBeDispatchedUsingStringRequestPathAndMethod()
	{
		$this->mIoCContainer
			->expects($this->once())
			->method('call')
			->with(
				$this->mEndpoint,
				'dispatch'
			);
		$router = new Rest($this->mRoutesConfig);
		$router->dispatch($this->mResponse, $this->mIoCContainer, 'GET /test');
	}
	
}

class RestTestIndex implements IEndpoint {}
class RestTestTest implements IEndpoint {}
class RestTestNestedPath implements IEndpoint {}
