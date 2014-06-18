<?php

namespace PO\IoCContainer\Containment\Application;

class Exception
extends \PO\Exception
{
	
	const OPTIONS_NOT_PROVIDED			= 0;
	const REQUIRED_OPTION_NOT_PROVIDED	= 1;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::OPTIONS_NOT_PROVIDED:
				return 'An array of options must be provided in order to resolve the application';
			break;
			
			case Exception::REQUIRED_OPTION_NOT_PROVIDED:
				return 'A required key was not provided as part of the given options array';
			break;
			
		}
		
	}
	
}
