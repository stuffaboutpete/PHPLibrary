<?php

namespace PO\Application\Dispatchable\Rest;

use PO\Application;
use PO\Http\Response;

interface IEndpoint
{
	/**
	 * A dispatch method must exist in order
	 * for an instance of this class to be used
	 * by \PO\Application\Dispatchable\Rest. It
	 * is not strictly required by this abstract
	 * class because it will be dependency injected
	 * and defining a signature here would stop
	 * that functionality.
	 * 
	 * (Shout if you have a better solution)
	 * @todo Come up with a better solution
	 */
	// public function dispatch();
	
}