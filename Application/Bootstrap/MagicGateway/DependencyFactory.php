<?php

namespace PO\Application\Bootstrap\MagicGateway;

use PO\Gateway\Factory\Model as ModelFactory;
use PO\Gateway\QueryProvider\Simple as SimpleQueryProvider;
use PO\IoCContainer;

class DependencyFactory
{
	
	public function getModelFactory(
		/*string */		$className,
		array			$buildMapContributors = null,
		array			$dismantleContributors = null,
		IoCContainer	$ioCContainer = null
	)
	{
		return new ModelFactory(
			$className,
			$buildMapContributors,
			$dismantleContributors,
			$ioCContainer
		);
	}
	
	public function getSimpleQueryProvider($className, $tableName)
	{
		return new SimpleQueryProvider($className, $tableName);
	}
	
}
