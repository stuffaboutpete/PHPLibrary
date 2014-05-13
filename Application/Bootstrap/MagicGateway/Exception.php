<?php

namespace PO\Application\Bootstrap\MagicGateway;

class Exception
extends \PO\Exception
{
	
	const INVALID_SEARCH_DIRECTORY = 0;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::INVALID_SEARCH_DIRECTORY:
				return 'The directory provided to be searched for Gateway classes does not exist';
			break;
			
		}
		
	}
	
}
