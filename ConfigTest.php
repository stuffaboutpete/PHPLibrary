<?php

namespace Suburb;

require_once dirname(__FILE__) . '/Config.php';

class ConfigTest
extends \PHPUnit_Framework_TestCase {
	
	// @todo Can mix camelcase\underscore
	
	public function testConfigRequiresDataToBeInstantiated()
	{
		$this->setExpectedException('\PHPUnit_Framework_Error');
		$config = new Config();
	}
	
	public function testConfigCanBeInstantiatedWithArray()
	{
		$config = new Config(['key' => 'value']);
		$this->assertInstanceOf('Suburb\Config', $config);
	}
	
	public function testConstructorArrayMustBeAssociative()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['one', 'two']);
	}
	
	public function testConstructorArrayCannotBeEmpty()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config([]);
	}
	
	public function testConfigCanBeInstantiatedWithJSONString()
	{
		$config = new Config('{"key":"value"}');
		$this->assertInstanceOf('Suburb\Config', $config);
	}
	
	public function testConstructorDoesNotAcceptNonJSONString()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config('string');
	}
	
	public function testConstructorDoesNotAcceptNonStringOrArrayAsData()
	{
		$stdObject = new \stdClass();
		$stdObject->key = 'value';
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config($stdObject);
	}
	
	public function testConstructorJSONMustBeValidJSON()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config('{key:"value"}'); // Missing quotes around key
	}
	
	public function testConstructorJSONMustHaveObjectAsRootElement()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config('["value"]');
	}
	
	public function testConstructorJSONObjectCannotBeEmpty()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config('{}');
	}
	
	public function testDataCanBeRetrievedByKey()
	{
		$config = new Config(['key' => 'value']);
		$this->assertEquals('value', $config->get('key'));
	}
	
	public function testJSONDataCanBeRetrievedByKey()
	{
		$config = new Config('{"key":"value"}');
		$this->assertEquals('value', $config->get('key'));
	}
	
	public function testNonAssociativeArrayCanBeRetrievedByKey()
	{
		$array = ['value'];
		$config = new Config(['array' => $array]);
		$this->assertEquals($array, $config->get('array'));
	}
	
	public function testAssociativeArrayCanBeRetrievedByKey()
	{
		$array = ['key' => 'value'];
		$config = new Config(['array' => $array]);
		$this->assertEquals($array, $config->get('array'));
	}
	
	public function testNestedKeyCanBeRetrieved()
	{
		$config = new Config([
			'array' => ['key' => 'value']
		]);
		$this->assertEquals('value', $config->get('array\key'));
	}
	
	public function testMultiNestedKeyCanBeRetrieved()
	{
		$config = new Config([
			'one' => [
				'two' => [
					'three' => [
						'four' => 'value'
					]
				]
			]
		]);
		$this->assertEquals('value', $config->get('one\two\three\four'));
	}
	
	public function testGetRequiresString()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value']);
		$config->get(123);
	}
	
	public function testExceptionIsThrownIfNoValueCanBeFound()
	{
		$this->setExpectedException('\OutOfBoundsException');
		$config = new Config(['key' => 'value']);
		$config->get('invalid_key');
	}
	
	public function testExceptionIsThrownIfNoNestedValueCanBeFound()
	{
		$this->setExpectedException('\OutOfBoundsException');
		$config = new Config([
			'one' => [
				'two' => [
					'three' => [
						'four' => 'value'
					]
				]
			]
		]);
		$config->get('one\two\three\invalid_key');
	}
	
	public function testExceptionIsThrownIfKeyContainsBackSlash()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['my\key' => 'value']);
	}
	
	public function testConfigCanBeInstantiatedWithEnvironmentsArray()
	{
		$config = new Config(['key' => 'value'], ['live' => 'livesite.com']);
		$this->assertInstanceOf('Suburb\Config', $config);
	}
	
	public function testEnvironmentsArrayMustBeAssociative()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value'], ['live', 'stage']);
	}
	
	public function testEnvironmentsArrayCannotBeEmpty()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value'], []);
	}
	
	public function testEnvironmentsArrayCannotContainKeysContainingColons()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value'], ['li:ve' => 'livesite.com']);
	}
	
	public function testEnvironmentsArrayMustContainOnlyStrings()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value'], [
			'live' => 'livesite.com',
			'stage' => ['stagesite.com']
		]);
	}
	
	public function testKeyCanBeDeclaredAgainstAnEnvironmentAndWillBeMatchedAgainstHttpHost()
	{
		$config = new Config([
			'live:key'	=> 'live value',
			'stage:key'	=> 'stage value',
			'dev:key'	=> 'dev value'
		], [
			'live'	=> 'livesite.com',
			'stage'	=> 'stagesite.com',
			'dev'	=> 'localhost'
		]);
		$_SERVER['HTTP_HOST'] = 'livesite.com';
		$this->assertEquals('live value', $config->get('key'));
		$_SERVER['HTTP_HOST'] = 'stagesite.com';
		$this->assertEquals('stage value', $config->get('key'));
		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEquals('dev value', $config->get('key'));
	}
	
	public function testEnvironmentValuesCanBeInNestedKeys()
	{
		$config = new Config([
			'one' => [
				'two' => [
					'live:three'	=> 'live value',
					'stage:three'	=> 'stage value',
					'dev:three'		=> 'dev value'
				]
			]
		], [
			'live'	=> 'livesite.com',
			'stage'	=> 'stagesite.com',
			'dev'	=> 'localhost'
		]);
		$_SERVER['HTTP_HOST'] = 'livesite.com';
		$this->assertEquals('live value', $config->get('one\two\three'));
		$_SERVER['HTTP_HOST'] = 'stagesite.com';
		$this->assertEquals('stage value', $config->get('one\two\three'));
		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEquals('dev value', $config->get('one\two\three'));
	}
	
	public function testSpecificEnvironmentValueIsUsedOverNonEnvironmentValue()
	{
		$config = new Config([
			'key'		=> 'default value',
			'live:key'	=> 'live value'
		], [
			'live'	=> 'livesite.com'
		]);
		$_SERVER['HTTP_HOST'] = 'livesiteNOTREALLY.com';
		$this->assertEquals('default value', $config->get('key'));
		$_SERVER['HTTP_HOST'] = 'livesite.com';
		$this->assertEquals('live value', $config->get('key'));
	}
	
	public function testDefaultValueIsUsedIfNoEnvironmentValueIsAvailable()
	{
		$config = new Config([
			'key'		=> 'default value',
			'stage:key'	=> 'stage value'
		], [
			'stage'	=> 'stagesite.com',
			'live'	=> 'livesite.com'
		]);
		$_SERVER['HTTP_HOST'] = 'livesite.com';
		$this->assertEquals('default value', $config->get('key'));
	}
	
	public function testDefaultValueIsUsedIfNoHttpHostIsProvided()
	{
		$config = new Config([
			'key'		=> 'default value',
			'stage:key'	=> 'stage value'
		], [
			'stage'	=> 'stagesite.com',
			'live'	=> 'livesite.com'
		]);
		$this->assertEquals('default value', $config->get('key'));
	}
	
	public function testArrayOfKeysCanBeObtained()
	{
		$config = new Config([
			'key1'	=> 'value 1',
			'key2'	=> 'value 2',
			'key3'	=> 'value 3'
		]);
		$keys = $config->getKeys();
		$this->assertTrue(in_array('key1', $keys));
		$this->assertTrue(in_array('key2', $keys));
		$this->assertTrue(in_array('key3', $keys));
	}
	
	public function testGetKeysRequiresString()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$config = new Config(['key' => 'value']);
		$config->getKeys(123);
	}
	
	public function testArrayOfKeysInSubLevelCanBeObtained()
	{
		$config = new Config([
			'one' => [
				'two' => [
					'three' => [
						'key1'	=> 'value 1',
						'key2'	=> 'value 2',
						'key3'	=> 'value 3'
					]
				]
			]
		]);
		$keys = $config->getKeys('one\two\three');
		$this->assertTrue(in_array('key1', $keys));
		$this->assertTrue(in_array('key2', $keys));
		$this->assertTrue(in_array('key3', $keys));
	}
	
	public function testEnvironmentKeysAreRemovedWhenGettingArray()
	{
		$config = new Config([
			'array' => [
				'dev:key'	=> 'value 1',
				'live:key'	=> 'value 2'
			]
		], [
			'dev'	=> 'localhost',
			'live'	=> 'livesite.com'
		]);
		$_SERVER['HTTP_HOST'] = 'livesite.com';
		$this->assertEquals(1, count($config->get('array')));
		$this->assertEquals('value 2', $config->get('array')['key']);
	}
	
	public function testEnvironmentKeysAreOnlyReturnedOnceFromGetKeys()
	{
		$config = new Config([
			'key'			=> 'value 1',
			'live:key'		=> 'value 2',
			'live:key2'		=> 'value 3',
			'stage:key2'	=> 'value 4'
		]);
		$keys = $config->getKeys();
		$this->assertEquals(2, count($keys));
		$this->assertTrue(in_array('key', $keys));
		$this->assertTrue(in_array('key2', $keys));
	}
	
}