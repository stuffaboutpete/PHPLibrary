<?php

namespace PO\Helper;

final class StringType
{
	
	const SET_LOWER		= 'abcdefghijklmnopqrstuvwxyz';
	const SET_UPPER		= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const SET_NUMBERS	= '0123456789';
	
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
	
	public static function createRandom($length = 1, $charSets = [StringType::SET_LOWER])
	{
		// @todo Throw if length not positive iteger
		if (!is_array($charSets)) $charSets = [$charSets];
		$allChars = '';
		foreach ($charSets as $charSet) {
			// @todo Throw if charSet not a string
			$allChars .= $charSet;
		}
		$allCharsLength = strlen($allChars);
		// @todo Throw if allCharsLength is 0;
		$return = '';
		for ($i = 0; $i < $length; $i++) {
			$return .= substr($allChars, mt_rand(0, $allCharsLength - 1), 1);
		}
		return $return;
	}
	
}
