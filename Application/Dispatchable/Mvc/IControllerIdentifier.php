<?php

namespace Suburb\Application\Dispatchable\Mvc;

interface IControllerIdentifier
{
	
	public function receivePath($path);
	public function getControllerClass();
	public function getTemplatePath();
	public function getPathVariables();
	
}