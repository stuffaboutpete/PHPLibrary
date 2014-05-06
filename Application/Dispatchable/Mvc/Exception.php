<?php

namespace Suburb\Application\Dispatchable\Mvc;

class Exception
extends \Suburb\Exception
{
	
	const NO_CONTROLLER_CLASS_OR_TEMPLATE_COULD_BE_IDENTIFIED					= 1;
	const CONTROLLER_CLASS_DOES_NOT_EXIST										= 2;
	const CONTROLLER_CLASS_IS_NOT_CONTROLLER									= 3;
	const CONTROLLER_TEMPLATE_DOES_NOT_EXIST									= 4;
	const CONTROLLER_IDENTIFIER_RETURNS_NON_ASSOCIATIVE_ARRAY_PATH_VARIABLES	= 5;
	const CONTROLLER_RETURNS_NON_ASSOCIATIVE_ARRAY_TEMPLATE_VARIABLES			= 6;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NO_CONTROLLER_CLASS_OR_TEMPLATE_COULD_BE_IDENTIFIED:
				return 'No controller class or template file could be identified';
			break;
			
			case Exception::CONTROLLER_CLASS_DOES_NOT_EXIST:
				return 'The identified controller could not be found';
			break;
			
			case Exception::CONTROLLER_CLASS_IS_NOT_CONTROLLER:
				return 'The identified controller is not an instance of ' .
					'Suburb\Application\Dispatchable\Mvc\Controller';
			break;
			
			case Exception::CONTROLLER_TEMPLATE_DOES_NOT_EXIST:
				return 'The identified template could not be found';
			break;
			
			case Exception::CONTROLLER_IDENTIFIER_RETURNS_NON_ASSOCIATIVE_ARRAY_PATH_VARIABLES:
				return 'The controller identifier provided illegal path variables. ' .
					'Path variables must be in an associative array or null';
			break;
			
			case Exception::CONTROLLER_RETURNS_NON_ASSOCIATIVE_ARRAY_TEMPLATE_VARIABLES:
				return 'The identified controller provided illegal template variables. ' .
					'Template variables must be in an associative array or null';
			break;
			
		}
		
	}
	
}