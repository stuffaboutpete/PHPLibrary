<?php

namespace Suburb\Model\Property\Constraint;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../IConstraint.php';

class Positive
implements Model\Property\IConstraint
{
	
	public function isValid($value)
	{
		
		if (!is_numeric($value)) {
			throw new \InvalidArgumentException();
			// @todo Should be NonsensicalValueException
		}
		return ($value > 0) ? true : false;
		
	}
	
}