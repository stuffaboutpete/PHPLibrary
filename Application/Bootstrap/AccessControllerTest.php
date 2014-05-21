<?php

namespace PO\Application\Bootstrap;

require_once dirname(__FILE__) . '/../../Application.php';
require_once dirname(__FILE__) . '/../IBootstrap.php';
require_once dirname(__FILE__) . '/AccessController.php';
require_once dirname(__FILE__) . '/../../Exception.php';
//require_once dirname(__FILE__) . '/AccessController/Exception.php';

class AccessControllerTest
extends \PHPUnit_Framework_TestCase {
	
	private $mApplication;
	
	public function setUp()
	{
		$this->mApplication = $this->getMockBuilder('\PO\Application')
			->disableOriginalConstructor()
			->getMock();
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mApplication = null;
		parent::tearDown();
	}
	
	public function testAccessControllerBootstrapCanBeInstantiated()
	{
		// $accessControllerBootstrap = new AccessController();
		// $this->assertInstanceOf(
		// 	'\PO\Application\Bootstrap\AccessController',
		// 	$accessControllerBootstrap
		// );
	}
	
	public function testAuthenticatorApplicationExtensionMustExist()
	{
		// $this->setExpectedException('\PO\Application\Bootstrap\AccessController\Exception');
		// $this->mApplication
		// 	->expects($this->once())
		// 	->method('hasExtension')
		// 	->with('authenticator')
		// 	->will($this->returnValue(false));
		// $accessController = new AccessController();
		// $accessController->run($this->mApplication);
	}
	
	public function testTEMP()
	{
		// $accessController = new AccessController([
		// 	'/^index/' => [
		// 		'redirect'				=> '/index',
		// 		'requiredPermisssions'	=> [1, 2, 3]
		// 	]
		// ]);
	}
	
	/**
	 * Authenticator must exist on application
	 * Should accept array of arrays
	 * Array must be associative
	 * Each key is regex for path
	 * Each array contains keys redirect and requiredAccessTypes
	 * Access types are integers
	 * Redirects to path if fails test
	 */
	
}
