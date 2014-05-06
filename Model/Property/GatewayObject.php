<?php

namespace Suburb\Model\Property;

use Suburb\Gateway;
use Suburb\Model;
use Suburb\Model\Property;
use Suburb\Model\Property\GatewayObject\Exception;

class GatewayObject
extends Property
{
	
	private $targetClassName;
	private $childProperty;
	private $gateway;
	
	public function __construct($targetClassName, Property $childProperty, Gateway $gateway)
	{
		if (!class_exists($targetClassName)) {
			throw new Exception(
				Exception::INVALID_CLASS_NAME_PROVIDED,
				"Class name: $targetClassName"
			);
		}
		$this->targetClassName = ltrim($targetClassName, '\\');
		$this->childProperty = $childProperty;
		$this->gateway = $gateway;
	}
	
	public function editInput($originalValue)
	{
		if (is_object($originalValue)) {
			if ($originalValue instanceof $this->targetClassName) {
				return $originalValue;
			}
			throw new Exception(
				Exception::INVALID_OBJECT_TYPE_PROVIDED_AS_PROPERTY_VALUE,
				"Nominated object type: $this->targetClassName, Provided object type: " .
				get_class($originalValue)
			);
		}
		return $this->childProperty->set($originalValue);
	}
	
	public function editOutput($savedValue)
	{
		if ($savedValue instanceof Property) {
			$this->set($this->gateway->fetch($this->targetClassName, $savedValue->get()));
			return $this->get();
		}
		return $savedValue;
	}
	
}
