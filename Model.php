<?php

namespace PO;

use PO\Model;
use PO\Model\Property;
use PO\Helper\ArrayType;

/**
 * Model
 * 
 * Used to hold a collection of
 * Properties and allow magic setting
 * and getting of their values
 */
class Model
{
	
	/**
	 * An array of Properties
	 * 
	 * Holds only instances of
	 * PO\Model\Property
	 * 
	 * @var array
	 */
	private $properties;
	
	/**
	 * Intercepts getPropertyName and setPropertyName
	 * 
	 * Any method call which begins with get or set
	 * will be handled here, attempting to get or
	 * set the relevant property
	 * 
	 * @param  string     $method     The name of the method called
	 * @param  array|null $arguments  Any arguments provided to the method call
	 * @return mixed                  The returned value for get method or $this for set methods
	 * @throws BadMethodCallException If method does not begin with get or set
	 * @throws BadMethodCallException If property cannot be identified from method name
	 */
	public function __call($method, $arguments = null)
	{
		
		// Split the string into direction and
		// property, eg 'set' and 'Title'
		$direction = substr($method, 0, 3);
		$propertyName = substr($method, 3, strlen($method));
		
		// If the string did not begin with 'get'
		// or 'set', it was a wayward method call
		if ($direction !== 'get' && $direction !== 'set') {
			throw new \BadMethodCallException();
		}
		
		// Lowercase the first letter of the property
		$propertyName = strtolower(substr($propertyName, 0, 1)) . substr($propertyName, 1);
		
		// Ensure the property exists
		$property = $this->getPropertyObject($propertyName);
		if (!$property) {
			throw new Model\Exception(
				Model\Exception::INVALID_PROPERTY_NAME,
				"Property: $propertyName"
			);
		}
		
		// If we are trying to 'set', go for it
		if ($direction === 'set') $property->set(isset($arguments[0]) ? $arguments[0] : null);
		
		// Else if we are trying to 'get', go for it
		else if ($direction === 'get') return $property->get();
		
		// If we haven't returned a value,
		// return this for chaining
		return $this;
		
	}
	
	/**
	 * Constructor
	 * 
	 * Accepts an array of properties and an
	 * optional array of initial values
	 * 
	 * @param  array $properties An associative array of PO\Model\Property objects
	 * @param  array $values     optional Any initial values that must be set immediately
	 * @return null
	 * @throws InvalidArgumentException If an empty array of properties is provided
	 * @throws InvalidArgumentException If properties are in a non-associative array
	 * @throws InvalidArgumentException If properties array contains non Model\Property objects
	 * @throws OutOfBoundsException     If Default value is provided for non existant property
	 */
	public function __construct(array $properties, array $values = null)
	{
		
		// If the properties array is
		// empty, throw an exception
		if (count($properties) == 0) {
			throw new Model\Exception(Model\Exception::NO_PROPERTIES_PROVIDED);
		}
		
		// If the properties array is not
		// associative, throw an exception;
		// the key is required as it is the
		// property name
		if (!ArrayType::isAssociative($properties)) {
			throw new Model\Exception(Model\Exception::PROPERTY_NAMES_NOT_PROVIDED);
		}
		
		// Loop through the properties...
		foreach ($properties as $propertyName => $propertyObject) {
			
			// Ensure each element is an instance
			// of PO\Model\Property
			if (!$propertyObject instanceof Property) {
				throw new Model\Property(
					Model\Property::PROVIDED_PROPERTY_NOT_INSTANCE_OF_PROPERTY,
					'Property type: ' . get_class($propertyObject)
				);
			}
			
			// Set the property with an initial value
			// if one exists, otherwise set it with
			// null to ensure that required values
			// are caught at this point
			$propertyObject->set(isset($values[$propertyName]) ? $values[$propertyName] : null);
			
		}
		
		// Ensure there are no unknown properties
		// in the array of initial values, throwing
		// an exception if any are found
		foreach ((array) $values as $propertyName => $value) {
			if (!isset($properties[$propertyName])) {
				throw new Model\Exception(
					Model\Exception::INVALID_PROPERTY_NAME,
					"Property: $propertyName"
				);
			}
		}
		
		$this->properties = $properties;
		
	}
	
	/**
	 * Returns the names of all properties
	 * 
	 * @return array The names of all the model properties
	 */
	public function propertyNames()
	{
		return array_keys($this->properties);
	}
	
	/**
	 * Finds a property object from its name
	 * 
	 * @param  string $propertyName        Name of the property
	 * @return PO\Model\Property|false A property object if found, false otherwise
	 */
	private function getPropertyObject($propertyName)
	{
		foreach ($this->properties as $key => $property) {
			if ($key == $propertyName) return $property;
		}
		return false;
	}
	
}
