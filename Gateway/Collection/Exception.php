<?php

namespace PO\Gateway\Collection;

class Exception
extends \PO\Exception
{
	
	const MISSING_CONSTRUCTOR_ARGUMENT					= 1;
	const INVALID_CLASS_NAME							= 2;
	const DATA_IS_NOT_ARRAY_OF_ASSOCIATIVE_ARRAYS		= 3;
	const DATA_AND_OBJECT_COUNT_MISMATCH				= 4;
	const INVALID_OBJECT								= 5;
	const INVALID_GATEWAY_OBJECT						= 6;
	const COLLECTION_ENTRY_CANNOT_BE_MANUALLY_CHANGED	= 7;
	const COLLECTION_ENTRY_CANNOT_BE_UNSET				= 8;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::MISSING_CONSTRUCTOR_ARGUMENT:
				return 'Not all constructor arguments were provided';
			break;
			
			case Exception::INVALID_CLASS_NAME:
				return 'The provided class name does not represent an existing class';
			break;
			
			case Exception::DATA_IS_NOT_ARRAY_OF_ASSOCIATIVE_ARRAYS:
				return 'The provided data does not contain only associative arrays';
			break;
			
			case Exception::DATA_AND_OBJECT_COUNT_MISMATCH:
				return 'The provided data and object arrays are not the same length';
			break;
			
			case Exception::INVALID_OBJECT:
				return 'An object provided to the constructor was not of the type ' .
					'supplied as the collection class name';
			break;
			
			case Exception::INVALID_GATEWAY_OBJECT:
				return 'An object returned from the gateway was not of the type requested';
			break;
			
			case Exception::COLLECTION_ENTRY_CANNOT_BE_MANUALLY_CHANGED:
				return 'An illegal attempt was made to change an entry in the collection';
			break;
			
			case Exception::COLLECTION_ENTRY_CANNOT_BE_UNSET:
				return 'An illegal attempt was made to unset an entry in the collection';
			break;
			
		}
		
	}
	
}
