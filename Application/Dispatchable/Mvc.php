<?php

namespace PO\Application\Dispatchable;

use PO\Application;
use PO\Application\IErrorHandler;
use PO\Application\IDispatchable;
use PO\Application\Dispatchable\Mvc\Controller;
use PO\Application\Dispatchable\Mvc\IControllerIdentifier;
use PO\Application\Dispatchable\Mvc\Exception;
use PO\Http\Response;
use PO\IoCContainer;
use PO\Helper\ArrayType;

/**
 * Needs re-commenting
 * 
 * Mvc
 * 
 * An application router which auto loads
 * controller files based on the requested
 * URL along with any similarly named
 * template file
 */
class Mvc
implements IDispatchable
{
	
	/**
	 * 
	 */
	private $controllerIdentifier;
	
	/**
	 * An optional IoC container
	 * 
	 * An inversion of control container
	 * which will be used to resolve the
	 * controller if available so that
	 * the controller can declare dependencies
	 * 
	 * @var PO\IoCContainer
	 */
	private $ioCContainer;
	
	private $errorHandler;
	
	private $application;
	private $response;
	
	/**
	 * Provide a location to find controllers
	 * both in terms of file system and namespace
	 * 
	 * @param string $controllerPath            The root directory that controllers are found
	 * @param string $controllerBaseNamespace   The root namespace that controllers are found
	 * @param PO\IoCContainer $ioCContainer optional An IoC container to resolve the controller
	 */
	public function __construct(
		IControllerIdentifier	$controllerIdentifier,
		IoCContainer			$ioCContainer = null,
		IErrorHandler			$errorHandler = null
	)
	{
		$this->controllerIdentifier = $controllerIdentifier;
		$this->ioCContainer = $ioCContainer;
		$this->errorHandler = $errorHandler;
	}
	
	/**
	 * Controls the process of discovering the
	 * required controller and template and
	 * running them whilst passing output from
	 * both to the provided response object
	 * 
	 * @param  PO\Application   $application An application object to pass to the controller
	 * @param  PO\Http\Response $response    A response object which will be set during dispatch
	 * @return null
	 */
	public function dispatch(Application $application, Response $response)
	{
		
		$this->application = $application;
		$this->response = $response;
		
		if ($this->hasErrorHandler()) {
			$this->errorHandler->setup($application, $response);
			register_shutdown_function([$this->errorHandler, 'handleError']);
		}
		
		try {
			
			$this->controllerIdentifier->receivePath($_SERVER['REQUEST_URI']);
			$controller = $this->controllerIdentifier->getControllerClass();
			$templatePath = $this->controllerIdentifier->getTemplatePath();
			$pathVariables = $this->controllerIdentifier->getPathVariables();
			
		} catch (\Exception $exception) {
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception);
				return;
			} else {
				throw $exception;
			}
		}
		
		if ($controller && !class_exists($controller)) {
			
			$exception = new Exception(
				Exception::CONTROLLER_CLASS_DOES_NOT_EXIST,
				"Class name: $controller"
			);
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception, 500);
				return;
			} else {
				throw $exception;
			}
			
		} else if ($controller) {
			
			if (isset($this->ioCContainer)) {
				// @todo Needs try/catching
				$controller = $this->ioCContainer->resolve($controller);
			} else {
				// @todo Needs try/catching
				$controller = new $controller();
			}
			
			if (!$controller instanceof Controller) {
				
				$exception = new Exception(
					Exception::CONTROLLER_CLASS_IS_NOT_CONTROLLER,
					'Class name: ' . get_class($controller)
				);
				
				if ($this->hasErrorHandler()) {
					$this->handleException($exception, 500);
					return;
				} else {
					throw $exception;
				}
				
			}
			
		}
		
		if ($templatePath && !file_exists($templatePath)) {
			
			$exception = new Exception(
				Exception::CONTROLLER_TEMPLATE_DOES_NOT_EXIST,
				"Template path: $templatePath"
			);
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception, 500);
				return;
			} else {
				throw $exception;
			}
			
		}
		
		if (!$controller && !$templatePath) {
			
			$exception = new Exception(
				Exception::NO_CONTROLLER_CLASS_OR_TEMPLATE_COULD_BE_IDENTIFIED
			);
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception, 404);
			} else {
				$response->set404();
			}
			
			return;
			
		}
		
		if (!is_null($pathVariables)) {
			
			if (!is_array($pathVariables) || !ArrayType::isAssociative($pathVariables)) {
				
				$exception = new Exception(
					Exception::CONTROLLER_IDENTIFIER_RETURNS_NON_ASSOCIATIVE_ARRAY_PATH_VARIABLES,
					'Path variables: ' . (is_array($pathVariables)
						? 'Array ' . implode(', ', $pathVariables)
						: gettype($pathVariables))
				);
				
				if ($this->hasErrorHandler()) {
					$this->handleException($exception, 500);
					return;
				} else {
					throw $exception;
				}
				
			}
			
		}
		
		ob_start();
		
		try {
			if (is_object($controller)) $controller->dispatch($application, $pathVariables);
		} catch (\Exception $exception) {
			
			ob_end_clean();
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception);
				return;
			} else {
				throw $exception;
			}
			
			ob_start();
			
		}
		
		
		if (is_object($controller)) {
			
			$templateVariables = $controller->getTemplateVariables();
			
			if (!is_null($templateVariables) && !ArrayType::isAssociative($templateVariables)) {
				
				ob_end_clean();
				
				$exception = new Exception(
					Exception::CONTROLLER_RETURNS_NON_ASSOCIATIVE_ARRAY_TEMPLATE_VARIABLES,
					'Template variables: ' . (is_array($templateVariables)
						? 'Array ' . implode(', ', $templateVariables)
						: gettype($templateVariables))
				);
				
				if ($this->hasErrorHandler()) {
					$this->handleException($exception, 500);
					return;
				} else {
					throw $exception;
				}
				
				ob_start();
				
			}
			
			extract($templateVariables);
			
		}
		
		try {
			if (file_exists($templatePath)) include $templatePath;
		} catch (\Exception $exception) {
			
			ob_end_clean();
			
			if ($this->hasErrorHandler()) {
				$this->handleException($exception);
				return;
			} else {
				throw $exception;
			}
			
			ob_start();
			
		}
		
		$output = ob_get_contents();
		ob_end_clean();
		
		$response->set200($output);
		
	}
	
	private function hasErrorHandler()
	{
		return isset($this->errorHandler);
	}
	
	private function handleException($exception, $recommendedResponseCode = null)
	{
		// Should we recieve a message?
		$method = 'set' . (isset($recommendedResponseCode) ? $recommendedResponseCode : 500);
		try {
			$this->errorHandler->handleException($exception, $recommendedResponseCode);
		} catch (\Exception $exceptionio) {
			$this->response->$method($exceptionio->getMessage() . $exceptionio->getTraceAsString());
		}
		if (!$this->response->isInitialised()) {
			$this->response->$method();
		}
	}
	
}
