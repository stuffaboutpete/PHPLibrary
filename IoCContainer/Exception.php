<?php

namespace PO\IoCContainer;

class Exception
extends \PO\Exception
{
	
	const INVALID_CLASS														= 0;
	const CANNOT_INJECT_NON_OBJECT_DEPENDENCY								= 1;
	const PROVIDED_DEPENDENCIES_MUST_BE_INSIDE_ARRAY						= 2;
	const PROVIDED_DOWNSTREAM_DEPENDENCIES_MUST_BE_INSIDE_ARRAY				= 3;
	const CANNOT_REGISTER_NON_OBJECT_SINGLETON								= 4;
	const SINGLETON_INSTANCE_ALREADY_REGISTERED								= 5;
	const SINGLETON_CANNOT_BE_REGISTERED_STATICALLY							= 6;
	const CALLBACK_CANNOT_BE_REGISTERED_STATICALLY							= 7;
	const CALLBACK_CANNOT_BE_RESOLVED_STATICALLY							= 8;
	const OBJECT_DOES_NOT_IMPLEMENT_INTERFACE								= 9;
	const INTERFACE_IMPLEMENTATION_CANNOT_BE_REGISTERED_STATICALLY			= 10;
	const INTERFACE_CANNOT_BE_RESOLVED_STATICALLY							= 11;
	const NON_OBJECT_PROVIDED_TO_CALL										= 12;
	const CALL_METHOD_DOES_NOT_EXIST										= 13;
	const SINGLETON_STYLE_DEPENDENCY_MUST_BE_OBJECT							= 14;
	const SINGLETON_STYLE_DEPENDENCY_CONFLICTS_WITH_REGISTERED_SINGLETON	= 15;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::INVALID_CLASS:
				return 'The provided class is not valid';
			break;
			
			case Exception::CANNOT_INJECT_NON_OBJECT_DEPENDENCY:
				return 'A non-object dependency was encountered and no value has been provided';
			break;
			
			case Exception::PROVIDED_DEPENDENCIES_MUST_BE_INSIDE_ARRAY:
			case Exception::PROVIDED_DOWNSTREAM_DEPENDENCIES_MUST_BE_INSIDE_ARRAY:
				return 'Dependencies must be provided within an array';
			break;
			
			case Exception::CANNOT_REGISTER_NON_OBJECT_SINGLETON:
				return 'An object must be provided when registering a singleton';
			break;
			
			case Exception::SINGLETON_INSTANCE_ALREADY_REGISTERED:
				return 'A singleton instance was registered when one already exists';
			break;
			
			case Exception::SINGLETON_CANNOT_BE_REGISTERED_STATICALLY:
				return 'Singletons cannot be registered statically';
			break;
			
			case Exception::CALLBACK_CANNOT_BE_REGISTERED_STATICALLY:
				return 'Callbacks cannot be registered statically';
			break;
			
			case Exception::CALLBACK_CANNOT_BE_RESOLVED_STATICALLY:
				return 'Callbacks cannot be resolved statically';
			break;
			
			case Exception::OBJECT_DOES_NOT_IMPLEMENT_INTERFACE:
				return 'The provided object does not implement the identified interface';
			break;
			
			case Exception::INTERFACE_IMPLEMENTATION_CANNOT_BE_REGISTERED_STATICALLY:
				return 'An instance of an interface cannot be registered statically';
			break;
			
			case Exception::INTERFACE_CANNOT_BE_RESOLVED_STATICALLY:
				return 'An interface cannot be resolved statically';
			break;
			
			case Exception::NON_OBJECT_PROVIDED_TO_CALL:
				return 'A non-object was provided whilst attempting ' .
					'to resolve a method\'s dependency';
			break;
			
			case Exception::CALL_METHOD_DOES_NOT_EXIST:
				return 'The provided method does not exist';
			break;
			
			case Exception::SINGLETON_STYLE_DEPENDENCY_MUST_BE_OBJECT:
				return 'Dependencies provided to be made available for ' .
					'one method call must be objects';
			break;
			
			case Exception::SINGLETON_STYLE_DEPENDENCY_CONFLICTS_WITH_REGISTERED_SINGLETON:
				return 'A dependency provided to be made available for one method ' .
					'call conflicts with an existing singleton';
			break;
			
		}
		
	}
	
}
