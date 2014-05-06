<?php

namespace PO\Application\Bootstrap\Authenticator;

class Exception
extends \PO\Exception
{
	
	const USER_IS_ALREADY_REGISTERED	= 0;
	const INCORRECT_PASSWORD_SUPPLIED	= 1;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::USER_IS_ALREADY_REGISTERED:
				return 'An attempt was made to register an already registered user';
			break;
			
			case Exception::INCORRECT_PASSWORD_SUPPLIED:
				return 'A password was supplied but it could not be verified against a stored hash';
			break;
			
		}
		
	}
	
}
