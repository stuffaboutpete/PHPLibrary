<?php

namespace PO\Application\Bootstrap\Authenticator;

class Exception
extends \PO\Exception
{
	
	const NO_PDO_CONNECTION_IS_AVAILABLE	= 0;
	const USER_IS_ALREADY_REGISTERED		= 1;
	const INCORRECT_PASSWORD_SUPPLIED		= 2;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NO_PDO_CONNECTION_IS_AVAILABLE:
				return 'No PDO connection could be found either through ' .
					'the constructor or via an IoC singleton';
			break;
			
			case Exception::USER_IS_ALREADY_REGISTERED:
				return 'An attempt was made to register an already registered user';
			break;
			
			case Exception::INCORRECT_PASSWORD_SUPPLIED:
				return 'A password was supplied but it could not be verified against a stored hash';
			break;
			
		}
		
	}
	
}
