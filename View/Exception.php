<?php

namespace Suburb\View;

class Exception
extends \Suburb\Exception
{
	
	const TEMPLATE_PATH_NOT_STRING					= 0;
	const TEMPLATE_FILE_COULD_NOT_BE_IDENTIFIED		= 1;
	const TEMPLATE_VARIABLES_NOT_ASSOCIATIVE_ARRAY	= 2;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::TEMPLATE_PATH_NOT_STRING:
				return 'Provided template path is not a string or it is a zero length string';
			break;
			
			case Exception::TEMPLATE_FILE_COULD_NOT_BE_IDENTIFIED:
				return 'No template path could be found from the data provided';
			break;
			
			case Exception::TEMPLATE_VARIABLES_NOT_ASSOCIATIVE_ARRAY:
				return 'The template variables provided to the constructor ' .
					'were not in a key/value format';
			break;
			
		}
		
	}
	
}
