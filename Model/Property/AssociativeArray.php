<?php

namespace Suburb\Model\Property;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../Property.php';

class PHPArray
extends Model\Property
{
	
	public function editInput($originalValue)
	{
		if (!is_array($originalValue)) {
			throw new \InvalidArgumentException();
			// @todo Throw better error
		}
		return $originalValue;
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
