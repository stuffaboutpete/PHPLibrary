<?php

namespace Suburb;

abstract class Exception
extends \Exception
{
	
	abstract protected function getMessageFromCode($code);
	
	public function __construct($code, $extraInformation = null)
	{
		$message = $this->getMessageFromCode($code);
		if ($extraInformation) $message .= " ($extraInformation)";
		parent::__construct($message, $code);
		
	}
	
}