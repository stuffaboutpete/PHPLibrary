<?php

namespace PO\Application\Dispatchable;

use PO\Application;
use PO\Application\IErrorHandler;
use PO\Application\IDispatchable;
use PO\Application\Dispatchable\Mvc\Controller;
use PO\Application\Dispatchable\Mvc\IControllerIdentifier;
use PO\Application\Dispatchable\Mvc\Exception;
use PO\Application\Dispatchable\Mvc\RouteVariables;
use PO\Http\Response;
use PO\IoCContainer;
use PO\Helper\ArrayType;

/**
 * Mvc
 * 
 * An application dispatchable which
 * identifies a controller to run based
 * on a provided IControllerIdentifier
 * object. It will also load a basic html
 * template file to run along side the
 * controller.
 * 
 * Controllers are built with
 * and dispatched using an IoCContainer
 * to inject dependencies.
 * 
 * Exceptions that occur in identifying
 * a route or whilst dispatching are
 * handled by an IErrorHandler object.
 */
class Mvc
implements IDispatchable
{
	
	/**
	 * Controller identifier
	 * 
	 * An instance of IControllerIdentifier
	 * used to identify a controller and/or
	 * a html template to serve as content
	 * 
	 * @var PO\Application\Dispatchable\Mvc\IControllerIdentifier
	 */
	private $controllerIdentifier;
	
	/**
	 * Error handler
	 * 
	 * An instance of IErrorHandler which
	 * is provided with any exception thrown
	 * whilst identifying or dispatching
	 * a controller or template
	 * 
	 * @var PO\Application\IErrorHandler
	 */
	private $errorHandler;
	
	/**
	 * Response object
	 * 
	 * Provided by application as
	 * this object is dispatched.
	 * It is set with controller
	 * content or provided to the
	 * error handler if required.
	 * 
	 * @var PO\Http\Response
	 */
	private $response;
	
	/**
	 * Constructor
	 * 
	 * Provide a location to find controllers
	 * both in terms of file system and namespace
	 * 
	 * @param string          $controllerPath          Root directory where controllers are found
	 * @param string          $controllerBaseNamespace Root namespace where controllers are found
	 * @return PO\Application\Dispatchable\Mvc self
	 */
	public function __construct(
		IControllerIdentifier	$controllerIdentifier,
		IErrorHandler			$errorHandler = null
	)
	{
		$this->controllerIdentifier = $controllerIdentifier;
		$this->errorHandler = $errorHandler;
	}
	
	/**
	 * Controls the process of discovering the
	 * required controller and template and
	 * running them whilst passing output from
	 * both to the provided response object.
	 * 
	 * Note that no exceptions are thrown if
	 * instance of IErrorHandler is provided
	 * to constructor.
	 * 
	 * @param  PO\Http\Response $response     A response object which will be set during dispatch
	 * @param  PO\IoCContainer  $ioCContainer An IoC container to build and run the controller
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller class doesn't exist
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller class isn't a controller
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller doesn't have dispatch method
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller template doesn't exist
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller and template aren't found
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller and template aren't found
	 * @throws PO\Application\Dispatchable\Mvc\Exception If identifier gives non-assoc path vars 
	 * @throws PO\Application\Dispatchable\Mvc\Exception If controller gives non-assoc template vars
	 * @return null
	 */
	public function dispatch(Response $response, IoCContainer $ioCContainer)
	{
		
		// Save the response object
		// for use in error handling
		$this->response = $response;
		
		// Set up the error handler
		// if provided
		if ($this->hasErrorHandler()) {
			$this->errorHandler->setup($response);
			register_shutdown_function([$this->errorHandler, 'handleError']);
		}
		
		// Attempt to identify a controller,
		// template and path variables from
		// our controller identifier. Pass
		// any exception to the error handler
		// if provided, else rethrow.
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
		
		// Throw or handle an exception if
		// a controller has been identified
		// but it is not an instance of
		// PO\Application\Dispatchable\Mvc\Controller
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
		
		// If we have a controller and it
		// is an instance of Controller...
		} else if ($controller) {
			
			// Build the controller using
			// inversion of control
			$controller = $ioCContainer->resolve($controller);
			
			// Throw or handle an exception if
			// the object returned to us by IoC
			// is not an instance of Controller
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
			
			// Throw or handle an exception if
			// the controller does not have a
			// dispatch method. Note that we
			// cannot force this as an abstract
			// method as that would not allow us
			// to run it via inversion of control.
			if (!method_exists($controller, 'dispatch')) {
				$exception = new Exception(Exception::CONTROLLER_HAS_NO_DISPATCH_METHOD);
				if ($this->hasErrorHandler()) {
					$this->handleException($exception, 500);
					return;
				} else {
					throw $exception;
				}
			}
			
		}
		
		// Throw or handle an exception if
		// a template has been identified
		// but it does not exist
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
		
		// Throw or handle an exception if
		// no controller has been identified
		// and no template has been identified
		// as this means we have nothing to output
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
		
		// If we have identified any variables
		// from the requested path...
		if (!is_null($pathVariables)) {
			
			// Throw or handle an exception if
			// the identified path variables
			// are not an associative array
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
			
		// Otherwise initialise an empty
		// array to avoid an error later
		} else {
			$pathVariables = [];
		}
		
		// Begin output buffering so that
		// the controller or template do
		// not output directly to the screen
		ob_start();
		
		// Run the controller via the
		// inversion of control container
		// whilst handling any exceptions
		// if error handler is available
		try {
			if (is_object($controller)) {
				$ioCContainer->call(
					$controller,
					'dispatch',
					[],
					[new RouteVariables($pathVariables)]
				);
			}
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
		
		// Get template variables from
		// the controller object
		if (is_object($controller)) {
			$templateVariables = $controller->getTemplateVariables();
			
			// Throw or handle an exception if
			// the template variables are not
			// an associative array
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
			
			// Turn the template variables
			// array into variables in the
			// current scope so they will
			// be available to the template
			// file included below
			extract($templateVariables);
			
		}
		
		// Include the template file if one
		// has been identified whilst handling
		// any error that occurs in the process
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
		
		// Get the contents of the output
		// buffer and stop buffering
		$output = ob_get_contents();
		ob_end_clean();
		
		// Set the response to 200 with
		// the contents of the output buffer
		$response->set200($output);
		
	}
	
	/**
	 * Returns whether this object has
	 * an instance of IErrorHandler
	 * 
	 * @return boolean
	 */
	private function hasErrorHandler()
	{
		return isset($this->errorHandler);
	}
	
	/**
	 * Passes provided exception to
	 * error handler and ensures that
	 * the response object is set to
	 * the given response code or 500
	 * 
	 * @param  Exception  $exception                        The exception to handle
	 * @param  int|string $recommendedResponseCode optional The response code to set the response to
	 * @return null
	 */
	private function handleException(\Exception $exception, $recommendedResponseCode = null)
	{
		// Should we recieve a message?
		$method = 'set' . (isset($recommendedResponseCode) ? $recommendedResponseCode : 500);
		try {
			$this->errorHandler->handleException($exception, $recommendedResponseCode);
		} catch (\Exception $exception) {
			$this->response->$method($exception->getMessage() . $exception->getTraceAsString());
		}
		if (!$this->response->isInitialised()) {
			$this->response->$method();
		}
	}
	
}
