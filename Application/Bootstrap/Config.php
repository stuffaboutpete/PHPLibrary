<?php

namespace PO\Application\Bootstrap;

use PO\Application\IBootstrap;
use PO\IoCContainer;

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
	
	public function run(IoCContainer $ioCContainer)
	{
		if (!isset($this->pathToConfig)) return;
		$ioCContainer->registerSingleton(new \PO\Config(
			file_get_contents($this->pathToConfig),
			$this->environments
		));
	}
	
}
