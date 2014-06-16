<?php

namespace PO\Http;

class Request
{
	
	public function getRequestMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
	
	public function getBody()
	{
		return json_decode(file_get_contents('php://input'), true);
	}
	
}
