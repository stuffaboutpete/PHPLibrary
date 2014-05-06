<?php

namespace PO\Model;

class Exception
extends \PO\Exception
{
	
	const INVALID_PROPERTY_NAME							= 0;
	const NO_PROPERTIES_PROVIDED						= 1;
	const PROPERTY_NAMES_NOT_PROVIDED					= 2;
	const PROVIDED_PROPERTY_NOT_INSTANCE_OF_PROPERTY	= 3;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::INVALID_PROPERTY_NAME:
				return 'A property name was not recognised';
			break;
			
			case Exception::NO_PROPERTIES_PROVIDED:
				return 'No properties were provided to the constructor';
			break;
			
			case Exception::PROPERTY_NAMES_NOT_PROVIDED:
				return 'Properties array is not associative. Property names ' .
					'should be declared as keys';
			break;
			
			case Exception::PROVIDED_PROPERTY_NOT_INSTANCE_OF_PROPERTY:
				return 'A supplied property was not an instance of PO\Model\Property';
			break;
			
		}
		
	}
	
}
