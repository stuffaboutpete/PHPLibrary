<?php

namespace PO\Model\Property\Constraint;

class Exception
extends \PO\Exception
{
	
	const INCOMPATIBLE_TYPE					= 0;
	const VALUE_DOES_NOT_MEET_CONSTRAINT	= 1;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::INCOMPATIBLE_TYPE:
				return 'The provided value type is incompatible with this constraint';
			break;
			
			case Exception::VALUE_DOES_NOT_MEET_CONSTRAINT:
				return 'The provided value does not meet requirements';
			break;
			
		}
		
	}
	
}
