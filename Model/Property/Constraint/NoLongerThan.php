<?php

namespace Suburb\Model\Property\Constraint;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../IConstraint.php';

class NoLongerThan
implements Model\Property\IConstraint
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
		if (count($value) > $this->length) {
			throw new \InvalidArgumentException(
				'Value length must be no longer than ' . $this->length
			);
		}
		return true;
	}
	
}