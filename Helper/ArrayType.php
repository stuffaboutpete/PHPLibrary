<?php

namespace PO\Helper;

final class ArrayType
{
	
	private function __construct(){}
	
	public static function isAssociative(array $array)
	{
		// http://stackoverflow.com/questions/173400/php-arrays-a-good-way- ...
		// to-check-if-an-array-is-associative-or-sequential/4254008#4254008
		if (count($array) == 0) return true;
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
	
	public static function containSameElements(array $array1, array $array2)
	{
		// http://stackoverflow.com/questions/1404114/php-built-in-function- ...
		// to-check-whether-two-array-values-are-equal-ignoring-th
		return (!array_diff($array1, $array2) && !array_diff($array2, $array1));
	}
	
}
