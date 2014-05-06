<?php

namespace Suburb\Application\Bootstrap;

use Suburb\Application;
use Suburb\Application\IBootstrap;
use Suburb\Application\Bootstrap\AccessController\Exception;

class AccessController
implements IBootstrap
{
	
	private $rules;
	
	public function __construct(array $rules = [])
	{
		$this->rules = $rules;
	}
	
	public function run(Application $application)
	{
		
		if (!$application->hasExtension('authenticator')) {
			throw new Exception(Exception::AUTHENTICATOR_DOES_NOT_EXIST_AS_APPLICATION_EXTENSION);
		}
		
		$authenticator = $application->getAuthenticator();
		
		foreach ($this->rules as $pattern => $definition) {
			if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
				foreach ($definition['requiredAccessTypes'] as $accessType) {
					if (!$authenticator->userCanAccess($accessType)) {
						header('Location: ' . $definition['redirect']);
						exit;
					}
				}
			}
		}
		
	}
	
}
