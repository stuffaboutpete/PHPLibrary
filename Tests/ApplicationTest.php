<?php

namespace PO\Tests;

use PO\IoCContainer;

require_once 'vfsStream/vfsStream.php';

class ApplicationTest
extends \PHPUnit_Framework_TestCase {
	
	private $virtualDir;
	
	public function setUp()
	{
		$this->virtualDir = \vfsStream::setup('POTestApplicationTest', null, [
			'JustTemplate' => [
				'Index.phtml' => 'Hello, World!'
			],
			'JustController' => [
				'Index.php' => '<?php namespace POTestApplication\JustController; ' .
				'class Index extends \PO\Application\Dispatchable\Mvc\Controller {' .
					'public function dispatch(){ echo \'Hello, Controller!\'; }' .
				'}'
			]
		]);
		parent::setUp();
	}
	
	public function tearDown()
	{
		unset($this->virtualDir);
		parent::tearDown();
	}
	
	public function testMvcApplicationCanBeResolvedAndDispatched()
	{
		// @todo Get rid of this when we abstract request away from Mvc
		$_SERVER['REQUEST_URI'] = '/';
		$mResponse = $this->getMock('PO\Http\Response');
		$mResponse
			->expects($this->at(0))
			->method('set200')
			->with('Hello, World!');
		$mResponse
			->expects($this->at(1))
			->method('isInitialised')
			->will($this->returnValue(true));
		$mResponse
			->expects($this->at(2))
			->method('process');
		$ioCContainer = new IoCContainer(new IoCContainer\Containment\All());
		$ioCContainer->registerSingleton($mResponse);
		$application = $ioCContainer->resolve(
			'PO\Application\Mvc',
			[
				'templateDirectory'	=> \vfsStream::url('POTestApplicationTest/JustTemplate'),
				// @todo Containment only accepts response so we can do this. Would be
				// better if we could provide the response as an IoC singleton instead maybe?
				'response'			=> $mResponse
			]
		);
		$application->run();
	}
	
	public function testMvcApplicationControllerCanBeDispatched()
	{
		$_SERVER['REQUEST_URI'] = '/';
		$mResponse = $this->getMock('PO\Http\Response');
		$mResponse
			->expects($this->at(0))
			->method('set200')
			->with('Hello, Controller!');
		$mResponse
			->expects($this->at(1))
			->method('isInitialised')
			->will($this->returnValue(true));
		$mResponse
			->expects($this->at(2))
			->method('process');
		$ioCContainer = new IoCContainer(new IoCContainer\Containment\All());
		$ioCContainer->registerSingleton($mResponse);
		$application = $ioCContainer->resolve(
			'PO\Application\Mvc',
			[
				'controllerNamespace'	=> 'POTestApplication\JustController',
				'response'				=> $mResponse
			]
		);
		include \vfsStream::url('POTestApplicationTest\JustController\Index.php');
		$application->run();
	}
	
}
