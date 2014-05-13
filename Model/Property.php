<?php

namespace PO\Model;

use PO\Model\Property\IConstraint;
use PO\Model\Property\Exception;

/**
 * PO\Model\Property
 * 
 * Used to represent an object with a
 * single value which may or may not
 * be subject to constraints on its value.
 * It can be altered on set or get to
 * ensure its type is predictable.
 */
abstract class Property
{
	
	/**
	 * The current value
	 * 
	 * @var mixed
	 */
	private $value;
	
	/**
	 * Constraints the property is subject to
	 * 
	 * This will only hold instances of
	 * PO\Model\Property\IConstraint
	 * 
	 * @var array
	 */
	private $constraints = [];
	
	/**
	 * Whether the value is optional
	 * 
	 * If it is optional, the property can be
	 * set with a value of null and the null
	 * value will not be subject to constraints
	 * 
	 * @var boolean
	 */
	private $optional;
	
	/**
	 * Constructor
	 * 
	 * Accepts an array of instances of
	 * PO\Model\Property\IConstraint as
	 * well as a boolean declaring whether
	 * the property value is optional
	 * 
	 * @param  array   $constraints An array of instances of PO\Model\Property\IConstraint
	 * @param  boolean $optional    optional Whether the property value is optional
	 * @return null
	 * @throws InvalidArgumentException If non-IConstraints are provided
	 */
	public function __construct($constraints = [], $optional = true)
	{
		
		// @todo Should optional value be set using a method?
		
		// Ensure each constraint is an instance
		// of PO\Model\Property\Constraint and
		// save it if so
		foreach ((array) $constraints as $constraint) {
			if (!$constraint instanceof IConstraint) {
				throw new Exception(Exception::NON_ICONSTRAINT_PROVIDED);
			}
			$this->constraints[] = $constraint;
		}
		
		// Record whether the property will
		// accept null values
		$this->optional = ($optional) ? true : false;
		
	}
	
	/**
	 * Allows the value to be set
	 * 
	 * The set value will be passed through
	 * all of the registered constraints and
	 * through the editInput method of the
	 * implementing class to ensure it is
	 * a predictable type
	 * 
	 * @param mixed $value              optional The value to be set or null as default
	 * @return PO\Model\Property    $this
	 * @throws InvalidArgumentException If null is set when the value is not optional
	 * @throws InvalidArgumentException If value does not match at least one constraint
	 */
	public function set($value = null)
	{
		
		// If the provided value is null, either
		// save it if it null values are allowed
		// or throw an exception 
		if (is_null($value)) {
			if ($this->optional) {
				$value = null;
			} else {
				throw new Exception(
					Exception::NON_NULL_PROPERTY_SET_TO_NULL,
					'Property type: ' . get_class($this)
				);
			}
			return;
		}
		
		// Otherwise, loop through our constraints,
		// passing the value to each and throwing
		// an exception if it fails with any of them
		foreach ($this->constraints as $constraint) {
			if (!$constraint->isValid($value)) {
				throw new Exception(
					Exception::CONSTRAINT_RETURNED_NON_FALSE_FROM_IS_VALID,
					'Constraint class: ' . get_class($constraint)
				);
			}
		}
		
		// Save the value after it has been altered
		// by the editInput method
		$this->value = $this->editInput($value);
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Allows the value to be retrieved
	 * 
	 * The value will be passed through the
	 * editOutput method of the implementing
	 * class as it is retrieved so that any
	 * last minute work can be done before
	 * returning the value
	 * 
	 * @return mixed The value returned from editOutput method
	 */
	public function get()
	{
		return $this->editOutput($this->value);
	}
	
	/**
	 * Used to alter the value as it is set
	 * 
	 * @param  mixed $originalValue The value provided by the user
	 * @return mixed                The value after it has been optionally altered
	 */
	abstract protected function editInput($originalValue);
	
	/**
	 * Used to alter the value as it is retrieved
	 * 
	 * @param  mixed $savedValue The value after it was passed through the editInput method
	 * @return mixed             The value after it has been optionally altered
	 */
	abstract protected function editOutput($savedValue);
	
}
