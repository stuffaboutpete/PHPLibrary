<?php

namespace PO\Gateway\QueryProvider;

use PO\Gateway\IQueryProvider;

class Simple
implements IQueryProvider
{
	
	private $className;
	private $tableName;
	
	public function __construct($className, $tableName)
	{
		$this->className = trim($className, '\\');
		$this->tableName = $tableName;
	}
	
	public function approveClass($class)
	{
		return ($class == $this->className);
	}
	
	public function getSingleSelectPreparedStatements($keys)
	{
		$preparedKeys = array_map(function($key){
			return "$key = :$key";
		}, $keys);
		return [
			'single' => "SELECT * FROM $this->tableName WHERE " . implode(' AND ', $preparedKeys)
		];
	}
	
	public function getMultipleSelectPreparedStatements($keys)
	{
		return [
			'all' => "SELECT * FROM $this->tableName"
		];
	}
	
	public function getSavePreparedStatement($keys, $allFields)
	{
		$otherFields = array_diff($allFields, $keys);
		$updateStrings = array_map(function($field){
			return "`$field` = VALUES(`$field`)";
		}, $otherFields);
		return "INSERT INTO $this->tableName (`" . implode('`, `', $allFields) . '`) ' .
			'VALUES (:' . implode(', :', $allFields) . ') ' .
			'ON DUPLICATE KEY UPDATE ' . implode(', ', $updateStrings);
	}
	
	public function getDeletePreparedStatement($keys)
	{
		$preparedKeys = array_map(function($key){
			return "`$key` = :$key";
		}, $keys);
		return "DELETE FROM $this->tableName WHERE " . implode(' AND ', $preparedKeys);
	}
	
}
