<?php

namespace Suburb\Application\Dispatchable\Mvc;

use Suburb\Application;

abstract class Controller
{
	
	private $templateVariables = [];
	
	abstract public function dispatch(Application $application, $pathVariables = null);
	
	protected function addTemplateVariable($key, $value)
	{
		$this->templateVariables[$key] = $value;
	}
	
	public function getTemplateVariables()
	{
		return $this->templateVariables;
	}
	
}
