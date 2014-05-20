<?php

namespace PO\Application\Bootstrap;

use PO\IoCContainer;
use PO\Application\IBootstrap;

class AccessController
implements IBootstrap
{
	
	private $rules;
	
	public function __construct(array $rules = [])
	{
		$this->rules = $rules;
	}
	
	public function run(IoCContainer $ioCContainer)
	{
		
		$authenticator = $ioCContainer->resolve('PO\Application\Bootstrap\Authenticator');
		
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
