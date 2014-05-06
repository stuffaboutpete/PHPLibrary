<?php

namespace Suburb\IoCContainer\Containment;

use Suburb\IoCContainer;
use Suburb\IoCContainer\IContainment;
use Suburb\Application\Dispatchable\Mvc;
use Suburb\Application\Dispatchable\Rest;
use Suburb\Application\Bootstrap;
use Suburb\Http\Response;
use Suburb\Config;

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
			'Suburb\\Application\\Mvc',
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
				
				return new \Suburb\Application(
					new Mvc(
						new Mvc\ControllerIdentifier\FolderStructure(
							$controllersNamespace,
							$templatesDirectory
						),
						$container,
						(isset($customErrorView))
							? new \Suburb\Application\ErrorHandler\View\Hybrid($customErrorView)
							: new \Suburb\Application\ErrorHandler\View()
					),
					new Response(),
					array_merge([
						new Bootstrap\Config($pathToConfig, $environments),
						new Bootstrap\Pdo(),
						new Bootstrap\Authenticator(new \Suburb\Helper\Cookie()),
						new Bootstrap\AccessController($accessControllerRules)
					], $bootstraps)
				);
				
			}
		);
		
	}
	
	private function registerRest()
	{
		
		$this->container->registerCallback(
			'Suburb\\Application\\Rest',
			function($container, $pathToRoutesConfig, $pathToConfig = null){
				
				return new \Suburb\Application(
					new Rest(
						new Config(file_get_contents($pathToRoutesConfig))
					),
					new Response(),
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
			'Suburb\\Application\\MvcWithRest',
			function($container){
				
				return new \Suburb\Application(
					// ...
				);
				
			}
		);
		
	}
	
}