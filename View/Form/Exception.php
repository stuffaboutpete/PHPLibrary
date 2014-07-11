<?php

namespace PO\View\Form;

class Exception
extends \PO\Exception
{
	
	const NO_ELEMENT_PROVIDED							= 0;
	const INVALID_ELEMENT_DEFINITION					= 1;
	const INVALID_METHOD_PROVIDED						= 2;
	const NON_STRING_PROVIDED_AS_TARGET					= 3;
	const INVALID_ELEMENT_KEY_PROVIDED					= 4;
	const MISSING_VALUE_IN_ELEMENT_DECLARATION			= 5;
	const INVALID_VALUE_IN_ELEMENT_DECLARATION			= 6;
	const CONTENT_PROVIDED_FOR_SELF_CLOSING_TAG			= 7;
	const REDUNDANT_KEY_PROVIDED_IN_ELEMENT_DECLARATION	= 8;
	const DUPLICATE_AUTOFOCUS							= 9;
	
	protected function getMessageFromCode($code)
	{
		
		switch ($code) {
			
			case Exception::NO_ELEMENT_PROVIDED:
				return 'At least one form element must be provided';
			break;
			
			case Exception::INVALID_ELEMENT_DEFINITION:
				return 'An element was declared incorrectly. Each element must ' .
					'be either an integer or an array containing the key \'element\'';
			break;
			
			case Exception::INVALID_METHOD_PROVIDED:
				return 'Form method must be provided as either \'post\' or \'get\'';
			break;
			
			case Exception::NON_STRING_PROVIDED_AS_TARGET:
				return 'If a target is supplied, it must be a string';
			break;
			
			case Exception::INVALID_ELEMENT_KEY_PROVIDED:
				return 'A provided element key was not equal to any constant defined in this class';
			break;
			
			case Exception::MISSING_VALUE_IN_ELEMENT_DECLARATION:
				return 'A provided element declaration was missing a ' .
					'value required by another value';
			break;
			
			case Exception::INVALID_VALUE_IN_ELEMENT_DECLARATION:
				return 'A provided element declaration was deemed invalid';
			break;
			
			case Exception::CONTENT_PROVIDED_FOR_SELF_CLOSING_TAG:
				return 'Content was supplied for an element which is self ' .
					'closing and therefore cannot display it';
			break;
			
			case Exception::REDUNDANT_KEY_PROVIDED_IN_ELEMENT_DECLARATION:
				return 'A provided element declaration key was not relevent for the given element';
			break;
			
			case Exception::DUPLICATE_AUTOFOCUS:
				return 'More than one element was declared with an autofocus attribute';
			break;
			
		}
		
	}
	
}
