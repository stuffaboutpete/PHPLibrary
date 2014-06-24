<?php

namespace PO\IoCContainer\Containment;

use PO\IoCContainer\Containment\Application\Exception;

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
		// $this->registerMvcWithRest();
	}
	
	private function registerMvc()
	{
		
		/**
		 * Available options:
		 * 
		 * controllerNamespace	Root namespace of controllers
		 * templateDirectory	Location of controller templates
		 * bootstraps			Merged with default bootstraps
		 * exceptionHandler		Passed to application and Mvc objects
		 * configFile			Location of config data
		 * configEnvironments	Passed to config object
		 * modelDirectory		Location of models
		 * accessControlRules	Passed to access control bootstrap object
		 * response				The response object to be output at the end of the request
		 */
		
		$this->container->registerCallback(
			'PO\Application\Mvc',
			function($container, array $options = null){
				
				// Currently commented out because
				// there are no required options.
				// 
				// Ensure options is provided and
				// it is an associative array.
				// if (!is_array($options)) {
				// 	throw new Exception(
				// 		Exception::OPTIONS_NOT_PROVIDED,
				// 		'Provided type: ' . gettype($options)
				// 	);
				// }
				
				// Check that any required options have
				// been provided. (Ok, there aren't any
				// at the moment but you can just add
				// them to the array...)
				foreach ([] as $key) {
					if (!isset($options[$key])) {
						throw new Exception(
							Exception::REQUIRED_OPTION_NOT_PROVIDED,
							"Missing key: $key"
						);
					}
				}
				
				// Get the file that called this
				// function so we can have a guess
				// at the location of some files
				$backtrace = \debug_backtrace();
				if (isset($backtrace[2])) $clientDir = dirname($backtrace[2]['file']);
				
				// If a template directory hasn't
				// been provided but there is one
				// where we would expect it, use that
				if (!isset($options['templateDirectory'])) {
					$directory = $clientDir . '/../Controller';
					if (file_exists($directory) && is_dir($directory)) {
						$options['templateDirectory'] = $directory;
					} else {
						$options['templateDirectory'] = null;
					}
				}
				
				// If a config file hasn't been
				// provided but there is one where
				// we would expect it, use that
				if (!isset($options['configFile'])) {
					$file = $clientDir . '/../../../config.json';
					if (file_exists($file) && !is_dir($file)) {
						$options['configFile'] = $file;
					} else {
						$options['configFile'] = null;
					}
				}
				
				// If a model directory hasn't been
				// provided but there is one where
				// we would expect it, use that
				if (!isset($options['modelDirectory'])) {
					$directory = $clientDir . '/../../../common/classes/Model';
					if (file_exists($directory) && is_dir($directory)) {
						$options['modelDirectory'] = $directory;
					} else {
						$options['modelDirectory'] = null;
					}
				}
				
				// Fill in a couple of non-required options
				if (!isset($options['accessControlRules'])) $options['accessControlRules'] = [];
				if (!isset($options['configEnvironments'])) $options['configEnvironments'] = null;
				if (!isset($options['controllerNamespace'])) $options['controllerNamespace'] = null;
				if (!isset($options['response'])) {
					$options['response'] = $container->resolve('PO\Http\Response');
				}
				
				// Create an array of default bootstraps
				$defaultBootstraps = [
					new Bootstrap\Config($options['configFile'], $options['configEnvironments']),
					new Bootstrap\Pdo(),
					new Bootstrap\Authenticator(new \PO\Helper\Cookie()),
					new Bootstrap\AccessController($options['accessControlRules'])
				];
				
				// If we have a model directory then
				// we can add a magic gateway bootstrap
				if (isset($options['modelDirectory'])) {
					array_push(
						$defaultBootstraps,
						$container->resolve(
							Bootstrap\MagicGateway::Class,
							[
								null,
								$options['modelDirectory']
							]
						)
					);
				}
				
				// Merge any provided bootstrap
				// files with the default ones
				if (!isset($options['bootstraps'])) $options['bootstraps'] = [];
				$options['bootstraps'] = array_merge($defaultBootstraps, $options['bootstraps']);
				
				// If an exception handler has been provided
				// and it is a view, pass it in to a hybrid
				// view exception handler. Wrap the whole
				// thing in an error exception handler.
				if (isset($options['exceptionHandler'])
				&&	$options['exceptionHandler'] instanceof PO\View) {
					$options['exceptionHandler'] = new ExceptionHandler\ErrorException(
						new ExceptionHandler\View\Hybrid(
							$options['exceptionHandler']
						),
						$options['response']
					);
				
				// Otherwise, if none is provided, create
				// a basic view exception handler inside
				// an error exception handler
				} else if (!isset($options['exceptionHandler'])) {
					$options['exceptionHandler'] = new ExceptionHandler\ErrorException(
						new ExceptionHandler\View(),
						$options['response']
					);
				}
				
				// Create the application using our options
				return new \PO\Application(
					new Mvc(
						new Mvc\ControllerIdentifier\FolderStructure(
							$options['controllerNamespace'],
							$options['templateDirectory']
						),
						$options['exceptionHandler']
					),
					$options['response'],
					$container,
					$options['bootstraps'],
					$options['exceptionHandler']
				);
				
			}
		);
		
	}
	
	public function registerRest()
	{
		
		/**
		 * Available options:
		 * 
		 * routesConfigFile		Config file containing available routes
		 * bootstraps			Merged with default bootstraps
		 * exceptionHandler		Passed to application and Mvc objects
		 * configFile			Location of config data
		 * configEnvironments	Passed to config object
		 * modelDirectory		Location of models
		 * accessControlRules	Passed to access control bootstrap object
		 * response				The response object to be output at the end of the request
		 */
		
		$this->container->registerCallback(
			'PO\Application\Rest',
			function($container, $options = null){
				
				// Currently commented out because
				// there are no required options.
				// 
				// Ensure options is provided and
				// it is an associative array.
				// if (!is_array($options)) {
				// 	throw new Exception(
				// 		Exception::OPTIONS_NOT_PROVIDED,
				// 		'Provided type: ' . gettype($options)
				// 	);
				// }
				
				// Check that any required options have
				// been provided. (Ok, there aren't any
				// at the moment but you can just add
				// them to the array...)
				foreach ([] as $key) {
					if (!isset($options[$key])) {
						throw new Exception(
							Exception::REQUIRED_OPTION_NOT_PROVIDED,
							"Missing key: $key"
						);
					}
				}
				
				// Get the file that called this
				// function so we can have a guess
				// at the location of some files
				$backtrace = \debug_backtrace();
				if (isset($backtrace[2])) $clientDir = dirname($backtrace[2]['file']);
				
				// If a routes config file hasn't been
				// provided but there is one where
				// we would expect it, use that
				if (!isset($options['routesConfigFile'])) {
					$file = $clientDir . '/../../routes.json';
					if (file_exists($file) && !is_dir($file)) {
						$options['routesConfigFile'] = $file;
					} else {
						$options['routesConfigFile'] = null;
					}
				}
				
				// If a config file hasn't been
				// provided but there is one where
				// we would expect it, use that
				if (!isset($options['configFile'])) {
					$file = $clientDir . '/../../../config.json';
					if (file_exists($file) && !is_dir($file)) {
						$options['configFile'] = $file;
					} else {
						$options['configFile'] = null;
					}
				}
				
				// If a model directory hasn't been
				// provided but there is one where
				// we would expect it, use that
				if (!isset($options['modelDirectory'])) {
					$directory = $clientDir . '/../../../common/classes/Model';
					if (file_exists($directory) && is_dir($directory)) {
						$options['modelDirectory'] = $directory;
					} else {
						$options['modelDirectory'] = null;
					}
				}
				
				// Fill in a couple of non-required options
				if (!isset($options['accessControlRules'])) $options['accessControlRules'] = [];
				if (!isset($options['configEnvironments'])) $options['configEnvironments'] = null;
				if (!isset($options['response'])) {
					$options['response'] = $container->resolve('PO\Http\Response');
				}
				
				// Create an array of default bootstraps
				$defaultBootstraps = [
					new Bootstrap\Config($options['configFile'], $options['configEnvironments']),
					new Bootstrap\Pdo(),
					new Bootstrap\Authenticator(new \PO\Helper\Cookie()),
					new Bootstrap\AccessController($options['accessControlRules'])
				];
				
				// If we have a model directory then
				// we can add a magic gateway bootstrap
				if (isset($options['modelDirectory'])) {
					array_push(
						$defaultBootstraps,
						$container->resolve(
							Bootstrap\MagicGateway::Class,
							[
								null,
								$options['modelDirectory']
							]
						)
					);
				}
				
				// Merge any provided bootstrap
				// files with the default ones
				if (!isset($options['bootstraps'])) $options['bootstraps'] = [];
				$options['bootstraps'] = array_merge($defaultBootstraps, $options['bootstraps']);
				
				$routesConfig = new Config(file_get_contents($options['routesConfigFile']));
				
				// If no exception handler has been provided,
				// create a basic debug exception handler
				// inside an error exception handler
				if (!isset($options['exceptionHandler'])) {
					$options['exceptionHandler'] = new ExceptionHandler\ErrorException(
						new ExceptionHandler\JsonDebug(),
						$options['response']
					);
				}
				
				// Create the application using our options
				return new \PO\Application(
					new Rest(
						$routesConfig,
						$options['exceptionHandler']
					),
					$options['response'],
					$container,
					$options['bootstraps'],
					$options['exceptionHandler']
				);
				
			}
		);
		
	}
	
	// private function registerMvcWithRest()
	// {
		
	// 	$this->container->registerCallback(
	// 		'PO\Application\MvcWithRest',
	// 		function($container){
				
	// 			return new \PO\Application(
	// 				// ...
	// 			);
				
	// 		}
	// 	);
		
	// }
	
}