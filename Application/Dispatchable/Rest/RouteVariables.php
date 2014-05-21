<?php

namespace PO\Application\Dispatchable\Rest;

use PO\Helper\ArrayType as ArrayHelper;

class RouteVariables
implements \ArrayAccess
{
	
	private $data;
	
	public function __construct(array $data)
	{
		if (!ArrayHelper::isAssociative($data)) {
			// @todo Custom exception please
			throw new \InvalidArgumentException('Data must be associative');
		}
		$this->data = $data;
	}
	
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return $this->data[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		// @todo Custom exception please
		throw new \BadMethodCallException('This data is read only and cannot be set');
	}
	
	public function offsetUnset($offset)
	{
		// @todo Custom exception please
		throw new \BadMethodCallException('This data is read only and cannot be unset');
	}
	
	
}
