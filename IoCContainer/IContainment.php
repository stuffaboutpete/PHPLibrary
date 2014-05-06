<?php

namespace Suburb\IoCContainer;

use Suburb\IoCContainer;

interface IContainment
{
	
	public function register(IoCContainer $container);
	
}