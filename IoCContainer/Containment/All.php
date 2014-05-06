<?php

namespace Suburb\IoCContainer\Containment;

use Suburb\IoCContainer;
use Suburb\IoCContainer\IContainment;
use Suburb\IoCContainer\Containment;

class All
implements IContainment
{
	
	public function register(IoCContainer $container)
	{
		
		$container->addContainment(new Containment\Application());
		
	}
	
}
