<?php

namespace PO\IoCContainer\Containment;

use PO\IoCContainer;
use PO\IoCContainer\IContainment;

class HttpRequest
implements IContainment
{
	
	public function register(IoCContainer $container)
	{
		
		$container->registerInterface(
			'PO\\HttpRequest\\ITransferMethod',
			'PO\\HttpRequest\\TransferMethod\\Curl'
		);
		
	}
	
}