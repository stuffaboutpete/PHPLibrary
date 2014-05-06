<?php

namespace PO;

class HttpRequest
{
	
	private $transferMethod;
	private $response;
	private $basePath;
	private $headers;
	private $dataEncoding;
	
	public function __construct(
		HttpRequest\ITransferMethod	$transferMethod,
		HttpRequest\Response		$response,
		/* string */				$basePath = null,
		array						$headers = [],
		/* string */				$dataEncoding = 'json'
	)
	{
		
		// Remove leading / from base path
		if (substr($basePath, -1) == '/') {
			$basePath = substr($basePath, 0, -1);
		}
		
		// Ensure the selected data encoding is
		// valid (currently none or json)
		if (!in_array($dataEncoding, [false, 'json', 'form-urlencoded'], true)) {
			throw new \InvalidArgumentException(
				'Data encoding type must be false, "json" or "form-urlencoded"'
			);
		}
		
		// Save the provided data
		$this->transferMethod = $transferMethod;
		$this->response = $response;
		$this->basePath = $basePath;
		$this->headers = $headers;
		$this->dataEncoding = $dataEncoding;
		
	}
	
	public function get($path, array $headers = [])
	{
		return $this->request($path, 'GET', $headers);
	}
	
	public function post($path, $data, array $headers = [])
	{
		return $this->request($path, 'POST', $headers, $data);
	}
	
	public function put($path, $data, array $headers = [])
	{
		return $this->request($path, 'PUT', $headers, $data);
	}
	
	public function delete($path, array $headers = [])
	{
		return $this->request($path, 'DELETE', $headers);
	}
	
	private function request($path, $verb, array $headers = [], $data = null)
	{
		
		// Combine the base path and request path
		$path = $this->getCompletePath($path);
		
		// If data encoding is enabled, do it here
		switch ($this->dataEncoding) {
			case 'json':
				$data = json_encode($data);
				//header('Content-Type: application/json'); // @todo Not tested...
				break;
			case 'form-urlencoded':
				// @todo Not tested this encoding
				if ($data) {
					array_walk($data, function(&$item, $key){
						$key = rawurlencode($key);
						$item = rawurlencode($item);
						$item = "$key=$item";
					});
					$data = implode('&', $data);
				}
				//header('Content-Type: application/x-www-form-urlencoded'); // @todo Not tested...
				break;
		}
		
		// @todo The idea of cloning the response
		// has not been tested! This is so that
		// multiple requests can be made on the
		// same HttpRequest object.
		$response = clone $this->response;
		
		// Call the transfer method
		$this->transferMethod->request(
			$path,
			$verb,
			$response,
			array_merge($this->headers, $headers),
			$data
		);
		
		// Ensure that the transfer method has
		// initialised the response object
		if (!$response->isInitialised()) {
			throw new \RuntimeException(
				'Response object must be initialised by transfer method object'
			);
		}
		
		return $response;
		
	}
	
	protected function getCompletePath($requestPath)
	{
		
		// If there is a base path saved, merge
		// it with the request path ensuring there
		// is a forward slash between them
		if ($this->basePath) {
			$requestPath = $this->basePath . ((substr($requestPath, 0, 1) == '/')
				? $requestPath
				: '/' . $requestPath);
		}
		
		return $requestPath;
		
	}
	
}