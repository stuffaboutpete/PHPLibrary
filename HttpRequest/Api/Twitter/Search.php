<?php

/**
 * Please note that this class uses the
 * Twitter v1 API which is deprecated and
 * could stop functioning at any time.
 * 
 * This class only exists because the new
 * v1.1 API requires authentication to search.
 * 
 * Once Suburb\HttpRequest\Api\Twitter is
 * developed enough to easily use the new
 * API, this class should be removed.
 */

namespace Suburb\HttpRequest\Api\Twitter;

class Search
extends \Suburb\HttpRequest
{
	
	public function __construct($transferMethod, $response)
	{
		parent::__construct(
			$transferMethod,
			$response,
			'http://search.twitter.com'
		);
	}
	
	public function search($query)
	{
		return $this->get("search.json?q=$query");
	}
	
	public function searchForHashTag($tag)
	{
		return $this->get("search.json?q=%23$tag");
	}
	
}