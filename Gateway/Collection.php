<?php

namespace Suburb\Gateway;

use Suburb\Gateway;
use Suburb\Gateway\Collection;
use Suburb\Gateway\Collection\Exception;
use Suburb\Gateway\IFactory;
use Suburb\Helper\ArrayType;

class Collection
implements \ArrayAccess, \Iterator, \Countable
{
	
	private $data;
	private $objects = [];
	private $position = 0;
	
	public function __construct(Gateway $gateway, $className, array $data, array $objects = null)
	{
		
		if (!isset($className)) throw new Exception(Exception::MISSING_CONSTRUCTOR_ARGUMENT);
		
		if (!class_exists($className)) {
			throw new Exception(Exception::INVALID_CLASS_NAME, "Class name: $className");
		}
		
		foreach ($data as $entry) {
			if (!is_array($entry) || !ArrayType::isAssociative($entry)) {
				throw new Exception(Exception::DATA_IS_NOT_ARRAY_OF_ASSOCIATIVE_ARRAYS);
			}
		}
		
		$this->gateway = $gateway;
		$this->className = ltrim($className, '\\');
		$this->data = $data;
		
		if (isset($objects)) {
			
			if (count($data) != count($objects)) {
				throw new Exception(
					Exception::DATA_AND_OBJECT_COUNT_MISMATCH,
					'Data array count: ' . count($data) . ', Object array count: ' . count($objects)
				);
			}
			
			foreach ($objects as $object) {
				if (isset($object) && get_class($object) != $this->className) {
					throw new Exception(
						Exception::INVALID_OBJECT,
						'Object type: ' . get_class($object) . ", Expected class: $this->className"
					);
				}
			}
			
			$this->objects = $objects;
			
		}
		
	}
	
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
	
	public function offsetGet($offset)
	{
		
		if (!isset($this->objects[$offset])) {
			
			$object = $this->gateway->getObject(
				$this->className,
				$this->data[$offset]
			);
			
			if (get_class($object) != $this->className) {
				$extraInformation = (gettype($object) == 'object')
					? 'Class: ' . get_class($object)
					: 'Returned type: ' . gettype($object);
				throw new Exception(
					Exception::INVALID_GATEWAY_OBJECT,
					$extraInformation
				);
			}
			
			$this->objects[$offset] = $object;
			
		}
		
		return $this->objects[$offset];
		
	}
	
	public function offsetSet($offset, $value)
	{
		throw new Exception(Exception::COLLECTION_ENTRY_CANNOT_BE_MANUALLY_CHANGED);
	}
	
	public function offsetUnset($offset)
	{
		throw new Exception(Exception::COLLECTION_ENTRY_CANNOT_BE_MANUALLY_CHANGED);
	}
	
	public function count()
	{
		return count($this->data);
	}
	
	public function current()
	{
		return $this->offsetGet($this->position);
	}
	
	public function key()
	{
		return $this->position;
	}
	
	public function next()
	{
		$this->position++;
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function valid()
	{
		return $this->offsetExists($this->position);
	}
	
}
