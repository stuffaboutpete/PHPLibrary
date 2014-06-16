<?php

namespace PO;

use PO\Gateway\IFactory;
use PO\Gateway\IQueryProvider;
use PO\Gateway\Collection;
use PO\Helper\ArrayType;

class Gateway
{
	
	private $connection;
	private $collectionFactory;
	private $types = [];
	private $queryCache = [];
	private $objectCache = [];
	
	public function __construct(\PDO $connection, Collection\Factory $collectionFactory)
	{
		// @todo Erm, is this right? Should we be catching pdo errors?
		$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->connection = $connection;
		$this->collectionFactory = $collectionFactory;
	}
	
	public function addType(
		/* string */	$className,
		IFactory		$factory,
		IQueryProvider	$queryProvider,
		array			$keys = ['id']
	)
	{
		
		$className = ltrim($className, '\\');
		
		if (!class_exists($className)) {
			throw new Gateway\Exception(Gateway\Exception::CLASS_TYPE_DOES_NOT_EXIST, $className);
		}
		
		if ($factory->approveClass($className) !== true) {
			throw new Gateway\Exception(
				Gateway\Exception::SUPPLIED_FACTORY_DOES_NOT_WORK_WITH_SUPPLIED_CLASS,
				"Class name: $className, Factory: " . get_class($factory)
			);
		}
		
		if ($queryProvider->approveClass($className) !== true) {
			throw new Gateway\Exception(
				Gateway\Exception::SUPPLIED_QUERY_PROVIDER_DOES_NOT_WORK_WITH_SUPPLIED_CLASS,
				"Class name: $className, Query Provider: " . get_class($queryProvider)
			);
		}
		
		$this->types[$className] = [
			'factory'				=> $factory,
			'queryProvider'			=> $queryProvider,
			'keys'					=> $keys,
			'statements'			=> [],
			'queriedForStatements'	=> false
		];
		
		$this->objectCache[$className] = [];
		
	}
	
	public function __call($method, $arguments = null)
	{
		
		if (substr($method, 0, 5) != 'fetch') throw new \BadMethodCallException($method);
		
		$className = ltrim($arguments[0], '\\');
		
		if (!isset($className)) throw new Gateway\Exception(
			Gateway\Exception::CLASS_TYPE_NOT_SPECIFIED
		);
		
		if (!class_exists($className)) {
			throw new Gateway\Exception(
				Gateway\Exception::CLASS_TYPE_DOES_NOT_EXIST,
				'Provided argument: ' . $className
			);
		}
		
		if (!$this->getType($className)) {
			throw new Gateway\Exception(
				Gateway\Exception::CLASS_TYPE_NOT_REGISTERED,
				'Provided class type: ' . $className
			);
		}
		
		$key = substr($method, 5);
		
		if ($key === false) {
			$key = 'single';
		} else if ($key == 'All') {
			$key = 'all';
		} else if (substr($key, 0, 2) == 'By') {
			$key = lcfirst(substr($key, 2));
		} else {
			throw new \Exception('Naaaah');
		}
		
		$type = $this->getType($className);
		if (!$type['queriedForStatements']) $type = $this->queryForStatements($type);
		
		if (!isset($type['queries'][$key])) {
			throw new Gateway\Exception(
				Gateway\Exception::QUERY_KEY_NOT_RECOGNISED,
				"Method called: $method. Therefore query key: $key"
			);
		}
		
		$query = $type['queries'][$key];
		
		preg_match_all('/(?::([A-Za-z0-9_]*))/', $query['query'], $preparedKeys);
		$preparedKeys = $preparedKeys[1];
		
		if (count($preparedKeys) == 0) {
			
			if ($this->returnedDataIsInQueryCache($query['query'], [])) {
				return $this->getReturnedDataFromQueryCache($query['query'], []);
			}
			
			if ($this->queryIsInCache($query['query'])) {
				$statement = $this->getQuery($query['query'])['statement'];
			} else {
				$statement = $this->connection->query($query['query']);
			}
			
		} else {
			
			if (count($arguments) != count($preparedKeys) + 1) {
				throw new Gateway\Exception(
					Gateway\Exception::INCORRECT_ARGUMENT_COUNT_FOR_SELECTED_QUERY,
					'Provided parameter count: ' . (count($arguments) - 1) .
					". Expected parameters: '" . implode("', '", $preparedKeys) . "'"
				);
			}
			
			$preparedData = [];
			
			for ($i = 0; $i < count($preparedKeys); $i++) {
				$preparedData[$preparedKeys[$i]] = $arguments[$i + 1];
			}
			
			if ($this->returnedDataIsInQueryCache($query['query'], $preparedData)) {
				return $this->getReturnedDataFromQueryCache($query['query'], $preparedData);
			}
			
			if ($this->queryIsInCache($query['query'])) {
				$statement = $this->getQuery($query['query'])['statement'];
			} else {
				$statement = $this->connection->prepare($query['query']);
			}
			
			$statement->execute($preparedData);
			
		}
		
		if ($query['single']) {
			
			$data = $statement->fetch(\PDO::FETCH_ASSOC);
			
			// This block not tested...
			if ($data === false) {
				throw new Gateway\Exception(
					Gateway\Exception::NO_DATA_FOUND,
					'Query: ' . $query['query'] .
					'; Data: ' . (isset($preparedData) ? implode(', ', $preparedData) : 'none')
				);
			}
			
			$keyValues = $this->getKeyValuesFromData($className, $data);
			
			$object = $this->getObject($className, $data);
			
			$this->addToQueryCache(
				$query['query'],
				$statement,
				(isset($preparedKeys) ? $preparedKeys : []),
				$object
			);
			
			return $object;
			
		} else {
			
			$data = $statement->fetchAll(\PDO::FETCH_ASSOC);
			
			$cachedObjects = [];
			
			foreach ($data as $entry) {
				$keyValues = $this->getKeyValuesFromData($className, $entry);
				array_push(
					$cachedObjects,
					($this->dataIsInObjectCache($className, $keyValues)
						? $this->getDataFromObjectCache($className, $keyValues)['object']
						: null
					)
				);
			}
			
			$collection = $this->collectionFactory->build($this, $className, $data, $cachedObjects);
			
			if (!$collection instanceof Collection) {
				throw new Gateway\Exception(
					Gateway\Exception::COLLECTION_OBJECT_NOT_CREATED,
					'Returned type: ' . getType($collection)
				);
			}
			
			$this->addToQueryCache(
				$query['query'],
				$statement,
				(isset($preparedKeys) ? $preparedKeys : []),
				$collection
			);
			
			return $collection;
			
		}
		
	}
	
	public function getObject($className, $data)
	{
		
		$className = ltrim($className, '\\');
		
		if (!isset($className)) throw new Gateway\Exception(
			Gateway\Exception::CLASS_TYPE_NOT_SPECIFIED
		);
		
		if (!class_exists($className)) {
			throw new Gateway\Exception(
				Gateway\Exception::CLASS_TYPE_DOES_NOT_EXIST,
				'Provided argument: ' . $className
			);
		}
		
		if (!$this->getType($className)) {
			throw new Gateway\Exception(
				Gateway\Exception::CLASS_TYPE_NOT_REGISTERED,
				'Provided class type: ' . $className
			);
		}
		
		$keyValues = $this->getKeyValuesFromData($className, $data);
		
		if ($this->dataIsInObjectCache($className, $keyValues)) {
			return $this->getDataFromObjectCache($className, $keyValues)['object'];
		}
		
		$type = $this->getType($className);
		
		$object = $type['factory']->build($data);
		
		if (get_class($object) != $className) {
			throw new Gateway\Exception(
				Gateway\Exception::FACTORY_CREATED_OBJECT_IS_NOT_EXPECTED_TYPE,
				'Object type: ' . get_class($object) .
				', Expected type: ' . $className .
				', Factory: ' . get_class($type['factory'])
			);
		}
		
		$this->addToObjectCache($className, $data, $object);
		
		return $object;
		
	}
	
	public function save($object, array $nestedSaves = null)
	{
		$this->saveOrDelete($object, 'save');
		// @todo Validate nestedSaves format
		if (is_array($nestedSaves)) {
			foreach ($nestedSaves as $nestMethod) {
				// @todo Validate method exists
				$nestedData = $object->$nestMethod();
				if (!is_array($nestedData)) $nestedData = [$nestedData];
				foreach ($nestedData as $data) $this->save($data);
			}
		}
	}
	
	public function delete($object)
	{
		$this->saveOrDelete($object, 'delete');
	}
	
	private function saveOrDelete($object, $saveOrDelete)
	{
		
		$type = $this->getType(get_class($object));
		
		if (!$type) {
			throw new Gateway\Exception(
				Gateway\Exception::CLASS_TYPE_NOT_REGISTERED,
				'Provided object type: ' . get_class($object)
			);
		}
		
		$rawData = $type['factory']->dismantle($object);
		
		if ($saveOrDelete == 'save') {
			
			$query = $type['queryProvider']->getSavePreparedStatement(
				$type['keys'],
				array_keys($rawData)
			);
			$invalidStatement = (!preg_match('/insert/i', $query)
				|| !preg_match('/on duplicate key update/i', $query))
				? Gateway\Exception::INVALID_SAVE_STATEMENT : false;
				
		} else {
			
			$query = $type['queryProvider']->getDeletePreparedStatement($type['keys']);
			$invalidStatement = (!preg_match('/delete/i', $query))
				? Gateway\Exception::INVALID_DELETE_STATEMENT : false;
				
		}
		
		if ($invalidStatement) {
			throw new Gateway\Exception($invalidStatement, "Query: $query");
		}
		
		preg_match_all('/(?::([A-Za-z0-9_]*))/', $query, $preparedKeys);
		$preparedKeys = $preparedKeys[1];
		$dataKeys = array_keys($rawData);
		
		if ($saveOrDelete == 'save') {
			
			if (!ArrayType::containSameElements($preparedKeys, $dataKeys)) {
				throw new Gateway\Exception(
					Gateway\Exception::DISMANTLED_OBJECT_KEYS_DO_NOT_MATCH_PARAMETERS_IN_QUERY,
					'Data keys: ' . implode(', ', $dataKeys) .
					' Statement keys: ' . implode(', ', $preparedKeys)
				);
			}
			
		} else {
			
			if (!ArrayType::containSameElements($preparedKeys, $type['keys'])) {
				throw new Gateway\Exception(
					Gateway\Exception::DISMANTLED_OBJECT_KEYS_DO_NOT_MATCH_PARAMETERS_IN_QUERY,
					'Data keys: ' . implode(', ', $dataKeys) .
					' Statement keys: ' . implode(', ', $preparedKeys)
				);
			}
			
			foreach ($rawData as $key => $value) {
				if (!in_array($key, $type['keys'])) unset($rawData[$key]);
			}
			
		}
		
		$statement = $this->connection->prepare($query);
		
		$statement->execute($rawData);
		
		if ($saveOrDelete == 'save') {
			$this->addToObjectCache(get_class($object), $rawData, $object);
		}
		
	}
	
	private function getType($className)
	{
		return isset($this->types[$className]) ? $this->types[$className] : null;
	}
	
	private function queryForStatements($type)
	{
		$queryProvider = $type['queryProvider'];
		$singleQueries = $this->checkQueries(
			$queryProvider->getSingleSelectPreparedStatements($type['keys'])
		);
		foreach ($singleQueries as $key => $statement) {
			$type['queries'][$key] = [
				'query'		=> $statement,
				'single'	=> true
			];
		}
		
		$multipleQueries = $this->checkQueries(
			$queryProvider->getMultipleSelectPreparedStatements($type['keys'])
		);
		foreach ($multipleQueries as $key => $statement) {
			$type['queries'][$key] = [
				'query'		=> $statement,
				'single'	=> false
			];
		}
		$type['queriedForStatements'] = true;
		return $type;
	}
	
	private function checkQueries($queries)
	{
		if (!is_array($queries) || !ArrayType::isAssociative($queries)) {
			throw new Gateway\Exception(
				Gateway\Exception::QUERIES_NOT_PROVIDED_AS_ASSOCIATIVE_ARRAY
			);
		}
		return $queries;
	}
	
	private function addToQueryCache($query, $statement, $parameters, $returnedData)
	{
		if ($this->returnedDataIsInQueryCache($query, $parameters)) return;
		if (!$this->queryIsInCache($query)) {
			array_push($this->queryCache, [
				'query'		=> $query,
				'statement'	=> $statement,
				'instances'	=> []
			]);
		}
		foreach ($this->queryCache as &$entry) {
			if ($entry['query'] == $query) {
				array_push(
					$entry['instances'],
					[
						'parameters'	=> $parameters,
						'returnedData'	=> $returnedData
					]
				);
				return;
			}
		}
	}
	
	private function queryIsInCache($query)
	{
		return ($this->getQuery($query) !== null);
	}
	
	private function getQuery($query)
	{
		foreach ($this->queryCache as $entry) {
			if ($entry['query'] == $query) return $entry;
		}
	}
	
	private function returnedDataIsInQueryCache($query, $parameters)
	{
		return ($this->getReturnedDataFromQueryCache($query, $parameters) !== null);
	}
	
	private function getReturnedDataFromQueryCache($query, $parameters)
	{
		foreach ((array) $this->getQuery($query)['instances'] as $entry) {
			if ($entry['parameters'] === $parameters) return $entry['returnedData'];
		}
	}
	
	private function addToObjectCache($className, $rawData, $object = null)
	{
		array_push(
			$this->objectCache[$className],
			[
				'keyValues'	=> $this->getKeyValuesFromData($className, $rawData),
				'rawData'	=> $rawData,
				'object'	=> $object
			]
		);
	}
	
	private function dataIsInObjectCache($className, $keyValues)
	{
		return ($this->getDataFromObjectCache($className, $keyValues) !== null);
	}
	
	private function getDataFromObjectCache($className, $keyValues)
	{
		foreach ((array) $this->objectCache[$className] as $data) {
			if (ArrayType::containSameElements($data['keyValues'], $keyValues)) return $data;
		}
	}
	
	private function getTypeKeys($className)
	{
		return $this->types[$className]['keys'];
	}
	
	private function getKeyValuesFromData($className, $data)
	{
		return array_intersect_key($data, array_flip($this->getTypeKeys($className)));
	}
	
	// @todo Untested method
	public function getFactory($className)
	{
		return $this->getType($className)['factory'];
	}
	
	public function typeIsRegistered($className)
	{
		return (null !== $this->getType($className));
	}
	
}
