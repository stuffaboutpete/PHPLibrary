<?php

namespace PO;

use PO\Application\IDispatchable;
use PO\Application\IBootstrap;
use PO\Application\IErrorHandler;
use PO\Http\Response;

/**
 * Needs re-commenting
 * 
 * Application
 * 
 * Respresents a dispatchable object
 * which can be bootstrapped and
 * extended. Intended to be used as
 * a route object for a web project.
 */
class Application
{
	
	/**
	 * A dispatchable
	 * 
	 * @var PO\Application\IDispatchable
	 */
	private $dispatchable;
	
	/**
	 * A response object
	 * 
	 * @var PO\Http\Response
	 */
	private $response;
	
	/**
	 * Array of bootstrap objects
	 * 
	 * Holds only instances of
	 * PO\Application\IBootstrap
	 * 
	 * @var [type]
	 */
	private $bootstraps = [];
	
	private $exceptionHandler;
	
	/**
	 * Extensions that have been registered
	 * 
	 * @var array
	 */
	private $extensions = [];
	
	/**
	 * Inject a dispatchable object, a response
	 * object and any number of bootstrap objects
	 * 
	 * @param  PO\Application\IDispatchable $dispatchable An object which will be dispatched
	 * @param  PO\Http\Response             $response     Will be set during dispatch
	 * @param  array                            $bootstraps   Processed before dispatch
	 * @return null
	 * @throws InvalidArgumentException         If non IBootstrap provided
	 */
	public function __construct(
		IDispatchable	$dispatchable,
		Response		$response,
		array			$bootstraps = [],
		IErrorHandler	$exceptionHandler = null
	)
	{
		
		// Ensure that each element in the
		// bootstrap array is an IBootstrap
		foreach ($bootstraps as $bootstrap) {
			if (!$bootstrap instanceof IBootstrap) {
				throw new \InvalidArgumentException(
					'Bootstraps must be an array containing only ' .
					'instance of \PO\Application\IBootstrap'
				);
			}
		}
		
		// Save the provided objects
		$this->dispatchable = $dispatchable;
		$this->response = $response;
		$this->bootstraps = $bootstraps;
		$this->exceptionHandler = $exceptionHandler;
		
	}
	
	/**
	 * Starts the application by processing
	 * bootstraps, dispatching the IDispatchable
	 * and then processing the response
	 * 
	 * @return PO\Application Self
	 * @throws RuntimeException   If response is not initialised by dispatchable
	 */
	public function run()
	{
		
		if (isset($this->exceptionHandler)) {
			$this->exceptionHandler->setup($this, $this->response);
		}
		
		try {
			
			// Run any provided bootstrap files
			foreach ($this->bootstraps as $bootstrap) {
				$bootstrap->run($this);
			}
			
			// Dispatch the dispatchable, passing along
			// the application and response objects
			$this->dispatchable->dispatch($this, $this->response);
			
		} catch (\Exception $exception) {
			
			if (isset($this->exceptionHandler)) {
				$this->exceptionHandler->handleException($exception, 500);
			}
			
			$this->response->set500();
			
			throw $exception;
			
		}
		
		// If the response is not initialised during
		// dispatch, throw an exception
		if (!$this->response->isInitialised()) {
			throw new \RuntimeException(
				'IDispatchable must initialise the provided response object during dispatch'
			);
		}
		
		// Process the response object so that codes
		// and headers are set and the body is
		// output to the screen
		$this->response->process();
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Allows the application object to be extended
	 * so that information, objects or functions can
	 * be stored against it
	 * 
	 * @param  string $key         The alias for the extension
	 * @param  mixed  $value       Any data or callback which can be retrieved against the key
	 * @param  mixed  $callbackArg Data to be passed to the value if it is a callback
	 * @return PO\Application  Self
	 */
	public function extend($key, $value, $callbackArg = null)
	{
		
		// Throw an exception if the extension
		// has already been registered
		if ($this->hasExtension($key)) {
			throw new \RuntimeException("Extension with the key $key has already been registered");
		}
		
		// Save the extension
		$this->extensions[$key] = [
			'extension'		=> $value,
			'callbackArg'	=> $callbackArg
		];
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Checks for the existence of an extension
	 * 
	 * @param  string  $key The name of the extension
	 * @return boolean      Whether the extension exists
	 */
	public function hasExtension($key)
	{
		return (isset($this->extensions[$key]));
	}
	
	/**
	 * Handles calls to resolve extensions,
	 * of the format getNameOfExtension()
	 * 
	 * @param  string $key       The name of the method called
	 * @param  mixed  $arguments Arguments passed to the method on call
	 * @return mixed             The data stored as the extension
	 * @throws BadMethodCallException If the method name is not in the format "getSomething"
	 * @throws BadMethodCallException If no extension exists of the name "something"
	 * @throws BadMethodCallException If more than one argument is provided
	 */
	public function __call($key, $arguments = null)
	{
		
		// If the call was not in the format
		// getSomething, throw an exception
		if (substr($key, 0, 3) != 'get' || strlen($key) < 4) {
			throw new \BadMethodCallException("Unknown method called: $key");
		}
		
		// Remove the 'get' and lowercase
		// the first letter
		$key = lcfirst(substr($key, 3, strlen($key)));
		
		// Throw an exception if the extension
		// has not been registered
		if (!$this->hasExtension($key)) {
			throw new \BadMethodCallException(
				"Application does not have extension \"$key\""
			);
		}
		
		// If the extension is a function...
		if (is_callable($this->extensions[$key]['extension'])) {
			
			// Ensure we are only provided with
			// one argument to pass along
			if (count($arguments) > 1) {
				throw new \BadMethodCallException(
					'Application extensions can only accept one resolve time argument'
				);
			}
			
			// Call the method, provided any resolve
			// time argument and any declare time argument
			return $this->extensions[$key]['extension'](
				(isset($arguments[0])) ? $arguments[0] : null,
				$this->extensions[$key]['callbackArg']
			);
			
		}
		
		// Else just return the value of the extension
		return $this->extensions[$key]['extension'];
		
	}
	
}
