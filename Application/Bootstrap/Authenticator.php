<?php

namespace PO\Application\Bootstrap;

use PO\Application\Bootstrap\Authenticator\Exception;
use PO\Application\IBootstrap;
use PO\Helper\Cookie as CookieHelper;
use PO\IoCContainer;

class Authenticator
implements IBootstrap
{
	
	private $authenticatedUser;
	private $cookieHelper;
	private $cookiesSetDuringThisCall = [];
	private $databaseAccessTypesGivenThisCall = [];
	private $pdo;
	private $ioCContainer;
	
	public function __construct(CookieHelper $cookieHelper, \PDO $pdo = null)
	{
		$this->cookieHelper = $cookieHelper;
		$this->pdo = $pdo;
	}
	
	public function run(IoCContainer $ioCContainer)
	{
		$ioCContainer->registerSingleton($this);
		$this->ioCContainer = $ioCContainer;
	}
	
	/**
	 * Test user is registered and has
	 * provided some id (token or
	 * authenticateUser() pass)
	 */
	public function userIsAuthenticated()
	{
		
		if (!isset($this->authenticatedUser) && $accessToken = $this->accessTokenIsProvided()) {
			
			$selectUserStatement = $this->getPdo()->prepare(
				'SELECT id FROM auth_user WHERE access_token = :access_token'
			);
			
			$selectUserStatement->execute(['access_token' => $accessToken]);
			
			$user = $selectUserStatement->fetch(\PDO::FETCH_ASSOC);
			
			if ($user) $this->authenticatedUser = [
				'id'			=> $user['id'],
				'access_token'	=> $accessToken
			];
			
			// @todo If access token has run out...
			
		}
		
		return isset($this->authenticatedUser);
		
	}
	
	private function accessTokenIsProvided()
	{
		if (isset($_COOKIE['access_token'])) return $_COOKIE['access_token'];
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
		return false;
	}
	
	public function identityIsRegistered($identifier)
	{
		if (isset($this->authenticatedUser)) return true;
		$statement = $this->getPdo()->prepare(
			'SELECT id FROM auth_user WHERE identifier = :identifier'
		);
		$statement->execute(['identifier' => $identifier]);
		return ($statement->rowCount() == 1);
	}
	
	public function assignAccessToUser($accessType)
	{
		if ($this->userIsAuthenticated()) {
			
			$giveAccessStatement = $this->getPdo()->prepare(
				'INSERT INTO auth_user_access_type (auth_user_id, access_type) ' .
				'VALUES (:user_id, :access_type)'
			);
			
			$giveAccessStatement->execute([
				'user_id'		=> $this->authenticatedUser['id'],
				'access_type'	=> $accessType
			]);
			
			array_push($this->databaseAccessTypesGivenThisCall, $accessType);
			
		} else {
			
			$this->cookieHelper->set('access_type_' . $accessType, strval($accessType));
			$this->cookiesSetDuringThisCall['access_type_' . $accessType] = strval($accessType);
			
		}
	}
	
	public function userCanAccess($accessType, $authenticationRequired = true)
	{
		if ($this->userIsAuthenticated()) {
			
			if (in_array($accessType, $this->databaseAccessTypesGivenThisCall)) return true;
			
			$hasAccessStatement = $this->getPdo()->prepare(
				'SELECT auth_user_access_type.access_type FROM auth_user_access_type ' .
				'INNER JOIN auth_user ON auth_user_access_type.auth_user_id = auth_user.id ' .
				'WHERE auth_user.id = :user_id ' .
				'AND auth_user_access_type.access_type = :access_type'
			);
			
			$hasAccessStatement->execute([
				'user_id'		=> $this->authenticatedUser['id'],
				'access_type'	=> $accessType
			]);
			
			return $hasAccessStatement->rowCount() == 1;
			
		} else {
			
			$cookies = $this->getAllCookies();
			$cookieKey = 'access_type_' . $accessType;
			return isset($cookies[$cookieKey]) && $cookies[$cookieKey] === strval($accessType);
			
		}
	}
	
	public function registerUser($identifier, $password = null)
	{
		
		if ($this->identityIsRegistered($identifier)) {
			throw new Exception(
				Exception::USER_IS_ALREADY_REGISTERED,
				"Identifier: $identifier"
			);
		}
		
		if (isset($password)) {
			
			$insertStatement = $this->getPdo()->prepare(
				'INSERT INTO auth_user (identifier, password_hash) ' .
				'VALUES (:identifier, :password_hash)'
			);
			
			$insertStatement->execute([
				'identifier'	=> $identifier,
				'password_hash'	=> password_hash($password, PASSWORD_DEFAULT)
			]);
			
		} else {
			
			$insertStatement = $this->getPdo()->prepare(
				'INSERT INTO auth_user (identifier) VALUES (:identifier)'
			);
			
			$insertStatement->execute(['identifier' => $identifier]);
			
		}
		
		$newUserId; // Will be set below if needed...
		
		foreach ($this->getAccessCookies() as $key => $value) {
			
			if (!isset($newUserId)) $newUserId = $this->getPdo()->lastInsertId();
			
			$this->cookieHelper->revoke($key, $value);
			
			$giveAccessStatement = $this->getPdo()->prepare(
				'INSERT INTO auth_user_access_type (auth_user_id, access_type) ' .
				'VALUES (:user_id, :access_type)'
			);
			
			$giveAccessStatement->execute([
				'user_id'		=> $newUserId,
				'access_type'	=> $value
			]);
			
		}
		
	}
	
	public function authenticateUser($identifier, $password = null)
	{
		
		$selectUserStatement = $this->getPdo()->prepare(
			'SELECT id, password_hash FROM auth_user WHERE identifier = :identifier'
		);
		
		$selectUserStatement->execute(['identifier' => $identifier]);
		
		$user = $selectUserStatement->fetch(\PDO::FETCH_ASSOC);
		
		// @todo If no user...
		
		if (!is_null($user['password_hash'])) {
			
			// @todo If no password provided...
			
			if (!password_verify($password, $user['password_hash'])) {
				throw new Exception(Exception::INCORRECT_PASSWORD_SUPPLIED);
			}
			
		}
		
		$accessToken = $this->generateAccessToken();
		
		$saveAccessTokenStatement = $this->getPdo()->prepare(
			'UPDATE auth_user SET access_token = :access_token WHERE id = :id'
		);
		
		$saveAccessTokenStatement->execute([
			'id'			=> $user['id'],
			'access_token'	=> $accessToken
		]);
		
		$this->authenticatedUser = [
			'id'			=> $user['id'],
			'access_token'	=> $accessToken
		];
		
		$this->cookieHelper->set('access_token', $accessToken);
		
		return true;
		
	}
	
	private function generateAccessToken()
	{
		return hash('sha512', mt_rand());
	}
	
	public function getAccessToken()
	{
		// @todo Throw exception if no authed user
		return $this->authenticatedUser['access_token'];
	}
	
	private function getAllCookies()
	{
		return array_merge($_COOKIE, $this->cookiesSetDuringThisCall);
	}
	
	private function getAccessCookies()
	{
		$accessCookies = [];
		foreach ($this->getAllCookies() as $key => $value) {
			if (substr($key, 0, 12) == 'access_type_') $accessCookies[$key] = $value;
		}
		return $accessCookies;
	}
	
	private function getPdo()
	{
		if (!isset($this->pdo)) {
			try {
				$this->pdo = $this->ioCContainer->resolve('PDO');
			} catch (\RuntimeException $exception) {
				// RuntimeException indicates that the
				// ioc container doesn't have a pdo
				// singleton and has tried to create one
				throw new Exception(Exception::NO_PDO_CONNECTION_IS_AVAILABLE);
			}
		}
		return $this->pdo;
	}
	
}
