<?php

namespace Suburb\Helper;

final class StringType
{
	
	private function __construct(){}
	
	public static function camelCaseToUnderscore($string)
	{
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
	}
	
	public static function underscoreToCamelCase($string)
	{
		$values = explode('_', $string);
		array_walk($values, function(&$value){
			$value = ucfirst($value);
		});
		return lcfirst(implode('', $values));
	}
	
}
