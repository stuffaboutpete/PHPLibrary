<?php

namespace PO\HttpRequest\Api\TokenValidator\Mechanism;

class Exception
extends \PO\Exception
{
	
	const GENERIC_ERROR				= 0;
	const UNKNOWN_ERROR				= 1;
	const INVALID_TOKEN				= 2;
	const TOKEN_ALREADY_USED		= 3;
	const MISSING_REQUIRED_FIELDS	= 4;
	const PROVIDED_FIELD_TOO_LONG	= 5;
	const INVALID_FORMAT			= 6;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::GENERIC_ERROR:
				return 'An error state was indicated by the validation mechanism';
			break;
			
			case Exception::UNKNOWN_ERROR:
				return 'An unexpected error state was indicated by the validation mechanism';
			break;
			
			case Exception::INVALID_TOKEN:
				return 'Provided token was not accepted by the validation mechanism';
			break;
			
			case Exception::TOKEN_ALREADY_USED:
				return 'The validation mechanism indicated that the token had already been used';
			break;
			
			case Exception::MISSING_REQUIRED_FIELDS:
				return 'Fields required by the validation mechanism were not provided';
			break;
			
			case Exception::PROVIDED_FIELD_TOO_LONG:
				return 'A field provided to the validation mechanism was too long';
			break;
			
			case Exception::INVALID_FORMAT:
				return 'Data provided to the validation mechanism was in an invalid format';
			break;
			
		}
		
	}
	
}
