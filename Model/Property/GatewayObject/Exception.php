<?php

namespace PO\Model\Property\GatewayObject;

class Exception
extends \PO\Exception
{
	
	const INVALID_CLASS_NAME_PROVIDED						= 0;
	const INVALID_OBJECT_TYPE_PROVIDED_AS_PROPERTY_VALUE	= 1;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::INVALID_CLASS_NAME_PROVIDED:
				return 'The class name provided to the constructor is not a valid class name';
			break;
			
			case Exception::INVALID_OBJECT_TYPE_PROVIDED_AS_PROPERTY_VALUE:
				return 'An object was provided to be set as the value of the ' .
					'property that was not of the nominated type';
			break;
			
		}
		
	}
	
}
