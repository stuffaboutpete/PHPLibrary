<?php

namespace PO\HttpRequest;

require_once dirname(__FILE__) . '/Response.php';

class ResponseTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testResponseCanBeInstantiated()
	{
		$response = new \PO\HttpRequest\Response();
		$this->assertInstanceOf('\\PO\\HttpRequest\\Response', $response);
	}
	
	public function testResponseCanBeInitialisedWithCodeAndContentType()
	{
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'text/html');
	}
	
	public function testInitialisationRejectsInvalidResponseCodes()
	{
		$this->setExpectedException('\\InvalidArgumentException');
		$response = new \PO\HttpRequest\Response();
		$response->initialise(999, 'text/html');
	}
	
	public function testResponseCanOnlyBeInitialisedOnce()
	{
		$this->setExpectedException('\\RuntimeException');
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'text/html');
		$response->initialise(201, 'text/html');
	}
	
	public function testResponseCodeAndContentTypeCanBeRetrieved()
	{
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'text/html');
		$this->assertEquals(200, $response->getCode());
		$this->assertEquals('text/html', $response->getContentType());
	}
	
	public function testBodyCanBeSetOnInitialisationAndRecalled()
	{
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'text/html', 'some_data');
		$this->assertEquals('some_data', $response->getBody());
	}
	
	public function testGetBodyReturnsDecodedBodyIfAvailable()
	{
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'application/json', '{"key":"value"}');
		$this->assertEquals(['key' => 'value'], $response->getBody());
	}
	
	public function testRawBodyCanAlsoBeRetrieved()
	{
		$response = new \PO\HttpRequest\Response();
		$response->initialise(200, 'application/json', '{"key":"value"}');
		$this->assertEquals('{"key":"value"}', $response->getRawBody());
	}
	
}