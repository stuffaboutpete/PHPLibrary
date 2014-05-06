<?php

namespace Suburb\Model\Property;

interface IConstraint
{
	
	public function isValid($value);
	
}
