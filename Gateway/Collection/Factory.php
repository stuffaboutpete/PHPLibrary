<?php

namespace PO\Gateway\Collection;

use PO\Gateway;
use PO\Gateway\Collection;
use PO\Gateway\IFactory;

/**
 * The only reason for this class is testing
 * purposes. If we allowed the gateway to
 * create collections then we could not test
 * the gateway independently.
 */
class Factory
{
	
	public function build(Gateway $gateway, $className, array $data, array $objects = null)
	{
		return new Collection($gateway, $className, $data, $objects);
	}
	
}
