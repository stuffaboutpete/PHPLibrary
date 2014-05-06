<?php

namespace Suburb\HttpRequest\Api\TokenValidator\Mechanism;

use Suburb\Gateway;
use Suburb\HttpRequest\Api\TokenValidator\IMechanism;
use Suburb\HttpRequest\Response;
use Suburb\HttpRequest\Api\TokenValidator\Mechanism\Exception;

class Sponge
implements IMechanism
{
	
	private $gateway;
	
	public function __construct(Gateway $gateway)
	{
		$this->gateway = $gateway;
	}
	
	public function getHeaders()
	{
		return ['Authorization' => 'Basic ' . base64_encode('carlsberg:iFo7bael')];
	}
	
	public function getBasePath()
	{
		//TODO: use config file
		$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		if(strrpos($url,'stage.carlsbergfansquad.co.uk') === false){
			return 'https://api2.unityplatform.io/edfbb70ecef645ce8d47799ebf822833';  
		}
		else{
			return 'https://api-staging.unityplatform.io/edfbb70ecef645ce8d47799ebf822833';
		}
	}
	
	public function getRequestEncoding()
	{
		return 'form-urlencoded';
	}
	
	public function getTokenFieldName()
	{
		return 'code';
	}
	
	public function getEntryPath()
	{
		return 'entries';
	}
	
	public function getEntryRequiredFields()
	{
		return [
			'email',
			'firstName',
			'lastName',
			'phoneNumber',
			'teamId',
			'dob'
		];
	}
	
	public function validateEntryResponse(Response $response)
	{
		
		if (
			$response->getCode() == 201
			&& isset($response->getBody()['status'])
			&& $response->getBody()['status'] == 'ok'
		) return true;
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'required') {
			throw new Exception(Exception::MISSING_REQUIRED_FIELDS);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'too_long') {
			throw new Exception(Exception::PROVIDED_FIELD_TOO_LONG);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'invalid_format') {
			throw new Exception(Exception::INVALID_FORMAT);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'not_found') {
			throw new Exception(Exception::INVALID_TOKEN);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'used_different_user') {
			throw new Exception(Exception::TOKEN_ALREADY_USED);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'used_same_user') {
			throw new Exception(Exception::TOKEN_ALREADY_USED);
			return;
		}
		
		throw new Exception(
			Exception::UNKNOWN_ERROR,
			'Response code: ' . $response->getCode()
		);
		
	}
	
	public function isInstantWin(Response $response)
	{
		if (!$response->getBody()['win']) return false;
		$prizes = [];
		foreach ($response->getBody()['prizeIds'] as $prizeId) {
			array_push($prizes, $this->gateway->fetch(\FanSquad\Model\Prize::Class, $prizeId));
		}
		return $prizes;
	}
	
	public function getInstantWinIdentityField(Response $response)
	{
		$refs = [];
		foreach ($response->getBody()['winRefs'] as $winRef) {
			array_push($refs, $winRef);
		}
		return $refs;
	}
	
	public function getCompletionPath()
	{
		return 'claims';
	}
	
	public function getCompletionRequiredFields()
	{
		return [
			'postcode',
			'address1',
			'address2',
			'town'
		];
	}
	
	public function validateCompletionResponse(Response $response)
	{
		
		if (
			$response->getCode() == 201
			&& isset($response->getBody()['status'])
			&& $response->getBody()['status'] == 'ok'
		) return true;
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'required') {
			throw new Exception(Exception::MISSING_REQUIRED_FIELDS);
			return;
		}
		
		if ($response->getCode() == 400
		&&	$response->getBody()['errors'][0]['code'] == 'too_long') {
			throw new Exception(Exception::PROVIDED_FIELD_TOO_LONG);
			return;
		}
		
		throw new Exception(
			Exception::UNKNOWN_ERROR,
			'Response code: ' . $response->getCode()
		);
		
	}
	
}
