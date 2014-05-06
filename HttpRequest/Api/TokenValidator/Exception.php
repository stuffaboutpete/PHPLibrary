<?php

namespace Suburb\HttpRequest\Api\TokenValidator;

class Exception
extends \Suburb\Exception
{
	
	const NON_STRING_TOKEN_SUPPLIED								= 0;
	const REQUIRED_ENTRY_FIELDS_NOT_SUPPLIED					= 1;
	const UNRECOGNISED_TOKEN_PROVIDED_FOR_FURTHER_PROCESSING	= 2;
	const NON_INSTANT_WIN_TOKEN_PROVIDED_TO_COMPLETE_ENTRY		= 3;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NON_STRING_TOKEN_SUPPLIED:
				return 'Supplied token must be a string';
			break;
			
			case Exception::REQUIRED_ENTRY_FIELDS_NOT_SUPPLIED:
				return 'Data supplied for entry did not include all fields ' .
					'required by supplied mechanism';
			break;
			
			case Exception::UNRECOGNISED_TOKEN_PROVIDED_FOR_FURTHER_PROCESSING:
				return 'A token was provided for further processing that has not ' .
					'been passed to \'isValidToken\'';
			break;
			
			case Exception::NON_INSTANT_WIN_TOKEN_PROVIDED_TO_COMPLETE_ENTRY:
				return 'A token was provided for entry completion that is not an instant winner';
			break;
			
		}
		
	}
	
}
