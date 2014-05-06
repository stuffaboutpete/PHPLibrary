<?php

namespace Suburb\HttpRequest\Api;

use Suburb\HttpRequest;
use Suburb\HttpRequest\ITransferMethod;
use Suburb\HttpRequest\Response;
use Suburb\HttpRequest\Api\TokenValidator\IMechanism;
use Suburb\HttpRequest\Api\TokenValidator\Exception;

class TokenValidator
extends HttpRequest
{
	
	private $mechanism;
	private $requests = [];
	
	public function __construct(
		ITransferMethod	$transferMethod,
		Response		$response,
		IMechanism		$mechanism
	)
	{
		$requestEncoding = $mechanism->getRequestEncoding();
		parent::__construct(
			$transferMethod,
			$response,
			$mechanism->getBasePath(),
			$mechanism->getHeaders(), // not tested
			isset($requestEncoding) ? $requestEncoding : 'json'
		);
		$this->mechanism = $mechanism;
	}
	
	public function isValidToken($token, array $data = null)
	{
		
		if (!is_string($token)) {
			throw new Exception(
				Exception::NON_STRING_TOKEN_SUPPLIED,
				'Supplied type: ' . gettype($token)
			);
		}
		
		$this->validateRequiredFields((array) $this->mechanism->getEntryRequiredFields(), $data);
		
		if (!is_array($data)) $data = [];
		
		$tokenFieldName = $this->mechanism->getTokenFieldName();
		if (!$tokenFieldName) $tokenFieldName = 'token';
		
		$data[$tokenFieldName] = $token;
		
		$response = parent::post(
			$this->mechanism->getEntryPath(),
			$data
		);
		
		$this->requests[$token] = $response;
		
		$this->mechanism->validateEntryResponse($response);
		
		return true;
		
	}
	
	public function isInstantWin($token)
	{
		
		if (!is_string($token)) {
			throw new Exception(
				Exception::NON_STRING_TOKEN_SUPPLIED,
				'Supplied type: ' . gettype($token)
			);
		}
		
		if (!isset($this->requests[$token])) {
			throw new Exception(Exception::UNRECOGNISED_TOKEN_PROVIDED_FOR_FURTHER_PROCESSING);
		}
		
		return $this->mechanism->isInstantWin($this->requests[$token]);
		
	}
	
	public function completeEntry(
		/* string */	$token = null,
		array			$data = null,
		array			$instantWinIdentityField = null
	)
	{
		
		// if (!isset($instantWinIdentityField) && !$this->isInstantWin($token)) {
		// 	throw new Exception(Exception::NON_INSTANT_WIN_TOKEN_PROVIDED_TO_COMPLETE_ENTRY);
		// }
		
		$this->validateRequiredFields(
			(array) $this->mechanism->getCompletionRequiredFields(),
			$data
		);
		
		if (!is_array($data)) $data = [];
		
		if (isset($instantWinIdentityField)) {
			$data = array_merge($data, $instantWinIdentityField);
		} else {
			$data = array_merge(
				$data,
				(array) $this->mechanism->getInstantWinIdentityField($this->requests[$token])
			);
		}
		
		$response = parent::post($this->mechanism->getCompletionPath(), $data);
		
		$this->mechanism->validateCompletionResponse($response);
		
		return true;
		
	}
	
	public function getInstantWinIdentityField($token)
	{
		
		// @todo Method not tested at all
		
		if (!is_string($token)) {
			throw new Exception(
				Exception::NON_STRING_TOKEN_SUPPLIED,
				'Supplied type: ' . gettype($token)
			);
		}
		
		if (!isset($this->requests[$token])) {
			throw new Exception(Exception::UNRECOGNISED_TOKEN_PROVIDED_FOR_FURTHER_PROCESSING);
		}
		
		return $this->mechanism->getInstantWinIdentityField($this->requests[$token]);
		
	}
	
	private function validateRequiredFields($requiredFields, $suppliedData)
	{
		foreach ($requiredFields as $field) {
			if (!isset($suppliedData[$field])) {
				throw new Exception(
					Exception::REQUIRED_ENTRY_FIELDS_NOT_SUPPLIED,
					"Missing field: $field" .
					'; Expected fields: ' . implode(', ', $requiredFields) .
					'; Supplied fields ' . implode(', ', array_keys($suppliedData))
				);
			}
		}
	}
	
}
