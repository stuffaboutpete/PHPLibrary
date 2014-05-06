<?php

namespace Suburb\Model\Property;

use Suburb\Model\Property;

/**
 * Suburb\Model\Property\Integer
 * 
 * An implementation of a Property
 * which will only hold an integer
 */
class Integer
extends Property
{
	
	/**
	 * Converts input to an integer
	 * 
	 * @param  mixed   $originalValue   Any value
	 * @return boolean                  The integer value of the input
	 * @throws InvalidArgumentException If input is not numeric
	 */
	public function editInput($originalValue)
	{
		if (!is_numeric($originalValue)) throw new \InvalidArgumentException(
			'Value must be a number or numeric string'
		);
		return intval($originalValue);
	}
	
	/**
	 * Required method, returns given value
	 * 
	 * @param  mixed $savedValue Any value
	 * @return mixed             The same value
	 */
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
