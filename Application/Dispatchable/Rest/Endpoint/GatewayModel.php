<?php

namespace PO\Application\Dispatchable\Rest\Endpoint;

use PO\Application\Dispatchable\Rest\Endpoint\GatewayModel\Exception;
use PO\Application\Dispatchable\Rest\IEndpoint;
use PO\Application\Dispatchable\Rest\RouteVariables;
use PO\Gateway;
use PO\Gateway\IFactory;
use PO\Helper\ArrayType as ArrayHelper;
use PO\Helper\StringType as StringHelper;
use PO\Http;

class GatewayModel
implements IEndpoint
{
	
	private $gateway;
	private $targetClass;
	private $selectMap;
	private $saveMap;
	private $factories = [];
	
	public function __construct(
		Gateway			$gateway,
		/* string */	$targetClass,
		array			$selectMap = null,
		array			$saveMap = null
	)
	{
		if (!$gateway->typeIsRegistered($targetClass)) {
			throw new Exception(
				Exception::TARGET_CLASS_MUST_BE_REGISTERED_WITH_PROVIDED_GATEWAY,
				'Target class: ' . $targetClass
			);
		}
		$this->gateway = $gateway;
		$this->targetClass = $targetClass;
		$this->validateSelectMap($selectMap);
		$this->selectMap = $selectMap;
		$this->saveMap = $saveMap;
	}
	
	public function dispatch(
		Http\Request	$request,
		Http\Response	$response,
		RouteVariables	$routeVariables
	)
	{
		
		$requestMethod = $request->getRequestMethod();
		
		switch ($requestMethod) {
			
			case 'POST':
				$this->post($request, $response, $routeVariables);
			break;
			
			case 'PUT':
				$this->put($request, $response, $routeVariables);
			break;
			
			case 'DELETE':
				$this->delete($response, $routeVariables);
			break;
			
			default:
				$this->get($response, $routeVariables);
			
		}
		
	}
	
	private function get($response, $routeVariables)
	{
		
		try {
			$data = $this->loadData($routeVariables);
		} catch (Gateway\Exception $exception) {
			if ($exception->getCode() === Gateway\Exception::NO_DATA_FOUND) {
				$response->set404();
				return;
			}
			throw $exception;
		}
		
		if ($data instanceof Gateway\Collection) {
			$isCollection = true;
		} else {
			$isCollection = false;
			$data = [$data];
		}
		
		$outputData = [];
		
		for ($i = 0; $i < count($data); $i++) $outputData[$i] = $this->applySelectMap($data[$i]);
		
		$response->set200($isCollection ? $outputData : $outputData[0]);
		
	}
	
	private function post($request, $response)
	{
		$requestBody = $request->getBody();
		if (!is_array($requestBody)) {
			$response->set400([
				'error' => true,
				'message' => 'Non array request body provided'
			]);
			return;
		}
		$arrayData = (ArrayHelper::isAssociative($requestBody)) ? [$requestBody] : $requestBody;
		$objects = [];
		$dismantledObjects = [];
		foreach ($arrayData as $data) {
			try {
				$object = $this->getFactory($this->targetClass)->build($data);
			} catch (\Exception $exception) {
				$response->set400([
					'error' => true,
					'message' => "Data provided could not be used to create $this->targetClass"
				]);
				return;
			}
			array_push($objects, $object);
		}
		foreach ($objects as $object) {
			$this->gateway->save($object, $this->saveMap);
			array_push($dismantledObjects, $this->applySelectMap($object));
		}
		if (ArrayHelper::isAssociative($requestBody)) {
			$response->set201($dismantledObjects[0]);
		} else {
			$response->set201($dismantledObjects);
		}
		
	}
	
	private function put($request, $response, $routeVariables)
	{
		$requestBody = $request->getBody();
		if (!is_array($requestBody)) {
			$response->set400([
				'error' => true,
				'message' => 'Non array request body provided'
			]);
			return;
		}
		try {
			$object = $this->loadData($routeVariables);
		} catch (Gateway\Exception $exception) {
			if ($exception->getCode() === Gateway\Exception::NO_DATA_FOUND) {
				$response->set404();
				return;
			}
			throw $exception;
		}
		if ($object instanceof Gateway\Collection) {
			throw new Exception(Exception::UNEXPECTED_COLLECTION_IDENTIFIED);
		}
		foreach ($requestBody as $key => $value) {
			$method = 'set' . ucfirst(StringHelper::underscoreToCamelCase($key));
			$object->$method($value);
		}
		$this->gateway->save($object, $this->saveMap);
		$dismantledData = $this->applySelectMap($object);
		$response->set200($dismantledData);
	}
	
	private function delete($response, $routeVariables)
	{
		try {
			$data = $this->loadData($routeVariables);
		} catch (Gateway\Exception $exception) {
			if ($exception->getCode() === Gateway\Exception::NO_DATA_FOUND) {
				$response->set404();
				return;
			}
			throw $exception;
		}
		if ($data instanceof Gateway\Collection) {
			for ($i = 0; $i < count($data); $i++) {
				$this->gateway->delete($data[$i]);
			}
		} else {
			$this->gateway->delete($data);
		}
		$response->set204();
	}
	
	private function loadData($routeVariables)
	{
		
		$dataKeys = [];
		$nextKey = 1;
		
		do {
			$key = ($nextKey == 1) ? 'key' : "key_$nextKey";
			if (isset($routeVariables[$key])) {
				array_push($dataKeys, $routeVariables[$key]);
				$nextKey++;
			} else {
				$nextKey = false;
			}
		} while ($nextKey);
		
		array_unshift($dataKeys, $this->targetClass);
		
		$fetchMethod = (isset($routeVariables['fetch_key']))
			? 'fetch' . ucfirst(StringHelper::underscoreToCamelCase($routeVariables['fetch_key']))
			: 'fetch';
		
		$data = call_user_func_array([$this->gateway, $fetchMethod], $dataKeys);
		
		if ($data instanceof Gateway\Collection) return $data;
		
		if (!is_object($data)) {
			throw new Exception(
				Exception::NON_OBJECT_RETURNED_FROM_GATEWAY,
				'Returned type: ' . gettype($data)
			);
		}
		
		if (!($data instanceof $this->targetClass)) {
			throw new Exception(
				Exception::INVALID_CLASS_RETURNED_FROM_GATEWAY,
				'Returned class type: ' . get_class($data)
			);
		}
		
		return $data;
		
	}
	
	private function applySelectMap($originalData, $map = null)
	{
		if (!isset($map)) $map = $this->selectMap;
		if (!isset($map)) {
			$originalData = $this->getFactory(get_class($originalData))->dismantle($originalData);
			if (!is_array($originalData)) {
				throw new Exception(
					Exception::NON_ARRAY_RETURNED_FROM_FACTORY_DISMANTLE,
					'Returned type: ' . gettype($originalData)
				);
			}
			$map = [];
		}
		if (is_object($originalData)) {
			$factory = $this->getFactory(get_class($originalData));
			$dismantledData = $factory->dismantle($originalData);
			if (!is_array($dismantledData)) {
				throw new Exception(
					Exception::NON_ARRAY_RETURNED_FROM_FACTORY_DISMANTLE,
					'Returned type: ' . gettype($dismantledData)
				);
			}
		} else {
			$dismantledData = $originalData;
		}
		$map = array_merge(array_keys($dismantledData), $map);
		foreach ($map as $arrayKey => $arrayValue) {
			unset($direction);
			$key = (is_array($arrayValue)) ? $arrayKey : $arrayValue;
			if (in_array(substr($key, 0, 1), ['+', '-'])) {
				$direction = substr($key, 0, 1);
				$key = substr($key, 1);
			}
			if (isset($direction) && $direction == '-') {
				unset($dismantledData[$key]);
				continue;
			}
			if (isset($direction) && $direction == '+') {
				$selectMapKeyParts = explode(' as ', $key);
				$method = $selectMapKeyParts[0];
				$key = $selectMapKeyParts[1];
				if (!is_object($originalData)) {
					throw new Exception(
						Exception::METHOD_CALL_ON_NON_OBJECT_INITIATED_BY_SELECT_MAP,
						'Variable type: ' . gettype($originalData)
					);
				}
				if (!method_exists($originalData, $method)) {
					throw new Exception(
						Exception::UNKNOWN_METHOD_CALLED_BY_SELECT_MAP,
						'Object type: ' . get_class($originalData) . ', Method: ' . $method
					);
				}
				$dismantledData[$key] = $originalData->$method();
			}
			if (is_array($arrayValue)) {
				$dismantledData[$key] = $this->applySelectMap(
					$dismantledData[$key],
					$arrayValue
				);
			}
			if (is_object($dismantledData[$key])) {
				$factory = $this->getFactory(get_class($dismantledData[$key]));
				$dismantledData[$key] = $factory->dismantle($dismantledData[$key]);
			}
		}
		return $dismantledData;
	}
	
	private function validateSelectMap($map)
	{
		foreach ((array) $map as $key => $value) {
			if (is_array($value)) {
				$this->validateSelectMap($value);
				continue;
			}
			if (in_array(substr($value, 0, 1), ['+', '-'])) continue;
			if (strpos($key, ' as ')) continue;
			throw new Exception(
				Exception::INVALID_SELECT_MAP_KEY,
				"Invalid key: $value"
			);
		}
	}
	
	private function getFactory($className)
	{
		if (!isset($this->factories[$className])) {
			$this->factories[$className] = $this->gateway->getFactory($className);
			if (!($this->factories[$className] instanceof IFactory)) {
				throw new Exception(
					Exception::NON_FACTORY_RETURNED_FROM_GATEWAY,
					(is_object($this->factories[$className])
						? 'Returned class: ' . get_class($this->factories[$className])
						: 'Returned type: ' . gettype($this->factories[$className])
					)
				);
			}
			if (!$this->factories[$className]->approveClass($className)) {
				throw new Exception(
					Exception::INCORRECT_FACTORY_PROVIDED_FROM_GATEWAY,
					"Class type: $className"
				);
			}
		}
		return $this->factories[$className];
	}
	
}
