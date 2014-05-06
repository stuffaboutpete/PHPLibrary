<?php

namespace Suburb\Application\Bootstrap\Authenticator;

use Suburb\Application;
use Suburb\Application\Bootstrap\Authenticator;

require_once LIBRARY_PATH . '/Facebook/src/facebook.php';

class Facebook
extends Authenticator
{
	
	private $facebookObject;
	private $appId;
	private $secret;
	
	public function run(Application $application)
	{
		
		parent::run($application);
		
		$config = $application->getConfig();
		
		$this->appId = $config->get('facebook/app_id');
		$this->secret = $config->get('facebook/secret');
		
		if (isset($_GET['code'])) {
			
			$userId = $this->getFacebookObject()->getUser();
			
			if (!$this->identityIsRegistered("fb_$userId")) {
				$this->registerUser("fb_$userId");
			}
			
			$this->authenticateUser("fb_$userId");
			
		}
		
	}
	
	public function getLoginUrl($redirectUri, $permissionsList = 'read_stream')
	{
		return $this->getFacebookObject()->getLoginUrl([
			'redirect_uri'	=> 'https://' . $_SERVER['HTTP_HOST'] . $redirectUri,
			'scope'			=> $permissionsList
		]);
	}
	
	public function getFacebookObject()
	{
		if (!isset($this->facebookObject)) {
			$this->facebookObject = new \Facebook([
				'appId'		=> $this->appId,
				'secret'	=> $this->secret
			]);
		}
		return $this->facebookObject;
	}
	
	public function isLoggedInToFacebook()
	{
		if (!isset($this->facebookObject)) return false;
		$loggedIn;
		try {
			$me = $this->facebookObject->api('/me');
			$loggedIn = $me ? true : false;
		} catch (FacebookApiException $exception) {
			$loggedIn = false;
		}
		return $loggedIn;
	}
	
}
