<?php

namespace PO\Model\Property;

use PO\Model\Property;

/**
 * PO\Model\Property\Boolean
 * 
 * An implementation of a Property
 * which will only hold a boolean value
 */
class Boolean
extends Property
{
	
	/**
	 * Converts input to a boolean
	 * 
	 * @param  mixed   $originalValue Any value
	 * @return boolean                The truthiness of the value
	 */
	protected function editInput($originalValue)
	{
		return ($originalValue) ? true : false;
	}
	
	/**
	 * Required method, returns given value
	 * 
	 * @param  mixed $savedValue Any value
	 * @return mixed             The same value
	 */
	protected function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
