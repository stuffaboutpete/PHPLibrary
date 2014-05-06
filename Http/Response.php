<?php

namespace PO\Http;

class Response
{
	
	private $code;
	private $body;
	private $contentType = 'text/html';
	private $newLocation;
	
	public function process()
	{
		
		if (!$this->isInitialised()) {
			throw new \RuntimeException(
				'Response must be initialised (eg $response->set200()) ' .
				'before it can be processed'
			);
		}
		
		$headers = array(
			'200' => 'HTTP/1.1 200 OK',
			'201' => 'HTTP/1.1 201 Created',
			'302' => 'HTTP/1.1 302 Found',
			'304' => 'HTTP/1.1 304 Not Modified',
			'400' => 'HTTP/1.1 400 Bad Request',
			'401' => 'HTTP/1.1 401 Unauthorized',
			'403' => 'HTTP/1.1 403 Forbidden',
			'404' => 'HTTP/1.1 404 Not Found',
			'500' => 'HTTP/1.1 500 Internal Server Error',
			'501' => 'HTTP/1.1 501 Not Implemented'
		);
		
		header($headers[$this->code]);
		header('Content-type: ' . $this->contentType);
		echo $this->body;
		
		if ($this->newLocation) {
			header('Location: ' . $this->newLocation, true, $this->code);
		}
		
	}
	
	public function set200($representation)
	{
		$this->initialise(200, $representation, is_array($representation));
	}
	
	public function set201($representation)
	{
		$this->initialise(201, $representation, is_array($representation));
	}
	
	public function set302($location)
	{
		$this->newLocation = $location;
		$this->initialise(302);
	}
	
	public function set304()
	{
		$this->initialise(304);
	}
	
	public function set400($message)
	{
		if (!is_string($message)) {
			throw new \InvalidArgumentException(
				'Message must be a string'
			);
		}
		$this->initialise(400, $message, false);
	}
	
	public function set401()
	{
		$this->initialise(401);
	}
	
	public function set403($message)
	{
		if (!is_string($message)) {
			throw new \InvalidArgumentException(
				'Message must be a string'
			);
		}
		$this->initialise(403, $message, false);
	}
	
	public function set404($message = null)
	{
		$this->initialise(404, $message);
	}
	
	public function set500($message = 'Sorry, there was an unexpected error')
	{
		if (!is_string($message)) {
			throw new \InvalidArgumentException('Message must be a string');
		}
		$this->initialise(500, $message, false);
	}
	
	public function set501()
	{
		$this->initialise(501);
	}
	
	public function isInitialised()
	{
		return (isset($this->code));
	}
	
	private function initialise($code, $body = null, $convertBody = false)
	{
		
		if ($this->isInitialised()) {
			throw new \RuntimeException(
				'Response object can only be initialised once'
			);
		}
		
		if ($convertBody) {
			$body = json_encode($body);
			$this->contentType = 'application/json';
		}
		
		$this->code = $code;
		$this->body = $body;
		
	}
	
}