<?php

namespace Suburb;

class Autoloader
{
	
	private $pathToClasses;
	private $namespace;
	
	public function __construct($pathToClasses, $namespace)
	{
		$this->pathToClasses = $pathToClasses;
		$this->namespace = $namespace;
		spl_autoload_register(array($this, 'autoload'));
	}
	
	public function autoload($class) {
		if (strpos($class, $this->namespace) === 0) {
			$classParts = explode(
				'\\',
				substr(
					$class,
					strlen($this->namespace)
				)
			);
			$path = $this->pathToClasses . '' .
				implode('/', $classParts) . '.php';
			if (file_exists($path)) include_once $path;
		}
	}
	
}