<?php

namespace Suburb\Application\Bootstrap;

use Suburb\Application;
use Suburb\Application\IBootstrap;

class Config
implements IBootstrap
{
	
	private $pathToConfig;
	private $environments;
	
	public function __construct($pathToConfig = null, $environments = null)
	{
		$this->pathToConfig = $pathToConfig;
		$this->environments = $environments;
	}
	
	public function run(Application $application)
	{
		if (!isset($this->pathToConfig)) return;
		$application->extend(
			'config',
			new \Suburb\Config(file_get_contents($this->pathToConfig), $this->environments)
		);
	}
	
}
