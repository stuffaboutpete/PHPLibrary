<?php

namespace PO\Application\Dispatchable\Mvc;

use PO\Application;
use PO\View;

abstract class Controller
{
	
	private $renderInto = null;
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
	
	public function renderInto(View $view, $method)
	{
		if (!method_exists($view, $method)) {
			// @todo Custom exception please
			throw new \Exception("Method '$method' does not exists");
		}
		$this->renderInto = [$view, $method];
	}
	
	public function render($content)
	{
		if (!isset($this->renderInto)) return $content;
		$method = $this->renderInto[1];
		$this->renderInto[0]->$method($content);
		return $this->renderInto[0]->__toString();
	}
	
}
