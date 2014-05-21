<?php

namespace PO\Application;

use PO\IoCContainer;

interface IBootstrap
{
	
	public function run(IoCContainer $container);
	
}
