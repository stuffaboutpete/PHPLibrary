<?php

namespace PO\Application\Dispatchable\Rest;

class Exception
extends \PO\Exception
{
	
	const NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD	= 1;
	const ENDPOINT_CLASS_DOES_NOT_EXIST							= 2;
	const ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT			= 3;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD:
				return 'No endpoint could be identified from the supplied routes ' .
					'config file that matched the request';
			break;
			
			case Exception::ENDPOINT_CLASS_DOES_NOT_EXIST:
				return 'The identified endpoint class does not exist';
			break;
			
			case Exception::ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT:
				return 'The identified endpoint class does not implement IEndpoint';
			break;
			
		}
		
	}
	
}