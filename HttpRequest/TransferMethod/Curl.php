<?php

namespace Suburb\HttpRequest\TransferMethod;

class Curl
implements \Suburb\HttpRequest\ITransferMethod
{
	
	public function request(
		/* string */					$path,
		/* string */					$method,
		\Suburb\HttpRequest\Response	$response,
		array							$headers = null,
		/* mixed */						$data = null
	)
	{
		$ch = \curl_init($path);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		if ($method == 'POST' && is_null($data)) $headers['Content-Length'] = 0;
		array_walk($headers, function(&$value, $key){
			$value = "$key: $value";
		});
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			$headers
		);
		switch ($method) {
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				if ($data) {
					curl_setopt(
						$ch,
						CURLOPT_POSTFIELDS,
						$data
					);
				}
				break;
			case 'PUT':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'put');
				if ($data) {
					curl_setopt(
						$ch,
						CURLOPT_POSTFIELDS,
						$data
					);
				}
				break;
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'delete');
		}
		$body = curl_exec($ch);
		$response->initialise(
			curl_getinfo($ch, CURLINFO_HTTP_CODE),
			curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
			$body
		);
		curl_close($ch);
		return $response;
	}
	
}