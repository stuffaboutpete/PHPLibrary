<?php

namespace Suburb;

/**
 * Config
 * 
 * Represents a read only nested array
 * structure where values can be accessed
 * by a string representation of their
 * location eg 'key1/key2/key3'
 */
class Config
{
	
	/**
	 * The contained data
	 * 
	 * @var array
	 */
	private $data;
	
	/**
	 * A list of web addresses and
	 * their associated environment key
	 * 
	 * @var array
	 */
	private $environments;
	
	/**
	 * Accepts the original data and optionally
	 * a list of environments to use whilst
	 * retrieving values
	 * 
	 * @param  array|string             $data         The config data
	 * @param  array|null               $environments A list of enviroments associated with a key
	 * @return null
	 * @throws InvalidArgumentException If data is not provided as an array or JSON string
	 * @throws InvalidArgumentException If root element of data is not an associative array
	 * @throws InvalidArgumentException If environments is not an associative array
	 * @throws InvalidArgumentException If any environment key contains colon
	 * @throws InvalidArgumentException If any environment value is not a string
	 */
	public function __construct($data, $environments = null)
	{
		
		// Throw if the data is not
		// an array or a string
		if (!is_array($data) && !is_string($data)) {
			throw new \InvalidArgumentException(
				'Data must be provided as an array or JSON formatted string'
			);
		}
		
		// Throw if string data cannot be
		// treated as JSON
		if (is_string($data)) {
			$data = json_decode($data, true);
			if (is_null($data)) {
				throw new \InvalidArgumentException(
					'If data is provided as a string, it must be valid JSON'
				);
			}
		}
		
		// Throw if data is not
		// an associative array
		if (is_array($data) && !(bool) count(array_filter(array_keys($data), 'is_string'))) {
			throw new \InvalidArgumentException(
				'Data must be a non empty associative array'
			);
		}
		
		// Throw is any key
		// contains a back slash
		foreach ($data as $key => $value) {
			if (strpos($key, '\\') > 0) {
				throw new \InvalidArgumentException('Keys cannot contain a back slash');
			}
		}
		
		// Throw is environments is not
		// an associative array
		if (is_array($environments) && !(bool) count(
			array_filter(array_keys($environments), 'is_string')
		)) {
			throw new \InvalidArgumentException(
				'Environments must be a non empty associative array'
			);
		}
		
		// Throw if any environment key
		// contains a colon or any of the
		// environment values are not strings
		foreach ((array) $environments as $key => $value) {
			if (strpos($key, ':') !== false) {
				throw new \InvalidArgumentException(
					'Environments array must not contain any keys containing a colon'
				);
			}
			if (!is_string($value)) {
				throw new \InvalidArgumentException('Environments array must contain only strings');
			}
		}
		
		$this->data = $data;
		$this->environments = (array) $environments;
		
	}
	
	/**
	 * Retrieves an array of keys at the
	 * specified level of the data structure
	 * 
	 * @param  string $level            The level of the data structure to query
	 * @return array                    An array of keys
	 * @throws InvalidArgumentException If level is not a string or null
	 */
	public function getKeys($level = '')
	{
		
		// Throw if the level is not a string
		if (!is_string($level)) {
			throw new \InvalidArgumentException('$level must be a string or null');
		}
		
		// Split the level by forward slash
		// and ensure the array is empty if
		// an empty string is provided
		$levels = explode('\\', $level);
		if (count($levels) == 1 && $levels[0] == '') $levels = [];
		
		// Set the current level of inspection
		// to the full data structure
		$currentLevel = $this->data;
		
		// Drill throught the provided level
		// strings to find the target level
		foreach ($levels as $level) {
			$currentLevel = $currentLevel[$level];
		}
		
		// Get the keys of this level
		$keys = array_keys($currentLevel);
		
		// Ensure that any keys containing
		// a colon are reduced to the content
		// to the right of the colon
		foreach ($keys as &$key) $key = array_pop(explode(':', $key));
		
		// Remove duplicate keys which result
		// from environment settings
		$keys = array_unique($keys);
		
		return $keys;
		
	}
	
	/**
	 * Retrieves the value of the data
	 * structure at the specified location
	 * 
	 * @param  string $key              The location in the data structure represented as a string
	 * @return mixed                    The discovered value
	 * @throws InvalidArgumentException If the key is not a string
	 * @throws OutOfBoundsException     If the key does not match any data
	 */
	public function get($key)
	{
		
		// Ensure the key is a string
		if (!is_string($key)) throw new \InvalidArgumentException('$key must be a string');
		
		// Set a default for the environment, then
		// search the environments array for a
		// matching key based on the http host
		$environment = '';
		foreach ($this->environments as $envKey => $value) {
			if (isset($_SERVER['HTTP_HOST']) && preg_match("/$value/", $_SERVER['HTTP_HOST'])) {
				$environment = $envKey;
				break;
			}
		}
		
		// Split the key by forward slashes,
		// count it for iteration and pull
		// in the root data structure to inspect
		$key = explode('\\', $key);
		$keyCount = count($key);
		$data = $this->data;
		
		// Loop through each of the key parts
		for ($i = 0; $i < $keyCount; $i++) {
			
			// If we are on the last part of the
			// key, we should try to return a value
			if ($i == $keyCount - 1) {
				
				// Prepend the environment key, if it is set
				$finalKey = $environment ? "$environment:$key[$i]" : $key[$i];
				
				// Return our value if it exists
				if (array_key_exists($finalKey, $data)) {
					return $this->ensureNoEnvironmentKeys($data[$finalKey], $environment);
				}
				
				// Else force no environment key
				$finalKey = $key[$i];
				if (array_key_exists($finalKey, $data)) {
					return $this->ensureNoEnvironmentKeys($data[$finalKey], $environment);
				}
				
				// Throw an exception if it is not set
				throw new \OutOfBoundsException('No value could be found with the given key');
				
			}
			
			// Otherwise, get the next part of
			// the part of the data from our key
			// and repeat
			$data = $data[$key[$i]];
			
		}
		
	}
	
	private function ensureNoEnvironmentKeys($data, $environment)
	{
		if (!is_array($data)) return $data;
		foreach ($data as $key => &$value) {
			if (strpos($key, ':') === false) continue;
			if (substr($key, 0, strpos($key, ':')) == $environment) {
				$data[substr($key, strpos($key, ':') + 1)] = $value;
			}
			unset($data[$key]);
			$value = $this->ensureNoEnvironmentKeys($value, $environment);
		}
		return $data;
	}
	
}