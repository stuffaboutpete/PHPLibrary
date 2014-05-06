<?php

namespace Suburb\Gateway;

class Exception
extends \Suburb\Exception
{
	
	const CLASS_TYPE_NOT_SPECIFIED									= 0;
	const CLASS_TYPE_DOES_NOT_EXIST									= 1;
	const CLASS_TYPE_NOT_REGISTERED									= 2;
	const QUERIES_NOT_PROVIDED_AS_ASSOCIATIVE_ARRAY 				= 3;
	const QUERY_KEY_NOT_RECOGNISED									= 4;
	const COLLECTION_OBJECT_NOT_CREATED								= 5;
	const INCORRECT_ARGUMENT_COUNT_FOR_SELECTED_QUERY				= 6;
	const SUPPLIED_FACTORY_DOES_NOT_WORK_WITH_SUPPLIED_CLASS		= 7;
	const SUPPLIED_QUERY_PROVIDER_DOES_NOT_WORK_WITH_SUPPLIED_CLASS	= 8;
	const FACTORY_CREATED_OBJECT_IS_NOT_EXPECTED_TYPE				= 9;
	const INVALID_SAVE_STATEMENT									= 10;
	const INVALID_DELETE_STATEMENT									= 11;
	const DISMANTLED_OBJECT_KEYS_DO_NOT_MATCH_PARAMETERS_IN_QUERY	= 12;
	const NO_DATA_FOUND												= 13;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::CLASS_TYPE_NOT_SPECIFIED:
				return 'A class type must be provided to reference a registered type';
			break;
			
			case Exception::CLASS_TYPE_DOES_NOT_EXIST:
				return 'Registered class type does not exist';
			break;
			
			case Exception::CLASS_TYPE_NOT_REGISTERED:
				return 'Specified class has not been registered';
			break;
			
			case Exception::QUERIES_NOT_PROVIDED_AS_ASSOCIATIVE_ARRAY:
				return 'Queries returned from instance of IQueryProvider ' .
					'must be within an associative array';
			break;
			
			case Exception::QUERY_KEY_NOT_RECOGNISED:
				return 'No query from IQueryProvider could be identified';
			break;
			
			case Exception::COLLECTION_OBJECT_NOT_CREATED:
				return 'The provided collection factory did not return a collection object';
			break;
			
			case Exception::INCORRECT_ARGUMENT_COUNT_FOR_SELECTED_QUERY:
				return 'Arguments passed to fetch method should match parameter count in ' .
					'selected query. Note that the first argument should be the class type to ' .
					'select so number of arguments should be number of parameters plus one';
			break;
			
			case Exception::SUPPLIED_FACTORY_DOES_NOT_WORK_WITH_SUPPLIED_CLASS:
				return 'The supplied factory did not approve the supplied class name ' .
					'meaning that it cannot be expected to handle objects of this type';
			break;
			
			case Exception::SUPPLIED_QUERY_PROVIDER_DOES_NOT_WORK_WITH_SUPPLIED_CLASS:
				return 'The supplied query provider did not approve the supplied class name ' .
					'meaning that it cannot be expected to handle objects of this type';
			break;
			
			case Exception::FACTORY_CREATED_OBJECT_IS_NOT_EXPECTED_TYPE:
				return 'An object created by the supplied factory did match the expected type';
			break;
			
			case Exception::INVALID_SAVE_STATEMENT:
				return 'The save statement provided by the query provider must include the ' .
					"term 'INSERT' and also the term 'ON DUPLICATE KEY UPDATE'";
			break;
			
			case Exception::INVALID_DELETE_STATEMENT:
				return 'The delete statement provided by the query ' .
					"provider must include the term 'DELETE'";
			break;
			
			case Exception::DISMANTLED_OBJECT_KEYS_DO_NOT_MATCH_PARAMETERS_IN_QUERY:
				return 'The data keys returned from dismantling an object did not match the ' .
					'parameters in the statement provided by the query provider';
			break;
			
			case Exception::NO_DATA_FOUND:
				return 'A query was executed that unexpectedly returned no data';
			break;
			
		}
		
	}
	
}
