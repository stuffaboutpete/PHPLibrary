<?php

namespace Suburb\Application\Dispatchable;

use Suburb\Application;
use Suburb\Application\IDispatchable;
use Suburb\Application\Dispatchable\Rest\IEndpoint;
use Suburb\Config;
use Suburb\Http\Response;

/**
 * Rest
 * 
 * An IDispatchable which runs
 * instances of IEndpoint based on a
 * provided config file associating
 * request method / request path
 * combinations with named classes
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
	 * an optional rewrite base to specify the
	 * beginning of all matchable routes
	 * 
	 * @param Config $routesConfig         A Config object listing all available routes
	 * @param string $rewriteBase optional An optional base to use in matching routes
	 */
	public function __construct(Config $routesConfig, $rewriteBase = '')
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
	 * @param  Suburb\Application   $application    An application object to pass to the endpoint
	 * @param  Suburb\Http\Response $response       A response object which will be set downstream
	 * @param  string               $route optional Used in place of the server request data
	 * @return null
	 */
	public function dispatch(Application $application, Response $response, $route = null)
	{
		
		// Try to identify an endpoint
		// and catch any expected errors
		try {
			$route = $this->getRoute($response, $route);
		} catch (Rest\Exception $e) {
			switch ($e->getCode()) {
				
				// If the method / path combination
				// does not match an identified route
				// set the response to 404 (Not found)
				case Rest\Exception::NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD:
					$response->set404();
				break;
				
				// If the identified endpoint does
				// not exist or it is not an instance
				// of IEndpoint, set the response to
				// 500 (Internal server error)
				case Rest\Exception::ENDPOINT_CLASS_DOES_NOT_EXIST:
				case Rest\Exception::ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT:
					$response->set500();
				break;
				
			}
			
			return;
			
		}
		
		// Dispatch the identified endpoint,
		// ensuring that nothing is output
		// to the screen and that any exceptions
		// will cause the response to be set
		// to 500 (Internal server error)
		try {
			ob_start();
			$route['object']->dispatch(
				$application,
				$response,
				$route['variables']
			);
			ob_end_clean();
		} catch (\Exception $e) {
			$response->set500();
		}
		
	}
	
	/**
	 * Attempts to match an endpoint
	 * based on the request path and method
	 * 
	 * @param  Suburb\Http\Response $response       A response object which will be set downstream
	 * @param  string               $route optional Used in place of the server request data
	 * @return array                                An IEndpoint and any associated route variables
	 */
	private function getRoute(Response $response, $route = null)
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
				throw new Rest\Exception(Rest\Exception::ENDPOINT_CLASS_DOES_NOT_EXIST);
			}
			
			// Create an instance of the endpoint
			// and ensure it is an instance of IEndpoint
			$route = new $route['class']();
			if (!$route instanceof IEndpoint) {
				throw new Rest\Exception(
					Rest\Exception::ENDPOINT_CLASS_DOES_NOT_IMPLEMENT_IENDPOINT
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
			Rest\Exception::NO_ENDPOINT_IDENTIFIED_FROM_REQUEST_PATH_AND_METHOD
		);
		
	}
	
}
