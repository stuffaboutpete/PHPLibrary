<?php

namespace PO\Application\Dispatchable\Rest\Endpoint\GatewayModel;

class Exception
extends \PO\Exception
{
	
	const TARGET_CLASS_MUST_BE_REGISTERED_WITH_PROVIDED_GATEWAY	= 1;
	const INVALID_SELECT_MAP_KEY								= 2;
	const NON_OBJECT_RETURNED_FROM_GATEWAY						= 3;
	const INVALID_CLASS_RETURNED_FROM_GATEWAY					= 4;
	const NON_FACTORY_RETURNED_FROM_GATEWAY						= 5;
	const INCORRECT_FACTORY_PROVIDED_FROM_GATEWAY				= 6;
	const NON_ARRAY_RETURNED_FROM_FACTORY_DISMANTLE				= 7;
	const UNEXPECTED_COLLECTION_IDENTIFIED						= 8;
	const METHOD_CALL_ON_NON_OBJECT_INITIATED_BY_SELECT_MAP		= 9;
	const UNKNOWN_METHOD_CALLED_BY_SELECT_MAP					= 10;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::TARGET_CLASS_MUST_BE_REGISTERED_WITH_PROVIDED_GATEWAY:
				return 'The provided target class is not registered ' .
					'with the provided gateway object';
			break;
			
			case Exception::INVALID_SELECT_MAP_KEY:
				return 'All select map keys must contain either a plus or minus symbol or be ' .
					'of the format \'someMethod as some_key\' unless they have an array value';
			break;
			
			case Exception::NON_OBJECT_RETURNED_FROM_GATEWAY:
				return 'The provided gateway fetched a non object';
			break;
			
			case Exception::INVALID_CLASS_RETURNED_FROM_GATEWAY:
				return 'An object retrieved from the provided gateway was not of the expected type';
			break;
			
			case Exception::NON_FACTORY_RETURNED_FROM_GATEWAY:
				return 'The provided gateway object failed to return an ' .
					'instance of PO\Gateway\Factory when requested';
			break;
			
			case Exception::INCORRECT_FACTORY_PROVIDED_FROM_GATEWAY:
				return 'The factory object returned from the provided gateway ' .
					'is not compatible with the given class type';
			break;
			
			case Exception::NON_ARRAY_RETURNED_FROM_FACTORY_DISMANTLE:
				return 'The located factory object failed to dismantle an object into an array';
			break;
			
			case Exception::UNEXPECTED_COLLECTION_IDENTIFIED:
				return 'The provided gateway returned a collection of ' .
					'objects when only one object was expected';
			break;
			
			case Exception::METHOD_CALL_ON_NON_OBJECT_INITIATED_BY_SELECT_MAP:
				return 'The provided select map suggested a method ' .
					'call should be made on a non object';
			break;
			
			case Exception::UNKNOWN_METHOD_CALLED_BY_SELECT_MAP:
				return 'The provided select map suggested an non existent method should be called';
			break;
			
		}
		
	}
	
}