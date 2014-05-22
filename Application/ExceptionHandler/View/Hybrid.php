<?php

namespace PO\Application\ErrorHandler\View;

use PO\Application;
use PO\Http\Response;
use PO\Application\ErrorHandler\View as DebugView;

class Hybrid
implements Application\IErrorHandler
{
	
	const CAN_VIEW_DEBUG_INFO = 816235;
	
	private $nonAuthenticatedView;
	private $debugView;
	private $showDebugInfo;
	
	public function __construct(Application\IErrorHandler $nonAuthenticatedView)
	{
		$this->nonAuthenticatedView = $nonAuthenticatedView;
	}
	
	public function setup(Application $application, Response $response)
	{
		$this->showDebugInfo = false;
		if ($application->hasExtension('authenticator')) {
			if ($application->getAuthenticator()->userCanAccess(self::CAN_VIEW_DEBUG_INFO)) {
				$this->showDebugInfo = true;
			}
		}
		$this->getView()->setup($application, $response);
	}
	
	public function handleException(\Exception $exception, $recommendedResponseCode = null)
	{
		$this->getView()->handleException($exception, $recommendedResponseCode);
	}
	
	public function handleError()
	{
		$this->getView()->handleError();
	}
	
	private function getView()
	{
		if ($this->showDebugInfo) {
			return $this->getDebugView();
		} else {
			return $this->nonAuthenticatedView;
		}
	}
	
	private function getDebugView()
	{
		if (!isset($this->debugView)) {
			$this->debugView = new DebugView();
		}
		return $this->debugView;
	}
	
}
