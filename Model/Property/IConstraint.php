<?php

namespace PO\Model\Property;

interface IConstraint
{
	
	public function isValid($value);
	
}
