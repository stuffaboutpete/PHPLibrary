<?php

namespace PO\IoCContainer;

use PO\IoCContainer;

interface IContainment
{
	
	public function register(IoCContainer $container);
	
}