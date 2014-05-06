<?php

namespace Suburb\IoCContainer\Containment;

use Suburb\IoCContainer;
use Suburb\IoCContainer\IContainment;

class HttpRequest
implements IContainment
{
	
	public function register(IoCContainer $container)
	{
		
		$container->registerInterface(
			'Suburb\\HttpRequest\\ITransferMethod',
			'Suburb\\HttpRequest\\TransferMethod\\Curl'
		);
		
	}
	
}