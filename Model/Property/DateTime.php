<?php

namespace PO\Model\Property;

use PO\Model\Property;

class DateTime
extends Property
{
	
	public function editInput($originalValue)
	{
		if (!($originalValue instanceof \DateTime)) {
			$originalValue = new \DateTime($originalValue);
		}
		return $originalValue;
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
