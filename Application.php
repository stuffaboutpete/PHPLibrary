<?php

namespace PO;

use PO\Application\IDispatchable;
use PO\Application\IBootstrap;
use PO\Application\IExceptionHandler;
use PO\Http\Response;

/**
 * Application
 * 
 * Respresents a dispatchable object
 * which can be set up via bootstrap
 * obkects. Intended to be used as
 * a route object for a web project.
 */
class Application
{
	
	/**
	 * A dispatchable
	 * 
	 * This will handle the more
	 * detailed implementation of
	 * an application such as routing
	 * based on request url. This
	 * object will simply run it with
	 * a response object that is
	 * expected to be initialised.
	 * 
	 * @var PO\Application\IDispatchable
	 */
	private $dispatchable;
	
	/**
	 * A response object
	 * 
	 * Should be initialiased by the
	 * given dispatchable on dispatch
	 * and then its details will be
	 * output to the screen afterwards
	 * 
	 * @var PO\Http\Response
	 */
	private $response;
	
	/**
	 * An inversion of control container
	 * 
	 * A container that is used to
	 * handle any dependencies required
	 * whilst running the application.
	 * Bootstrap objects are expected
	 * to contribute to setting up
	 * the container and then the
	 * dispatchable may use it to
	 * create objects.
	 * 
	 * @var PO\IoCContainer
	 */
	private $ioCContainer;
	
	/**
	 * Array of bootstrap objects
	 * 
	 * Holds only instances of IBootstrap.
	 * All will be run before the
	 * dispatchable is run and each is
	 * encouraged to contribute in some
	 * way to an IoC managed object.
	 * 
	 * @var [PO\Application\IBootstrap]
	 */
	private $bootstraps = [];
	
	/**
	 * An error handler
	 * 
	 * Is provided with any exceptions
	 * encountered during running the
	 * application to handle by way
	 * of display/log/email/etc
	 * 
	 * @var PO\Application\IExceptionHandler
	 */
	private $exceptionHandler;
	
	/**
	 * Constructor
	 * 
	 * Inject a dispatchable object, a response
	 * object and any number of bootstrap objects
	 * 
	 * @param  PO\Application\IDispatchable     $dispatchable An object which will be dispatched
	 * @param  PO\Http\Response                 $response     Will be set during dispatch
	 * @param  PO\IoCContainer                  @ioCContainer Given to bootstraps and dispatchable
	 * @param  array                            $bootstraps   Bootstraps to be run before dispatch
	 * @param  PO\Application\IExceptionHandler @exceptionHandler Accepts any error encountered
	 * @return PO\Application self
	 * @throws InvalidArgumentException if non IBootstrap provided
	 */
	public function __construct(
		IDispatchable		$dispatchable,
		Response			$response,
		IoCContainer		$ioCContainer,
		array				$bootstraps = [],
		IExceptionHandler	$exceptionHandler = null
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
		$this->ioCContainer = $ioCContainer;
		$this->bootstraps = $bootstraps;
		$this->exceptionHandler = $exceptionHandler;
		
	}
	
	/**
	 * Starts the application by processing
	 * bootstraps, dispatching the IDispatchable
	 * and then processing the response
	 * 
	 * @return PO\Application self
	 */
	public function run()
	{
		
		try {
			
			// Run any provided bootstrap files
			foreach ($this->bootstraps as $bootstrap) {
				$bootstrap->run($this->ioCContainer);
			}
			
			// Dispatch the dispatchable, passing along
			// the application and response objects
			$this->dispatchable->dispatch($this->response, $this->ioCContainer);
			
		} catch (\Exception $exception) {
			
			// If we have an uncaught exception
			// in running the application, pass
			// it to the error handler if set
			if (isset($this->exceptionHandler)) {
				$this->exceptionHandler->handleException($exception, $this->response);
				$this->response->process();
				return;
			}
			
			// Ensure the response is 500
			// (Internal Server Error)
			$this->response->set500();
			
			// Draw the exception
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
	
}
