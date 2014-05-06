<?php

namespace Suburb\Helper;

require_once dirname(__FILE__) . '/StringType.php';

class StringTypeTest
extends \PHPUnit_Framework_TestCase {
	
	public function setUp()
	{
		parent::setUp();
	}
	
	public function tearDown()
	{
		parent::tearDown();
	}
	
	public function testStringCanBeConvertedFromCamelCaseToUnderscored()
	{
		$string = 'myCamelCasedString';
		$this->assertEquals('my_camel_cased_string', StringType::camelCaseToUnderscore($string));
	}
	
	public function testCamelCasedStringBeginningWithCapitalLetterDoesNotHaveLeadingUnderscore()
	{
		$string = 'MyCamelCasedString';
		$this->assertEquals('my_camel_cased_string', StringType::camelCaseToUnderscore($string));
	}
	
	public function testStringCanBeConvertedFromUnderscoredToCamelCase()
	{
		$string = 'my_underscored_string';
		$this->assertEquals('myUnderscoredString', StringType::underscoreToCamelCase($string));
	}
	
	public function testLeadingUnderscoreIsRemovedWhenConvertingToCamelCase()
	{
		$string = '_my_underscored_string';
		$this->assertEquals('myUnderscoredString', StringType::underscoreToCamelCase($string));
	}
	
	public function testTrailingUnderscoreIsRemovedWhenConvertingToCamelCase()
	{
		$string = 'my_underscored_string_';
		$this->assertEquals('myUnderscoredString', StringType::underscoreToCamelCase($string));
	}
	
}
