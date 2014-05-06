<?php

namespace Suburb\Application\Bootstrap;

require_once dirname(__FILE__) . '/../IBootstrap.php';
require_once dirname(__FILE__) . '/Authenticator.php';
require_once dirname(__FILE__) . '/../../Exception.php';
require_once dirname(__FILE__) . '/Authenticator/Exception.php';
require_once dirname(__FILE__) . '/../../Helper/Cookie.php';

class AuthenticatorTest
extends \PHPUnit_Framework_TestCase {
	
	private $mCookieHelper;
	private $mPdo;
	private $mPdoStatement;
	
	public function setUp()
	{
		$this->mCookieHelper = $this->getMock('\Suburb\Helper\Cookie');
		$this->mPdo = $this->getMock('\Suburb\Application\Bootstrap\AuthenticatorTestPDO');
		$this->mPdoStatement = $this->getMock('\PDOStatement');
		parent::setUp();
	}
	
	public function tearDown()
	{
		$this->mCookieHelper = null;
		$this->mPdo = null;
		$this->mPdoStatement = null;
		parent::tearDown();
	}
	
	public function testAuthenticatorBootstrapCanBeInstantiated()
	{
		$authenticator = new Authenticator($this->mCookieHelper);
		$this->assertInstanceOf('\Suburb\Application\Bootstrap\Authenticator', $authenticator);
	}
	
	public function testUserDoesNotHavePrivilegesForSomeAccessTypeByDefault()
	{
		$authenticator = new Authenticator($this->mCookieHelper);
		$this->assertFalse($authenticator->userCanAccess(123, false));
	}
	
	public function testUserCanBeAssignedAnAccessTypeIdentifiedByAnIntegerAndACookieIsSet()
	{
		$this->mCookieHelper
			->expects($this->once())
			->method('set')
			->with(
				$this->equalTo('access_type_123'),
				$this->identicalTo('123')
			);
		$authenticator = new Authenticator($this->mCookieHelper);
		$authenticator->assignAccessToUser(123);
	}
	
	public function testUserDoesHavePrivilegesForAccessTypeIfCookieIsSet()
	{
		$_COOKIE['access_type_123'] = '123';
		$authenticator = new Authenticator($this->mCookieHelper);
		$this->assertTrue($authenticator->userCanAccess(123, false));
	}
	
	public function testUserHasImmediateAccessUponGrantingPrivileges()
	{
		$authenticator = new Authenticator($this->mCookieHelper);
		$authenticator->assignAccessToUser(123);
		$this->assertTrue($authenticator->userCanAccess(123, false));
	}
	
	public function testMultiplePrivilegesCanBeGrantedByCookie()
	{
		$this->mCookieHelper
			->expects($this->at(0))
			->method('set')
			->with(
				$this->equalTo('access_type_123'),
				$this->identicalTo('123')
			);
		$this->mCookieHelper
			->expects($this->at(1))
			->method('set')
			->with(
				$this->equalTo('access_type_321'),
				$this->identicalTo('321')
			);
		$authenticator = new Authenticator($this->mCookieHelper);
		$this->assertFalse($authenticator->userCanAccess(123, false));
		$this->assertFalse($authenticator->userCanAccess(321, false));
		$authenticator->assignAccessToUser(123);
		$authenticator->assignAccessToUser(321);
		$this->assertTrue($authenticator->userCanAccess(123, false));
		$this->assertTrue($authenticator->userCanAccess(321, false));
	}
	
	public function testUserCanBeRegisteredInADatabaseIfDoesNotAlreadyExist()
	{
		
		// Check no user exists
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('rowCount')
			->will($this->returnValue(0));
		
		// Save user
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('INSERT INTO auth_user (identifier) VALUES (:identifier)')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument){
				if (!isset($argument['identifier'])
				||	$argument['identifier'] != 'example@identifier.com') return false;
				return true;
			}));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->registerUser('example@identifier.com');
		
	}
	
	public function testReRegisteringAUserResultsInAnException()
	{
		
		$this->setExpectedException('\Suburb\Application\Bootstrap\Authenticator\Exception');
		
		// Check user exists
		$this->mPdo
			->expects($this->once())
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->once())
			->method('rowCount')
			->will($this->returnValue(1));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->registerUser('example@identifier.com');
		
	}
	
	public function testRegisteredUserCanHaveAccessTypeGrantedAndItIsStoredInDatabase()
	{
		
		// Check no user exists
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('rowCount')
			->will($this->returnValue(0));
		
		// Add user
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('INSERT INTO auth_user (identifier) VALUES (:identifier)')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument){
				if (!isset($argument['identifier'])
				||	$argument['identifier'] != 'example@identifier.com') return false;
				return true;
			}));
		
		// Authenticate user
		$this->mPdo
			->expects($this->at(2))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('execute')
			->with(['identifier' => 'example@identifier.com'])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(4))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(['id' => 10, 'password_hash' => null]));
		$this->mPdo
			->expects($this->at(3))
			->method('prepare')
			->with('UPDATE auth_user SET access_token = :access_token WHERE id = :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(5))
			->method('execute');
		
		// Save permission
		$this->mPdo
			->expects($this->at(4))
			->method('prepare')
			->with(
				'INSERT INTO auth_user_access_type (auth_user_id, access_type) ' .
				'VALUES (:user_id, :access_type)'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(6))
			->method('execute')
			->with([
				'user_id'		=> 10,
				'access_type'	=> 123
			]);
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$this->assertFalse($authenticator->userCanAccess(123));
		$authenticator->registerUser('example@identifier.com');
		$authenticator->authenticateUser('example@identifier.com');
		$authenticator->assignAccessToUser(123);
		$this->assertTrue($authenticator->userCanAccess(123));
		
	}
	
	public function testAuthenticatorChecksDatabaseForAccessTypeForRegisteredUser()
	{
		
		// Get user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(['id' => 10, 'password_hash' => null]));
		
		// Set access token
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('UPDATE auth_user SET access_token = :access_token WHERE id = :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute');
		
		// Check the user has access
		$this->mPdo
			->expects($this->at(2))
			->method('prepare')
			->with(
				'SELECT auth_user_access_type.access_type FROM auth_user_access_type ' .
				'INNER JOIN auth_user ON auth_user_access_type.auth_user_id = auth_user.id ' .
				'WHERE auth_user.id = :user_id ' .
				'AND auth_user_access_type.access_type = :access_type'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('execute')
			->with([
				'user_id'		=> 10,
				'access_type'	=> 234
			])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(4))
			->method('rowCount')
			->will($this->returnValue(1));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->authenticateUser('example@identifier.com');
		$this->assertTrue($authenticator->userCanAccess(234));
		
	}
	
	public function testAuthenticatingUserAddsAccessTokenToCookie()
	{
		
		// Get user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id'			=> 10,
				'password_hash'	=> null,
				'access_token'	=> null
			]));
		
		// Set access token
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('UPDATE auth_user SET access_token = :access_token WHERE id = :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument){
				return (preg_match('/[a-z0-9]{128}/', $argument['access_token']));
			}));
		
		// Set access token cookie
		$this->mCookieHelper
			->expects($this->once())
			->method('set')
			->with(
				$this->equalTo('access_token'),
				$this->callback(function($argument){
					return (preg_match('/[a-z0-9]{128}/', $argument));
				})
			);
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->authenticateUser('example@identifier.com');
		
	}
	
	public function testUserIsIdentifiedByAccessTokenCookie()
	{
		
		$_COOKIE['access_token'] = '12345';
		
		// Get the stored user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE access_token = :access_token')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with(['access_token' => '12345'])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(['id' => 10, 'password_hash' => null]));
		
		// Check the user has access
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with(
				'SELECT auth_user_access_type.access_type FROM auth_user_access_type ' .
				'INNER JOIN auth_user ON auth_user_access_type.auth_user_id = auth_user.id ' .
				'WHERE auth_user.id = :user_id ' .
				'AND auth_user_access_type.access_type = :access_type'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with([
				'user_id'		=> 10,
				'access_type'	=> 123
			])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('rowCount')
			->will($this->returnValue(1));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$this->assertTrue($authenticator->userIsAuthenticated());
		$this->assertTrue($authenticator->userCanAccess(123));
		
	}
	
	public function testAccessTokenCanBeAccessedForAuthenticatedUser()
	{
		
		// Get user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id'			=> 10,
				'password_hash'	=> null,
				'access_token'	=> null
			]));
		
		$accessToken;
		
		// Set access token
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('UPDATE auth_user SET access_token = :access_token WHERE id = :id')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument) use (&$accessToken){
				$accessToken = $argument['access_token'];
				return (preg_match('/[a-z0-9]{128}/', $argument['access_token']));
			}));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->authenticateUser('example@identifier.com');
		$this->assertEquals($accessToken, $authenticator->getAccessToken());
		
	}
	
	public function testUserIsIdentifiedByAccessTokenInAuthorizationHeader()
	{
		
		$_SERVER['HTTP_AUTHORIZATION'] = '12345';
		
		// Get the stored user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE access_token = :access_token')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with(['access_token' => '12345'])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue(['id' => 10, 'password_hash' => null]));
		
		// Check the user has access
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with(
				'SELECT auth_user_access_type.access_type FROM auth_user_access_type ' .
				'INNER JOIN auth_user ON auth_user_access_type.auth_user_id = auth_user.id ' .
				'WHERE auth_user.id = :user_id ' .
				'AND auth_user_access_type.access_type = :access_type'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with([
				'user_id'		=> 10,
				'access_type'	=> 123
			])
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('rowCount')
			->will($this->returnValue(1));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$this->assertTrue($authenticator->userIsAuthenticated());
		$this->assertTrue($authenticator->userCanAccess(123));
		
	}
	
	public function testHashedVersionOfPasswordIsSuppliedToDatabaseIfPasswordIsProvided()
	{
		
		// Check no user exists
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('rowCount')
			->will($this->returnValue(0));
		
		// Save user
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with(
				'INSERT INTO auth_user (identifier, password_hash) ' .
				'VALUES (:identifier, :password_hash)'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument){
				if (!isset($argument['identifier'])
				||	$argument['identifier'] != 'example@identifier.com') return false;
				if (!isset($argument['password_hash'])
				||	!preg_match('/[$.\/A-Za-z0-9]{60}/', $argument['password_hash'])) return false; 
				return true;
			}));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->registerUser('example@identifier.com', 'password');
		
	}
	
	public function testAuthenticatingUserRequiresPasswordIfItHasBeenSet()
	{
		
		// Get user
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id'			=> 10,
				'password_hash'	=> password_hash('password', PASSWORD_DEFAULT),
				'access_token'	=> null
			]));
		
		// Save access token
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->will($this->returnValue($this->mPdoStatement));
		
		// Get user second time
		$this->mPdo
			->expects($this->at(2))
			->method('prepare')
			->with('SELECT id, password_hash FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(4))
			->method('fetch')
			->with(\PDO::FETCH_ASSOC)
			->will($this->returnValue([
				'id'			=> 10,
				'password_hash'	=> password_hash('password', PASSWORD_DEFAULT),
				'access_token'	=> null
			]));
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$this->assertTrue($authenticator->authenticateUser('example@identifier.com', 'password'));
		
		$this->setExpectedException('\Suburb\Application\Bootstrap\Authenticator\Exception');
		$authenticator->authenticateUser('example@identifier.com');
		
	}
	
	public function testCookiePermissionsAreMovedToDatabaseOnRegisteringUser()
	{
		
		$_COOKIE['access_type_123'] = '123';
		
		// Check no user exists
		$this->mPdo
			->expects($this->at(0))
			->method('prepare')
			->with('SELECT id FROM auth_user WHERE identifier = :identifier')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(0))
			->method('execute')
			->with($this->equalTo(['identifier' => 'example@identifier.com']))
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(1))
			->method('rowCount')
			->will($this->returnValue(0));
		
		// Save user
		$this->mPdo
			->expects($this->at(1))
			->method('prepare')
			->with('INSERT INTO auth_user (identifier) VALUES (:identifier)')
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(2))
			->method('execute')
			->with($this->callback(function($argument){
				if (!isset($argument['identifier'])
				||	$argument['identifier'] != 'example@identifier.com') return false;
				return true;
			}));
		$this->mPdo
			->expects($this->at(2))
			->method('lastInsertId')
			->will($this->returnValue(10));
		
		// Save permission
		$this->mPdo
			->expects($this->at(3))
			->method('prepare')
			->with(
				'INSERT INTO auth_user_access_type (auth_user_id, access_type) ' .
				'VALUES (:user_id, :access_type)'
			)
			->will($this->returnValue($this->mPdoStatement));
		$this->mPdoStatement
			->expects($this->at(3))
			->method('execute')
			->with([
				'user_id'		=> 10,
				'access_type'	=> 123
			]);
		
		$this->mCookieHelper
			->expects($this->once())
			->method('revoke')
			->with('access_type_123', '123');
		
		$authenticator = new Authenticator($this->mCookieHelper, $this->mPdo);
		$authenticator->registerUser('example@identifier.com');
		
	}
	
	/**
	 * Should
	 * 
	 *	User is not registered
	 *	User cannot access something denoted by some integer
	 *	Can assignAccessToUser with an integer and cookie is set
	 *	If cookie is set, user can access said something
	 *	Multiple cookies are set for multiple access types
	 *	User can be registered and stored in db
	 *	Access token is set in a cookie
	 *	Giving registered user access results in database storing
	 * As registered, any cookies are removed and added as database registrations
	 *	Registered user can access said something
	 * Password is hashed if provided
	 *	User gets cookie access immediately
	 * User gets database access immediately
	 * Cookie helper is required
	 * PDO should be required if used
	 * Make cookie more secure
	 * Error thrown if registering already registered user
	 * Check identifier is a string
	 * Test second argument of userCanAccess()
	 * Can get access token (for js requests for example)
	 * Remove pdo to constructor and test for it being an application extension
	 */
	
}

class AuthenticatorTestPDO extends \PDO
{
	public function __construct(){}
}
