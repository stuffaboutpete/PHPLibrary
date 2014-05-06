<?php

namespace PO\HttpRequest\Api;

class Twitter
extends \PO\HttpRequest
{
	
	private $application;
	private $authMechanism;
	
	public function __construct(
		/* Type checked by parent */	$transferMethod,
		/* Type checked by parent */	$response,
		Twitter\IApplication			$application,
		Twitter\IAuthMechanism			$authMechanism
	)
	{
		
		$this->authMechanism = $authMechanism;
		
		parent::__construct(
			$transferMethod,
			$response,
			'https://api.twitter.com',
			[],
			'form-urlencoded'
		);
		
		$this->application = $application;
		$this->authMechanism = $authMechanism;
		
	}
	
	public function signIn()
	{
		
		$path = '/oauth/request_token';
		
		$response = parent::post($path, null, [
			'Authorization' => $this->getAuthHeader(
				'POST',
				$path
			)
		]);
		
		$responseParts = [];
		
		foreach (explode('&', $response->getBody()) as $part) {
			$part = explode('=', $part);
			$responseParts[$part[0]] = $part[1];
		}
		
		return $responseParts['oauth_token'];
		
	}
	
	public function completeSignIn($verifier)
	{
		
		$path = '/oauth/access_token';
		
		$response = parent::post($path, ['oauth_verifier' => $verifier], [
			'Authorization' => $this->getAuthHeader(
				'POST',
				$path,
				[],
				['oauth_verifier' => $verifier]
			)
		]);
		
		if ($response->getCode() != 200) {
			return $response;
		}
		
		$responseParts = [];
		
		foreach (explode('&', $response->getBody()) as $part) {
			$part = explode('=', $part);
			$responseParts[$part[0]] = $part[1];
		}
		
		return $responseParts;
		
	}
	
	public function get($path, $pathParameters = [])
	{
		$pathWithParameters = $this->getRequestPath($path, $pathParameters);
		return parent::get('/1.1' . $pathWithParameters, [
			'Authorization' => $this->getAuthHeader(
				'GET',
				'/1.1' . $path,
				$pathParameters
			)
		]);
	}
	
	public function post($path, $pathParameters = [], $bodyParameters = [])
	{
		$pathWithParameters = $this->getRequestPath($path, $pathParameters);
		return parent::post('/1.1' . $pathWithParameters, $bodyParameters, [
			'Authorization' => $this->getAuthHeader(
				'POST',
				'/1.1' . $path,
				$pathParameters,
				$bodyParameters
			)
		]);
	}
	
	public function put($path, $pathParameters = [], $bodyParameters = [])
	{
		$pathWithParameters = $this->getRequestPath($path, $pathParameters);
		return parent::put('/1.1' . $pathWithParameters, $bodyParameters, [
			'Authorization' => $this->getAuthHeader(
				'PUT',
				'/1.1' . $path,
				$pathParameters,
				$bodyParameters
			)
		]);
	}
	
	public function delete($path, $pathParameters = [])
	{
		$pathWithParameters = $this->getRequestPath($path, $pathParameters);
		return parent::delete('/1.1' . $pathWithParameters, [
			'Authorization' => $this->getAuthHeader(
				'DELETE',
				'/1.1' . $path,
				$pathParameters
			)
		]);
	}
	
	private function getAuthHeader(
		$verb,
		$path,
		$pathParameters = [],
		$bodyParameters = []
	)
	{
		
		$version			= '1.0';
		$signatureMethod	= 'HMAC-SHA1';
		$timestamp			= time();
		$nonce				= $this->getNonce();
		$consumerKey		= $this->application->getConsumerKey();
		$token				= $this->authMechanism->getAccessToken();
		$signature			= $this->getSignature(
			$verb,
			$this->getCompletePath($path),
			array_merge($pathParameters, $bodyParameters),
			$consumerKey,
			$nonce,
			$signatureMethod,
			$timestamp,
			$token,
			$version
		);
		
		$headerParts = [
			'oauth_consumer_key'		=> $consumerKey,
			'oauth_nonce'				=> $nonce,
			'oauth_signature'			=> $signature,
			'oauth_signature_method'	=> $signatureMethod,
			'oauth_timestamp'			=> $timestamp,
			'oauth_token'				=> $token,
			'oauth_version'				=> $version
		];
		
		if (!$token) unset($headerParts['oauth_token']);
		
		$header = 'OAuth ';
		
		$header .= implode(', ', array_map(function($value, $key){
			return rawurlencode($key) . '="' . rawurlencode($value) . '"';
		}, $headerParts, array_keys($headerParts)));
		
		return $header;
		
	}
	
	private function getSignature(
		$verb,
		$path,
		$parameters,
		$consumerKey,
		$nonce,
		$signatureMethod,
		$timestamp,
		$token,
		$version
	)
	{
		
		$parameters['oauth_consumer_key']		= $consumerKey;
		$parameters['oauth_nonce']				= $nonce;
		$parameters['oauth_signature_method']	= $signatureMethod;
		$parameters['oauth_timestamp']			= $timestamp;
		$parameters['oauth_version']			= $version;
		
		if ($token) $parameters['oauth_token'] = $token;
		
		$newParameters = [];
		
		foreach ($parameters as $key => $value) {
			$newParameters[rawurlencode($key)] = rawurlencode($value);
		}
		$parameters = $newParameters;
		
		ksort($parameters);
		
		$parameterString = implode('&', array_map(function($value, $key){
			return "$key=$value";
		}, $parameters, array_keys($parameters)));
		
		$baseString = $verb .
			'&' .
			rawurlencode($path) .
			'&' .
			rawurlencode($parameterString);
		
		$signingKey = rawurlencode($this->application->getConsumerSecret()) .
			'&' . rawurlencode($this->authMechanism->getAccessTokenSecret());
		
		return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
		
	}
	
	private function getNonce()
	{
		return base64_encode(mt_rand());
	}
	
	private function getRequestPath($path, $parameters)
	{
		return $path . '?' . implode('&', array_map(function($value, $key){
			return "$key=$value";
		}, $parameters, array_keys($parameters)));
	}
}