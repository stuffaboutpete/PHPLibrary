<?php

namespace Suburb\Model\Property;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../Property.php';

class String
extends Model\Property
{
	
	public function editInput($originalValue)
	{
		if (is_null($originalValue)) return null;
		if (!is_string($originalValue) && !is_numeric($originalValue)) {
			throw new \InvalidArgumentException();
			// @todo Throw better error
		}
		return strval($originalValue);
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
