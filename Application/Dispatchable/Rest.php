<?php

namespace PO\Application\Dispatchable;

use PO\Application\Dispatchable\Rest\IEndpoint;
use PO\Application\Dispatchable\Rest\RouteVariables;
use PO\Application\IDispatchable;
use PO\Application\IExceptionHandler;
use PO\Config;
use PO\Http\Response;
use PO\IoCContainer;

/**
 * Rest
 * 
 * An IDispatchable which runs
 * instances of IEndpoint based on a
 * provided config file associating
 * request method / request path
 * combinations with named classes.
 * 
 * Exceptions that occur in identifying
 * a route or whilst dispatching are
 * handled by an IExceptionHandler object.
 */
class Rest
implements IDispatchable
{
	
	// @todo Would like to abstract route identifier; same as MVC approach
	
	/**
	 * All the possible routes
	 * that could be dispatched
	 * 
	 * @var array
	 */
	private $routes = [];
	
	/**
	 * Exception handler
	 * 
	 * An instance of IExceptionHandler which
	 * is provided with any exception thrown
	 * whilst identifying or dispatching
	 * a controller or template
	 * 
	 * @var PO\Application\IExceptionHandler
	 */
	private $exceptionHandler;
	
	/**
	 * An optional base to append
	 * to the beginning of the
	 * request path before attempting
	 * to match against a route
	 * 
	 * @var string|null
	 */
	private $rewriteBase;
	
	/**
	 * Constructor
	 * 
	 * Requires a config object which must
	 * contain a list of routes in the format
	 * 'METHOD /path => Class' and also accepts
	 * an exception handler and an optional
	 * rewrite base to specify the beginning
	 * of all matchable routes
	 * 
	 * @param  Config            $routesConfig     A Config object listing all available routes
	 * @param  IExceptionHandler $exceptionHandler An object to handle any exceptions encountered
	 * @param  string            $rewriteBase      optional Optional base to use in route matching
	 * @throws \InvalidArgumentException If any config route does not specify a valid HTTP verb
	 */
	public function __construct(
		Config				$routesConfig,
		IExceptionHandler	$exceptionHandler = null,
		/* string */		$rewriteBase = ''
	)
	{
		
		// Loop through all the route keys in
		// the provided config file which
		// should all identify a request
		// method / request path combination
		foreach ($routesConfig->getKeys() as $key) {
			
			// Get the request method
			$method = array_shift(explode(' ', $key));
			
			// Get the request path as a string
			// array, removing the first element
			// as it will always be empty (path
			// begins with a forward slash)
			$route = explode('/', array_pop(explode(' ', $key)));
			array_shift($route);
			
			// Ensure the method is valid
			if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
				throw new \InvalidArgumentException(
					'Route key must begin with a supported HTTP method ' .
					'(GET, POST, PUT or DELETE) followed by a space'
				);
			}
			
			// Save the route in an array
			array_push($this->routes, [
				'method'	=> $method,
				'path'		=> $route,
				'class'		=> $routesConfig->get($key)
			]);
			
		}
		
		$this->exceptionHandler = $exceptionHandler;
		
		// Ensure the rewrite base does have a
		// leading forward slash but does not
		// a trailing one and save it
		$rewriteBase = (substr($rewriteBase, 0, 1) == '/') ? substr($rewriteBase, 1) : $rewriteBase;
		$rewriteBase = (substr($rewriteBase, -1) == '/')
			? substr($rewriteBase, 0, strlen($rewriteBase) - 1)
			: $rewriteBase;
		$this->rewriteBase = ($rewriteBase == '') ? [] : explode('/', $rewriteBase);
		
	}
	
	/**
	 * Initiates the process of identifying and
	 * running an instance of IEndpoint based
	 * on the request path and method. If an
	 * optional route string is provided it
	 * should be in the format 'METHOD /path'.
	 * 
	 * @param  PO\Http\Response $response     A response object which will be set downstream
	 * @param  PO\IoCContainer  $ioCContainer An IoC container to build and run the endpoint
	 * @param  string           $route        optional Used in place of the server request data
	 * @return null
	 */
	public function dispatch(Response $response, IoCContainer $ioCContainer, $route = null)
	{
		
		// Try to identify an endpoint
		// and catch any expected errors
		try {
			$route = $this->getRoute($ioCContainer, $route);
		} catch (Rest\Exception $exception) {
			switch ($exception->getCode()) {
				
				// If the method / path combination
				// does not match an identified route
				// set the response to 404 (Not found)
				case Rest\Exception::NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD:
					if ($this->hasExceptionHandler()) {
						$this->handleException($exception, $response, 404);
						return;
					}
					$response->set404();
				break;
				
				// If the identified endpoint does
				// not exist or it is not an instance
				// of IEndpoint, set the response to
				// 500 (Internal server error)
				case Rest\Exception::ENDPOINT_CLASS_DOES_NOT_EXIST:
				case Rest\Exception::ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT:
					if ($this->hasExceptionHandler()) {
						$this->handleException($exception, $response);
						return;
					}
					throw $exception;
				break;
				
			}
			
			return;
			
		}
		
		// Dispatch the identified endpoint
		// using the IoC container, ensuring
		// that nothing is output to the
		// screen and that any exceptions
		// will cause the response to be set
		// to 500 (Internal server error)
		try {
			ob_start();
			$ioCContainer->call(
				$route['object'],
				'dispatch',
				[],
				[
					$response,
					new RouteVariables($route['variables'])
				]
			);
			ob_end_clean();
		} catch (\Exception $exception) {
			if ($this->hasExceptionHandler()) {
				$this->handleException($exception, $response);
				return;
			}
			throw $exception;
		}
		
	}
	
	/**
	 * Attempts to match an endpoint
	 * based on the request path and method
	 * 
	 * @param  PO\IoCContainer $ioCContainer Used to build the endpoint
	 * @param  string          $route        optional Used in place of the server request data
	 * @throws PO\Application\Dispatchable\Rest\Exception If the identified class does not exist
	 * @throws PO\Application\Dispatchable\Rest\Exception If identified class is not an IEndpoint
	 * @throws PO\Application\Dispatchable\Rest\Exception If no endpoint can be identified
	 * @return array An IEndpoint and any associated route variables
	 */
	private function getRoute($ioCContainer, $route = null)
	{
		
		// If we have a user supplied route,
		// we split it into a method and an
		// array representing the path
		if ($route) {
			$requestMethod = array_shift(explode(' ', $route));
			$requestPath = explode('/', array_pop(explode(' ', $route)));
			
		// Otherwise get the method and path
		// array from the server global
		} else {
			$requestMethod = $_SERVER['REQUEST_METHOD'];
			$requestPath = explode('/', $_SERVER['REQUEST_URI']);
		}
		
		// Remove the first element of the
		// request path as it will be blank
		array_shift($requestPath);
		
		// Loop through the provided routes
		// and look for a match
		foreach ($this->routes as $route) {
			
			// If the request method does not match
			// the current request method
			if ($requestMethod != $route['method']) continue;
			
			// If we have a rewrite base, prepend
			// it to the path of the route
			if (count($this->rewriteBase) > 0) {
				foreach (array_reverse($this->rewriteBase) as $baseElement) {
					array_unshift($route['path'], $baseElement);
				}
			}
			
			$path = $route['path'];
			
			// If this path is not the same length
			// in terms of forward slashes as the
			// request path, it is not a match
			if (count($path) != count($requestPath)) continue;
			
			$routeVariables = [];
			
			// Loop through the sections
			// of the request path
			for ($i = 0; $i < count($path); $i++) {
				
				// If this part begins with a '{', assume
				// it as a path variable, save the data
				// provided in the request path and regard
				// this as a match between the path and
				// request path
				if (substr($path[$i], 0, 1) == '{') {
					$routeVariables[substr($path[$i], 1, -1)] = $requestPath[$i];
					continue;
				}
				
				// If the request path and the path in
				// question are the same at this point
				// this is a match
				if (strtolower($path[$i]) != strtolower($requestPath[$i])) continue 2;
				
			}
			
			// If we get this far, we
			// have a matching route
			$class = $route['class'];
			
			// Ensure the class of the endpoint exists
			if (!class_exists($route['class'])) {
				throw new Rest\Exception(
					Rest\Exception::ENDPOINT_CLASS_DOES_NOT_EXIST,
					'Class: ' . $route['class']
				);
			}
			
			// Create an instance of the endpoint
			$route = $ioCContainer->resolve($route['class']);
			
			// Ensure it is an instance of IEndpoint
			if (!$route instanceof IEndpoint) {
				throw new Rest\Exception(
					Rest\Exception::ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT,
					'Class: ' . get_class($route)
				);
			}
			
			// Send back an array containing the
			// endpoint and path data gained from
			// variables in the request path
			return [
				'object'	=> $route,
				'variables'	=> $routeVariables
			];
			
		}
		
		// If we get to here, we don't have
		// a matching path for the current
		// request method/uri combination
		throw new Rest\Exception(
			Rest\Exception::NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD,
			"Request signature: $requestMethod /" . implode('/', $requestPath)
		);
		
	}
	
	private function hasExceptionHandler()
	{
		return isset($this->exceptionHandler);
	}
	
	private function handleException(\Exception $exception, Response $response, $responseCode = 500)
	{
		$this->exceptionHandler->handleException($exception, $response, $responseCode);
	}
	
}
