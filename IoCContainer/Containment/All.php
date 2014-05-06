<?php

namespace PO\IoCContainer\Containment;

use PO\IoCContainer;
use PO\IoCContainer\IContainment;
use PO\IoCContainer\Containment;

class All
implements IContainment
{
	
	public function register(IoCContainer $container)
	{
		
		$container->addContainment(new Containment\Application());
		
	}
	
}
