<?php

namespace Suburb\HttpRequest;

class Response
{
	
	private $code;
	private $contentType;
	private $rawBody;
	private $decodedBody;
	
	public function initialise($code, $contentType, $body = null)
	{
		
		// Ensure that the response has not
		// already been initialised
		if ($this->code) {
			throw new \RuntimeException(
				'Response can only be initialised once'
			);
		}
		
		// Ensure that the provided response code
		// is in the supported list
		if (!in_array(
			$code,
			array(200, 201, 304, 400, 401, 403, 404, 500, 501)
		)) {
			throw new \InvalidArgumentException(
				"Invalid http response code ($code)"
			);
		}
		
		// Save the provided data
		$this->code = $code;
		$this->contentType = $contentType;
		$this->rawBody = $body;
		
		// Identify certain content-types and
		// decode the body accordingly, saving
		// the new data to this::decodedBody
		switch (explode(';', $contentType)[0]) {
			case 'application/json':
				$this->decodedBody = json_decode($body, true);
				break;
		}
		
	}
	
	public function getCode()
	{
		
		// Throw if the response hasn't
		// yet been initialised
		if (!$this->code) {
			throw new \RuntimeException(
				'Response must be initialised first'
			);
		}
		
		return $this->code;
		
	}
	
	public function getContentType()
	{
		
		// Throw if the response hasn't
		// yet been initialised
		if (!$this->contentType) {
			throw new \RuntimeException(
				'Response must be initialised first'
			);
		}
		
		return $this->contentType;
		
	}
	
	public function getBody()
	{
		
		// Throw if the response hasn't
		// yet been initialised
		if (!$this->contentType) {
			throw new \RuntimeException(
				'Response must be initialised first'
			);
		}
		
		// Return a decoded body if one is
		// available, else return the raw body
		return ($this->decodedBody) ? $this->decodedBody : $this->rawBody;
		
	}
	
	public function getRawBody()
	{
		
		// Throw if the response hasn't
		// yet been initialised
		if (!$this->contentType) {
			throw new \RuntimeException(
				'Response must be initialised first'
			);
		}
		
		return $this->rawBody;
		
	}
	
	public function isInitialised()
	{
		
		// Return true or false based on whether
		// we have a response code set
		return ($this->code) ? true : false;
		
	}
	
}