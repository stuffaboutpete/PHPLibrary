<?php

namespace PO\View;

class Exception
extends \PO\Exception
{
	
	const TEMPLATE_PATH_NOT_STRING					= 0;
	const TEMPLATE_FILE_COULD_NOT_BE_IDENTIFIED		= 1;
	const TEMPLATE_VARIABLES_NOT_ASSOCIATIVE_ARRAY	= 2;
	const ANCESTOR_CLASS_DOES_NOT_EXIST				= 3;
	const ANCESTOR_CLASS_NOT_ANCESTOR				= 4;
	const RENDER_INTO_METHOD_DOES_NOT_EXIST			= 5;
	
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
			
			case Exception::ANCESTOR_CLASS_DOES_NOT_EXIST:
				return 'The class provided as an example of an ancestor does not exist';
			break;
			
			case Exception::ANCESTOR_CLASS_NOT_ANCESTOR:
				return 'The provided class is not an ancestor of this class';
			break;
			
			case Exception::RENDER_INTO_METHOD_DOES_NOT_EXIST:
				return 'The object provided to render the current view into ' .
					'does not have the provided method';
			break;
			
		}
		
	}
	
}
