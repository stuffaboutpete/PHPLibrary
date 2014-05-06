<?php

namespace Suburb\Gateway\Collection;

use Suburb\Gateway;
use Suburb\Gateway\Collection;
use Suburb\Gateway\IFactory;

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
