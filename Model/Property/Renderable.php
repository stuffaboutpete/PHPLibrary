<?php

namespace Suburb\Model\Property;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../Property.php';

class Renderable
extends Model\Property
{
	
	public function editInput($originalValue)
	{
		if (is_object($originalValue)) {
			if (!method_exists($originalValue, '__toString')) {
				throw new \InvalidArgumentException();
				// @todo Throw better error
			}
		}
		return $originalValue;
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
