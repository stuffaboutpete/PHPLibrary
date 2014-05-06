<?php

namespace Suburb\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure;

class Exception
extends \Suburb\Exception
{
	
	const GET_METHOD_CALLED_BEFORE_RECEIVE_PATH	= 0;
	const TEMPLATES_DIRECTORY_DOES_NOT_EXIST	= 1;
	const AMBIGUOUS_CONTROLLER_CLASS			= 2;
	const AMBIGUOUS_TEMPLATE_FILE				= 3;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::GET_METHOD_CALLED_BEFORE_RECEIVE_PATH:
				return 'Any method which gets data must be called after receivePath() is called';
			break;
			
			case Exception::TEMPLATES_DIRECTORY_DOES_NOT_EXIST:
				return 'The directory passed to the constructor does not exist';
			break;
			
			case Exception::AMBIGUOUS_CONTROLLER_CLASS:
				return 'Multiple classes were found that could be used as the controller';
			break;
			
			case Exception::AMBIGUOUS_TEMPLATE_FILE:
				return 'Multiple files were found that could be used as the template';
			break;
			
		}
		
	}
	
}
