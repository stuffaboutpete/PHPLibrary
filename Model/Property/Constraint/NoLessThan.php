<?php

namespace Suburb\Model\Property\Constraint;

use Suburb\Model;
use Suburb\Model\Property\IConstraint;

class NoLessThan
implements IConstraint
{
	
	private $length;
	
	public function __construct($length)
	{
		$this->length = $length;
	}
	
	public function isValid($value)
	{
		if (!is_array($value) && !is_string($value) && !is_numeric($value)) {
			throw new \InvalidArgumentException(
				'Value must be countable: array, string or numeric'
			);
		}
		if (count($value) < $this->length) {
			throw new \InvalidArgumentException(
				'Value length must be no shorter than ' . $this->length
			);
		}
		return true;
	}
	
}