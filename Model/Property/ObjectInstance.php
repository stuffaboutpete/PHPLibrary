<?php

namespace Suburb\Model\Property;
use Suburb\Model as Model;

require_once dirname(__FILE__) . '/../Property.php';

class ObjectInstance
extends Model\Property
{
	
	private $type;
	
	public function __construct(
		$type,
		$constraints = array(),
		$allowNull = true
	)
	{
		$this->type = $type;
		parent::__construct($constraints, $allowNull);
	}
	
	public function editInput($originalValue)
	{
		if ($originalValue instanceof $this->type) return $originalValue;
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
