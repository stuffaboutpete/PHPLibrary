<?php

namespace PO\Application\Bootstrap\AccessController;

class Exception
extends \PO\Exception
{
	
	const AUTHENTICATOR_DOES_NOT_EXIST_AS_APPLICATION_EXTENSION = 0;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::AUTHENTICATOR_DOES_NOT_EXIST_AS_APPLICATION_EXTENSION:
				return 'This class relies on the application having an extension named ' .
					'\'authenticator\'. See the bootstrap class ' .
					'\PO\Application\Bootstrap\Authenticator';
			break;
			
		}
		
	}
	
}
