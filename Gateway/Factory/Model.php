<?php

namespace PO\Gateway\Factory;

use PO\Gateway\Factory\Model\Exception;
use PO\Gateway\IFactory;
use PO\Helper\ArrayType;
use PO\Helper\StringType;
use PO\IoCContainer;

class Model
implements IFactory
{
	
	private $className;
	private $buildMapContributors;
	private $dismantleContributors;
	private $ioCContainer;
	
	public function __construct(
		/*string */		$className,
		array			$buildMapContributors = null,
		array			$dismantleContributors = null,
		IoCContainer	$ioCContainer = null
	)
	{
		$this->className = trim($className, '\\');
		// @todo Check following contains instances of relevant interface
		$this->buildMapContributors = $buildMapContributors;
		$this->dismantleContributors = $dismantleContributors;
		$this->ioCContainer = $ioCContainer;
	}
	
	public function approveClass($class)
	{
		return ($class == $this->className);
	}
	
	public function build(array $data)
	{
		
		foreach ($data as $key => $value) {
			if (StringType::underscoreToCamelCase($key) == $key) continue;
			$data[StringType::underscoreToCamelCase($key)] = $value;
			unset($data[$key]);
		}
		
		$buildMap = [];
		
		foreach ((array) $this->buildMapContributors as $contributor) {
			
			$map = $contributor->getMap();
			
			if (!is_null($map) && (!is_array($map) || !ArrayType::isAssociative($map))) {
				throw new Exception(
					Exception::BUILD_MAP_SUPPLIED_MUST_BE_ASSOCIATIVE_ARRAY_OR_NULL,
					'Argument type: ' . gettype($buildMap)
				);
			}
			
			if (is_array($map)) $buildMap = array_merge($buildMap, $map);
			
		}
		
		foreach (array_count_values((array) $buildMap) as $value => $count) {
			if ($count == 1) continue;
			$targetKeys = array_keys($buildMap, $value);
			$keysUsed = [];
			foreach ($targetKeys as $key) {
				if (isset($data[$key])) array_push($keysUsed, $key);
			}
			if (count($keysUsed) > 1) {
				throw new Exception(
					Exception::CONFLICTING_BUILD_MAP_KEYS_USED,
					'Keys used: ' . implode(', ', $keysUsed)
				);
			}
		}
		
		foreach ((array) $buildMap as $originalKey => $newKey) {
			if (array_key_exists($originalKey, $data)) {
				$data[$newKey] = $data[$originalKey];
				unset($data[$originalKey]);
			}
		}
		
		if (isset($this->ioCContainer)) {
			return $this->ioCContainer->resolve($this->className, [$data]);
		} else {
			return new $this->className($data);
		}
		
	}
	
	public function dismantle($object)
	{
		
		$properties = $object->getPropertyNames();
		$values = [];
		
		foreach ($properties as $property) {
			$method = 'get' . ucfirst($property);
			$values[$property] = $object->$method();
		}
		
		foreach ($values as $key => $value) {
			if (StringType::camelCaseToUnderscore($key) == $key) continue;
			$values[StringType::camelCaseToUnderscore($key)] = $value;
			unset($values[$key]);
		}
		
		foreach ((array) $this->dismantleContributors as $contributor) {
			
			$complexValues = $contributor->dismantle($values);
			
			// @todo Check complex values is null or associative array
			// Check no extra properties have been created
			// Check no properties are missing
			
			if (is_array($complexValues)) $values = array_merge($values, $complexValues);
		}
		
		foreach ((array) $this->dismantleContributors as $contributor) {
			
			$unusedKeys = $contributor->getUnusedKeys($values);
			
			foreach ((array) $unusedKeys as $key) {
				unset($values[$key]);
			}
			
		}
		
		foreach ($values as $property => $value) {
			if (is_object($value)) {
				throw new Exception(
					Exception::COMPLEX_PROPERTY_NOT_DISMANTLED,
					"Property: $property, Class type: " . get_class($value)
				);
			}
		}
		
		return $values;
		
	}
	
}
