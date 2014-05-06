<?php

namespace Suburb\Model\Property;

use Suburb\Model\Property\Integer;

/**
 * Suburb\Model\Property\ID
 * 
 * An implementation of a Property
 * which will only hold a positive integer
 */
class ID
extends Integer
{
	
	/**
	 * Converts input to an integer
	 * 
	 * @param  mixed   $originalValue   Any value
	 * @return boolean                  The integer value of the input
	 * @throws InvalidArgumentException If input is not a positive integer
	 * @see    Suburb\Model\Property\Integer
	 */
	public function editInput($originalValue)
	{
		if ($originalValue < 1) {
			throw new \InvalidArgumentException('Value must be positive');
		}
		return parent::editInput($originalValue);
	}
	
}