<?php

namespace Suburb\HttpRequest\Api\TokenValidator;

use Suburb\HttpRequest\Response;

interface IMechanism
{
	
	public function getHeaders();
	public function getBasePath();
	public function getRequestEncoding();
	public function getTokenFieldName();
	public function getEntryPath();
	public function getEntryRequiredFields();
	public function isInstantWin(Response $response);
	public function getInstantWinIdentityField(Response $response);
	public function getCompletionPath();
	public function getCompletionRequiredFields();
	public function validateEntryResponse(Response $response);
	public function validateCompletionResponse(Response $response);
	
}
