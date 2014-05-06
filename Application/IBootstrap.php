<?php

namespace PO\Application;

use PO\Application;

interface IBootstrap
{
	
	public function run(Application $application);
	
}