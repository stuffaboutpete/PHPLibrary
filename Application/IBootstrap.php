<?php

namespace Suburb\Application;

use Suburb\Application;

interface IBootstrap
{
	
	public function run(Application $application);
	
}