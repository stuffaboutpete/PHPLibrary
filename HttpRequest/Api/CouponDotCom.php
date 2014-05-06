<?php

namespace Suburb\HttpRequest\Api;

class CouponDotCom
extends \Suburb\HttpRequest
{
	
	public function __construct($transferMethod, $response)
	{
		parent::__construct(
			$transferMethod,
			$response,
			'http://cpt.coupons.com/au'
		);
	}
	
	public function generateCPT($pin, $offerCode, $shortKey, $longKey)
	{
		return $this->get(
			"/encodecpt.aspx?p=$pin&oc=$offerCode&sk=$shortKey&lk=$longKey"
		);
	}
	
}