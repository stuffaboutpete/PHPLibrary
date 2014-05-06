<?php

namespace PO\Model\Property;

class Exception
extends \PO\Exception
{
	
	const NON_ICONSTRAINT_PROVIDED						= 0;
	const CONSTRAINT_RETURNED_NON_FALSE_FROM_IS_VALID	= 1;
	const NON_NULL_PROPERTY_SET_TO_NULL					= 2;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NON_ICONSTRAINT_PROVIDED:
				return 'A constraint was provided that does not ' .
					'implement PO\Model\Property\IConstraint';
			break;
			
			case Exception::CONSTRAINT_RETURNED_NON_FALSE_FROM_IS_VALID:
				return 'A constraint returned a non-true value from its isValid method. ' .
					'Constraints are expected to return true or throw an exception when validating';
			break;
			
			case Exception::NON_NULL_PROPERTY_SET_TO_NULL:
				return 'A property which does not accept a null value was set to null';
			break;
			
		}
		
	}
	
}
