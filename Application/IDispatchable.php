<?php

namespace PO\Application;

use PO\Http\Response;
use PO\IoCContainer;

interface IDispatchable
{
	
	public function dispatch(Response $response, IoCContainer $container);
	
}
