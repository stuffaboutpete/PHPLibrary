<?php

namespace PO\Application\Bootstrap;

use PO\Application;
use PO\Application\IBootstrap;

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
			new \PO\Config(file_get_contents($this->pathToConfig), $this->environments)
		);
	}
	
}
