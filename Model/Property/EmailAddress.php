<?php

namespace PO\Model\Property;
use PO\Model as Model;

require_once dirname(__FILE__) . '/../Property.php';

class EmailAddress
extends Model\Property
{
	
	public function editInput($originalValue)
	{
		if (!is_string($originalValue)) {
			throw new \InvalidArgumentException(
				'Email address must be a string'
			);
		}
		$atIndex = strrpos($originalValue, '@');
		if ($atIndex === false) {
			throw new \InvalidArgumentException(
				'Email address must contain @ symbol'
			);
		}
		$domain = substr($originalValue, $atIndex + 1);
		$local = substr($originalValue, 0, $atIndex);
		$domainLen = strlen($domain);
		$localLen = strlen($local);
		if ($localLen < 1) {
			throw new \InvalidArgumentException(
				'Email address must contain a local part'
			);
		}
		if ($localLen > 64) {
			throw new \InvalidArgumentException(
				'Local part of address must not be longer than 64 characters'
			);
		}
		if ($domainLen < 1) {
			throw new \InvalidArgumentException(
				'Email address must contain a domain part'
			);
		}
		if ($localLen > 64) {
			throw new \InvalidArgumentException(
				'Domain part of address must not be longer than 255 characters'
			);
		}
		if ($local[0] == '.'
		||  $local[$localLen - 1] == '.'
		||  preg_match('/\\.\\./', $local)) {
			throw new \InvalidArgumentException(
				'Local part of address cannot begin or end with dots or ' .
				'contain two consecutive dots'
			);
		}
		if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
			throw new \InvalidArgumentException(
				'Domain part of address contains an illegal character'
			);
		}
		if (preg_match('/\\.\\./', $domain)) {
			throw new \InvalidArgumentException(
				'Domain part of address cannot contain consecutive dots'
			);
		}
		if (!preg_match(
			'/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
			str_replace('\\\\', '', $local)
		)) {
			throw new \InvalidArgumentException(
				'Local part of address contains illegal characters that ' .
				'has not been wrapped in quotes'
			);
		}
		// @todo Disabled because it requires inet connection and is REALLY slow
		//if (!checkdnsrr($domain, 'MX') || ! checkdnsrr($domain, 'A')) {
		//	throw new \InvalidArgumentException(
		//		'Email address domain not found'
		//	);
		//}
		return $originalValue;
	}
	
	public function editOutput($savedValue)
	{
		return $savedValue;
	}
	
}
