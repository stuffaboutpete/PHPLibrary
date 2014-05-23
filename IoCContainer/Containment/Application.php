<?php

namespace PO\IoCContainer\Containment;

use PO\Application\Bootstrap;
use PO\Application\Dispatchable\Mvc;
use PO\Application\Dispatchable\Rest;
use PO\Application\ExceptionHandler;
use PO\Config;
use PO\Http\Response;
use PO\IoCContainer;
use PO\IoCContainer\IContainment;

class Application
implements IContainment
{
	
	private $container;
	
	public function register(IoCContainer $container)
	{
		$this->container = $container;
		$this->registerMvc();
		$this->registerRest();
		$this->registerMvcWithRest();
	}
	
	private function registerMvc()
	{
		
		$this->container->registerCallback(
			'PO\\Application\\Mvc',
			function(
				$container,
				$controllersNamespace,
				$templatesDirectory,
				$pathToConfig = null,
				$environments = null,
				array $bootstraps = [],
				$accessControllerRules = [],
				$customErrorView = null
			){
				
				$response = new Response();
				
				$exceptionHandler = isset($customErrorView)
					? new ExceptionHandler\View\Hybrid($customErrorView)
					: new ExceptionHandler\View();
				
				$exceptionHandler = new ExceptionHandler\ErrorException(
					$exceptionHandler,
					$response
				);
				
				return new \PO\Application(
					new Mvc(
						new Mvc\ControllerIdentifier\FolderStructure(
							$controllersNamespace,
							$templatesDirectory
						),
						$exceptionHandler
					),
					$response,
					$container,
					array_merge([
						new Bootstrap\Config($pathToConfig, $environments),
						new Bootstrap\Pdo(),
						new Bootstrap\Authenticator(new \PO\Helper\Cookie()),
						new Bootstrap\AccessController($accessControllerRules)
					], $bootstraps)
				);
				
			}
		);
		
	}
	
	private function registerRest()
	{
		
		$this->container->registerCallback(
			'PO\\Application\\Rest',
			function($container, $pathToRoutesConfig, $pathToConfig = null){
				
				$response = new Response();
				
				return new \PO\Application(
					new Rest(
						new Config(file_get_contents($pathToRoutesConfig)),
						new ExceptionHandler\ErrorException(
							new ExceptionHandler\JsonDebug(),
							$response
						)
					),
					$response,
					$container,
					[
						new Bootstrap\Config($pathToConfig),
						new Bootstrap\Pdo()
					]
				);
				
			}
		);
		
	}
	
	private function registerMvcWithRest()
	{
		
		$this->container->registerCallback(
			'PO\\Application\\MvcWithRest',
			function($container){
				
				return new \PO\Application(
					// ...
				);
				
			}
		);
		
	}
	
}