<?php

namespace PO\Gateway\Factory\Model;

class Exception
extends \PO\Exception
{
	
	const BUILD_MAP_SUPPLIED_MUST_BE_ASSOCIATIVE_ARRAY_OR_NULL	= 0;
	const CONFLICTING_BUILD_MAP_KEYS_USED						= 1;
	const COMPLEX_PROPERTY_NOT_DISMANTLED						= 2;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::BUILD_MAP_SUPPLIED_MUST_BE_ASSOCIATIVE_ARRAY_OR_NULL:
				return 'The return value from getBuildMap was not an associative array or null';
			break;
			
			case Exception::CONFLICTING_BUILD_MAP_KEYS_USED:
				return 'Two conflicting keys from the provided build map were used';
			break;
			
			case Exception::COMPLEX_PROPERTY_NOT_DISMANTLED:
				return 'An object value was not replaced with a simpler type ' .
					'during a call to dismantleComplexProperties';
			break;
			
		}
		
	}
	
}
