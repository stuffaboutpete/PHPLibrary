<?php

namespace PO\View;

use PO\View;
use PO\Helper\ArrayType as ArrayHelper;
use PO\Helper\StringType as StringHelper;

class Form
extends View
{
	
	const TEXT				= 1;
	const SUBMIT			= 2;
	const TEXTAREA			= 3;
	const SELECT			= 4;
	const URL				= 5;
	const TEL				= 6;
	const EMAIL				= 7;
	const SEARCH			= 8;
	const NUMBER			= 9;
	const RANGE				= 10;
	const DATE_TIME_LOCAL	= 11;
	const DATE				= 12;
	const TIME				= 13;
	const WEEK				= 14;
	const MONTH				= 15;
	const COLOR				= 16;
	
	private $constants;
	
	public function __construct(array $elements, $method = 'post', $target = null)
	{
		if ($method !== 'post' && $method !== 'get') {
			throw new Form\Exception(
				Form\Exception::INVALID_METHOD_PROVIDED,
				is_string($method)
					? "Provided method: '$method'"
					: 'Provided argument type: ' . gettype($method)
			);
		}
		if (isset($target) && !is_string($target)) {
			throw new Form\Exception(
				Form\Exception::NON_STRING_PROVIDED_AS_TARGET,
				'Provided type: ' . gettype($target)
			);
		}
		if (count($elements) == 0) {
			throw new Form\Exception(Form\Exception::NO_ELEMENT_PROVIDED);
		}
		$this->addTemplateVariable('elements', $this->getElementsArray($elements));
		$this->addTemplateVariable('method', $method);
		$this->addTemplateVariable('target', $target);
		parent::__construct();
	}
	
	private function getElementsArray($elementsDefinition)
	{
		 // @todo Could do with some more data validation, like options are arrays,
		 // datalists are only associated with text fields etc
		$elements = [];
		$autofocusElements = 0;
		foreach ($elementsDefinition as $value) {
			$providedType = gettype($value);
			if (is_int($value)) $value = ['element' => $value];
			if (!isset($value['element'])) {
				throw new Form\Exception(
					Form\Exception::INVALID_ELEMENT_DEFINITION,
					'Provided type: ' . $providedType
				);
			}
			if (!$this->isClassConstantValue($value['element'])) {
				throw new Form\Exception(
					Form\Exception::INVALID_ELEMENT_KEY_PROVIDED,
					'custom message'
				);
			}
			$element = [
				'tag'			=> $this->getTag($value['element']),
				'selfClosing'	=> $this->isSelfClosing($value['element']),
				'attributes'	=> []
			];
			$type = $this->getType($value['element']);
			if ($type) $element['attributes']['type'] = [$type];
			foreach ($value as $subKey => $subValue) {
				switch ($subKey) {
					case 'element':
						continue;
					break;
					case 'content':
						if ($element['selfClosing']) {
							throw new Form\Exception(
								Form\Exception::CONTENT_PROVIDED_FOR_SELF_CLOSING_TAG,
								"Element type provided: $value[element]"
							);
						}
						if (!is_string($subValue)) {
							throw new Form\Exception(
								Form\Exception::INVALID_VALUE_IN_ELEMENT_DECLARATION,
								'Content expected to be a string'
							);
						}
						$element['content'] = $subValue;
					break;
					case 'options':
						$element['options'] = [];
						foreach ($subValue as $optionKey => $optionValue) {
							if (ArrayHelper::isAssociative($subValue)) {
								$element['options'][] = [
									'value'	=> $optionKey,
									'label'	=> $optionValue
								];
							} else {
								$element['options'][] = [
									'value'	=> null,
									'label'	=> $optionValue
								];
							}
						}
					break;
					case 'label':
						if (!is_string($subValue)) {
							throw new Form\Exception(
								Form\Exception::INVALID_VALUE_IN_ELEMENT_DECLARATION,
								'Label expected to be a string'
							);
						}
						if (!isset($value['id'])) {
							throw new Form\Exception(
								Form\Exception::MISSING_VALUE_IN_ELEMENT_DECLARATION,
								'Label requires that element \'id\' is set'
							);
						}
						$elements[] = [
							'tag'			=> 'label',
							'selfClosing'	=> false,
							'content'		=> $subValue,
							'attributes'	=> ['for' => [$value['id']]]
						];
					break;
					case 'datalist':
						if (!ArrayHelper::isAssociative($subValue)) {
							$subValue = [
								'id'		=> 'list-' . StringHelper::createRandom(6),
								'options'	=> $subValue
							];
						}
						$element['attributes']['list'] = [$subValue['id']];
						$options = [];
						foreach ($subValue['options'] as $option) {
							$options[] = ['value' => $option];
						}
						$elements[] = [
							'tag'			=> 'datalist',
							'selfClosing'	=> false,
							'attributes'	=> ['id' => [$subValue['id']]],
							'options'		=> $options
						];
					break;
					case 'autofocus':
						$autofocusElements++;
						if ($autofocusElements > 1) {
							throw new Form\Exception(Form\Exception::DUPLICATE_AUTOFOCUS);
						}
						$element['attributes']['autofocus'] = true;
					break;
					default:
						if (!isset($element['attributes'][$subKey])) {
							if ($subValue === true) {
								$element['attributes'][$subKey] = true;
							} elseif (is_array($subValue)) {
								$element['attributes'][$subKey] = $subValue;
							} else {
								$element['attributes'][$subKey] = [$subValue];
							}
						}
					break;
				}
			}
			foreach ($element['attributes'] as &$attributeArray) {
				if ($attributeArray === true) continue;
				$attributeArray = array_unique($attributeArray);
			}
			$elements[] = $element;
		}
		return $elements;
	}
	
	private function isClassConstantValue($value)
	{
		if (!isset($this->constants)) {
			$reflection = new \ReflectionClass(__CLASS__);
			$this->constants = $reflection->getConstants();
		}
		foreach ($this->constants as $name => $constValue) {
			if (defined(__CLASS__ . '::' . $name) && $value == $constValue) return true;
		}
		return false;
	}
	
	private function getTag($key)
	{
		switch ($key) {
			case self::TEXT:
			case self::SUBMIT:
			case self::URL:
			case self::TEL:
			case self::EMAIL:
			case self::SEARCH:
			case self::NUMBER:
			case self::RANGE:
			case self::DATE_TIME_LOCAL:
			case self::DATE:
			case self::TIME:
			case self::WEEK:
			case self::MONTH:
			case self::COLOR:
				return 'input';
			case self::TEXTAREA:
				return 'textarea';
			case self::SELECT:
				return 'select';
		}
	}
	
	private function isSelfClosing($key)
	{
		switch ($key) {
			case self::TEXT:
			case self::SUBMIT:
			case self::URL:
			case self::TEL:
			case self::EMAIL:
			case self::SEARCH:
			case self::NUMBER:
			case self::RANGE:
			case self::DATE_TIME_LOCAL:
			case self::DATE:
			case self::TIME:
			case self::WEEK:
			case self::MONTH:
			case self::COLOR:
				return true;
			case self::TEXTAREA:
			case self::SELECT:
				return false;
		}
	}
	
	private function getType($key)
	{
		switch ($key) {
			case self::TEXT:
				return 'text';
			case self::SUBMIT:
				return 'submit';
			case self::URL:
				return 'url';
			case self::TEL:
				return 'tel';
			case self::EMAIL:
				return 'email';
			case self::SEARCH:
				return 'search';
			case self::NUMBER:
				return 'number';
			case self::RANGE:
				return 'range';
			case self::DATE_TIME_LOCAL:
				return 'datetime-local';
			case self::DATE:
				return 'date';
			case self::TIME:
				return 'time';
			case self::WEEK:
				return 'week';
			case self::MONTH:
				return 'month';
			case self::COLOR:
				return 'color';
		}
	}
	
}
