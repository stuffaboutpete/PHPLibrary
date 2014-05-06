<?php

namespace Suburb\Model\Property\Constraint;

use Suburb\Model;
use Suburb\Model\Property\IConstraint;

class Positive
implements IConstraint
{
	
	public function isValid($value)
	{
		
		if (is_object($value) || get_class($value) != 'DateTime') {
			throw new Exception(
				Exception::INCOMPATIBLE_TYPE,
				'Constraint only compatible with instances of DateTime'
			);
		}
		
		if (!is_numeric($value)) {
			throw new Exception(
				Exception::VALUE_DOES_NOT_MEET_CONSTRAINT,
				"Value $value is not positive"
			);
		}
		
		return ($value > 0) ? true : false;
		
	}
	
}