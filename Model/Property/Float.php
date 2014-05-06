<?php

namespace PO\Model\Property;

use PO\Model\Property;

/**
 * PO\Model\Property\Float
 * 
 * An implementation of a Property
 * which will only hold a float value
 */
class Float
extends Property
{
	
	/**
	 * Length
	 * 
	 * The maximum number of
	 * characters that define
	 * the number
	 * 
	 * @var int
	 */
	private $length;
	
	/**
	 * Decimals
	 * 
	 * The maximum number of
	 * decimal places to record
	 * 
	 * @var int
	 */
	private $decimals;
	
	/**
	 * Constructor
	 * 
	 * Allows setting of maximum length
	 * and number of decimal places
	 * 
	 * @param  int     $length      The maximum number of characters in the number
	 * @param  int     $decimals    The maximum number of decimal places in the number
	 * @param  array   $constraints optional See parent
	 * @param  boolean $allowNull   optional See parent
	 * @see    PO\Model\Property
	 * @return null
	 */
	public function __construct($length, $decimals, $constraints = [], $allowNull = true)
	{
		
		// Save the values for this class
		$this->length = $length;
		$this->decimals = $decimals;
		
		// Pass other values to the parent
		parent::__construct($constraints, $allowNull);
		
	}
	
	/**
	 * Converts input to a float
	 * 
	 * @param  mixed $originalValue Any value
	 * @return float                The altered float value of the original value
	 * @throws InvalidArgumentException If value is an object, array or boolean
	 * @throws InvalidArgumentException If value is not numeric
	 * @throws InvalidArgumentException If the value cannot be represented with the given length
	 */
	protected function editInput($originalValue)
	{
		
		// Throw an exception if the value is
		// not a type that can be numeric
		if (is_object($originalValue) || is_array($originalValue) || is_bool($originalValue)) {
			throw new \InvalidArgumentException('Value cannot be an object, array or boolean');
		}
		
		// Throw an exception if the value
		// is not a numeric format
		if (!is_numeric($originalValue)) {
			throw new \InvalidArgumentException('Value must be numeric');
		}
		
		// Round the value to the set number
		// of decimal places, and cut the
		// length to the specified amount
		$trimmedValue  = substr(round($originalValue, $this->decimals), 0, $this->length + 1);
		
		// Convert the value to a float
		$roundedValue = floatval($trimmedValue);
		
		// Check whether the value
		// contains a decimal place
		$containsPoint = (strpos($roundedValue, '.') !== false);
		
		// Throw an exception if the altered
		// value is longer than the allowed
		// length, accounting for an decimal
		// point if one is present
		if (strlen($roundedValue) > $this->length + (1 * $containsPoint)) {
			throw new \InvalidArgumentException('The value provided is longer than the set length');
		}
		
		// Return the final float value
		return $roundedValue;
		
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
