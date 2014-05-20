<?php

namespace PO\Application\Dispatchable\Mvc;

use PO\Application;

abstract class Controller
{
	
	private $templateVariables = [];
	
	/**
	 * A dispatch method must exist in order
	 * for an instance of this class to be used
	 * by \PO\Application\Dispatchable\Mvc. It
	 * is not strictly required by this abstract
	 * class because it will be dependency injected
	 * and defining a signature here would stop
	 * that functionality.
	 * 
	 * (Shout if you have a better solution)
	 * @todo Come up with a better solution
	 */
	// abstract public function dispatch(/* Dependency injected */);
	
	protected function addTemplateVariable($key, $value)
	{
		$this->templateVariables[$key] = $value;
	}
	
	public function getTemplateVariables()
	{
		return $this->templateVariables;
	}
	
}
