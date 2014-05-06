<?php

namespace Suburb\Model\Property\Constraint;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../IConstraint.php';

class InArray
implements Model\Property\IConstraint
{
	
	private $array;
	private $typeCheck;
	
	public function __construct(array $array, $typeCheck = false)
	{
		$this->array = $array;
		$this->typeCheck = ($typeCheck) ? true : false;
	}
	
	public function isValid($value)
	{
		foreach ($this->array as $element) {
			if ($this->typeCheck) {
				$match = ($value === $element);
			} else {
				$match = ($value == $element);
			}
			if ($match) return true;
		}
		throw new \InvalidArgumentException(
			'Value must match an element in the provided array'
		);
	}
	
}