<?php

namespace Suburb\Model\Property\Constraint;

use Suburb\Model\Property\IConstraint;

class NotEmpty
implements IConstraint
{
	
	public function isValid($value)
	{
		
		if (!is_string($value) && !is_array($value)) {
			throw new Exception(
				Exception::INCOMPATIBLE_TYPE,
				'Constraint only compatible with string or array'
			);
		}
		
		if ($value === '') {
			throw new Exception(
				Exception::VALUE_DOES_NOT_MEET_CONSTRAINT,
				'String cannot be empty'
			);
		}
		
		if ($value === []) {
			throw new Exception(
				Exception::VALUE_DOES_NOT_MEET_CONSTRAINT,
				'Array cannot be empty'
			);
		}
		
		if (is_array($value)) {
			$foundNonNull = false;
			foreach ($value as $entry) {
				if (!is_null($entry)) {
					$foundNonNull = true;
					break;
				}
			}
			if (!$foundNonNull) {
				throw new Exception(
					Exception::VALUE_DOES_NOT_MEET_CONSTRAINT,
					'Array cannot only contain null values'
				);
			}
		}
		
		return true;
		
	}
	
}
