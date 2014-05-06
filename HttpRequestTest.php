<?php

namespace Suburb;

require_once dirname(__FILE__) . '/HttpRequest.php';
require_once dirname(__FILE__) . '/HttpRequest/ITransferMethod.php';
require_once dirname(__FILE__) . '/HttpRequest/Response.php';

class HttpRequestTest
extends \PHPUnit_Framework_TestCase {
	
	private $mTransferMethod;
	private $mResponse;
	
	public function setUp()
	{
		$this->mTransferMethod = $this->getMock(
			'\\Suburb\\HttpRequest\\ITransferMethod'
		);
		$this->mResponse = $this->getMock(
			'\\Suburb\\HttpRequest\\Response'
		);
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mTransferMethod = null;
		$this->mResponse = null;
		parent::tearDown();
	}
	
	public function testRequestObjectCanBeInstantiated()
	{
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertInstanceOf('\\Suburb\\HttpRequest', $request);
	}
	
	public function testGetMethodExists()
	{
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertTrue(method_exists($request, 'get'));
	}
	
	public function testPostMethodExists()
	{
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertTrue(method_exists($request, 'post'));
	}
	
	public function testPutMethodExists()
	{
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertTrue(method_exists($request, 'put'));
	}
	
	public function testDeleteMethodExists()
	{
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertTrue(method_exists($request, 'delete'));
	}
	
	public function testGetCallsTransferMethodOnceWithPathVerbAndResponse()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('http://somewhere.com'),
				$this->equalTo('GET'),
				$this->isInstanceOf('\Suburb\HttpRequest\Response')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->get('http://somewhere.com');
	}
	
	public function testPostCallsTransferMethodOnceWithPathVerbAndResponse()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('http://somewhere.com'),
				$this->equalTo('POST'),
				$this->isInstanceOf('\Suburb\HttpRequest\Response')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->post('http://somewhere.com', 'data');
	}
	
	public function testPutCallsTransferMethodOnceWithPathVerbAndResponse()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('http://somewhere.com'),
				$this->equalTo('PUT'),
				$this->isInstanceOf('\Suburb\HttpRequest\Response')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->put('http://somewhere.com', 'data');
	}
	
	public function testDeleteCallsTransferMethodOnceWithPathVerbAndResponse()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('http://somewhere.com'),
				$this->equalTo('DELETE'),
				$this->isInstanceOf('\Suburb\HttpRequest\Response')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->delete('http://somewhere.com');
	}
	
	public function testBasePathCanBePassedToConstructorAndIsIncludedInRequest()
	{
		$this->mTransferMethod
			->expects($this->once())
			->method('request')
			->with(
				$this->stringContains('http://api.somewhere.com')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			'http://api.somewhere.com'
		);
		$request->get('route');
	}
	
	public function testPathsAreAlwaysSeparatedWithOneForwardSlash()
	{
		$this->mTransferMethod
			->expects($this->exactly(4))
			->method('request')
			->with(
				$this->equalTo('http://api.somewhere.com/route')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			'http://api.somewhere.com'
		);
		$request->get('route');
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			'http://api.somewhere.com/'
		);
		$request->get('/route');
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			'http://api.somewhere.com/'
		);
		$request->get('route');
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			'http://api.somewhere.com'
		);
		$request->get('/route');
	}
	
	public function testHeadersPassedToGetArePassedToTransferMethod()
	{
		$authHeader = ['Authorization' => 'auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($authHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->get('/route', $authHeader);
	}
	
	public function testHeadersPassedToPostArePassedToTransferMethod()
	{
		$authHeader = ['Authorization' => 'auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($authHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->post('/route', 'data', $authHeader);
	}
	
	public function testHeadersPassedToPutArePassedToTransferMethod()
	{
		$authHeader = ['Authorization' => 'auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($authHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->put('/route', 'data', $authHeader);
	}
	
	public function testHeadersPassedToDeleteArePassedToTransferMethod()
	{
		$authHeader = ['Authorization' => 'auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($authHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->delete('/route', $authHeader);
	}
	
	public function testHeadersPassedToConstructorArePassedToTransferMethod()
	{
		$authHeader = ['Authorization' => 'auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($authHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			null,
			$authHeader
		);
		$request->get('/route');
	}
	
	public function testHeadersPassedToConstructorAndRequestAreMerged()
	{
		$constructorHeader = ['Authorization' => 'auth_info'];
		$requestHeader = ['Content-type' => 'content_type'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo([
					'Authorization'	=> 'auth_info',
					'Content-type'	=> 'content_type'
				])
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			null,
			$constructorHeader
		);
		$request->get('/route', $requestHeader);
	}
	
	public function testHeadersPassedToConstructorAreOverwrittenAtRequest()
	{
		$originalHeader = ['Authorization' => 'auth_info'];
		$newHeader = ['Authorization' => 'new_auth_info'];
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo($newHeader)
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			null,
			$originalHeader
		);
		$request->get('/route', $newHeader);
	}
	
	public function testDataPassedToPostIsJsonEncodedAndPassedToTransferMethod()
	{
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo('{"key":"value"}')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->post('/route', ['key' => 'value']);
	}
	
	public function testDataPassedToPutIsJsonEncodedAndPassedToTransferMethod()
	{
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo('{"key":"value"}')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->put('/route', ['key' => 'value']);
	}
	
	public function testDataCanBeNotEncodedOnRequest()
	{
		$this->mTransferMethod
			->expects($this->any())
			->method('request')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo('data_string')
			);
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			null,
			[],
			false
		);
		$request->put('/route', 'data_string');
	}
	
	public function testOtherEncodingTypesAreRejectedWithException()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse,
			null,
			[],
			'invalid'
		);
	}
	
	public function testResponseObjectMustBeInitialisedByTransferMethod()
	{
		$this->setExpectedException('\\RuntimeException');
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(false));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$request->get('/route');
	}
	
	public function testResponseObjectIsReturnedFromGetRequest()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertSame($this->mResponse, $request->get('/route'));
	}
	
	public function testResponseObjectIsReturnedFromPostRequest()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertSame($this->mResponse, $request->post('/route', 'data'));
	}
	
	public function testResponseObjectIsReturnedFromPutRequest()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertSame($this->mResponse, $request->put('/route', 'data'));
	}
	
	public function testResponseObjectIsReturnedFromDeleteRequest()
	{
		$this->mResponse
			->expects($this->any())
			->method('isInitialised')
			->will($this->returnValue(true));
		$request = new HttpRequest(
			$this->mTransferMethod,
			$this->mResponse
		);
		$this->assertSame($this->mResponse, $request->delete('/route'));
	}
	
}