<?php

namespace PO\Application;

require_once dirname(__FILE__) . '/Response.php';

/**
 * @runTestsInSeparateProcesses
 */
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
		$response = new Response();
		$this->assertInstanceOf('\\PO\\Application\\Response', $response);
	}
	
	public function testResponseCanBeInitialisedWithCodeAndBody()
	{
		$response = new Response();
		$response->set200('Everything is ok');
	}
	
	public function testInitialisedResponseCanBeProcessedToOutputBody()
	{
		$response = new Response();
		$response->set200('Everything is ok');
		ob_start();
		$response->process();
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Everything is ok', $output);
	}
	
	public function testResponseCannotBeProcessedBeforeItIsInitialised()
	{
		$this->setExpectedException('\\RuntimeException');
		$response = new Response();
		$response->process();
	}
	
	public function testProcessingOutputsRelevantHeader()
	{
		// @todo ...issues testing headers
	}
	
	public function testProcessingOutputsRelevantResponseCode()
	{
		// @todo ...issues testing headers
	}
	
	public function testResponseCanOnlyBeInitialisedOnce()
	{
		$this->setExpectedException('\\RuntimeException');
		$response = new Response();
		$response->set200('Everything is ok');
		$response->set500('Actually, it\'s not ok');
	}
	
	public function testResponseCanReportIfItIsInitialised()
	{
		$response = new Response();
		$this->assertFalse($response->isInitialised());
		$response->set200('Everything is ok');
		$this->assertTrue($response->isInitialised());
	}
	
	public function testRepresentationIsConvertedToJsonIfItIsAnArray()
	{
		$response = new Response();
		$response->set200(['key' => 'value']);
		ob_start();
		$response->process();
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('{"key":"value"}', $output);
		
	}
	
	public function testRepresentationIsNotConvertedIfItIsAString()
	{
		$response = new Response();
		$response->set200('representation');
		ob_start();
		$response->process();
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('representation', $output);
	}
	
	// @todo Tests regarding content-type
	
}