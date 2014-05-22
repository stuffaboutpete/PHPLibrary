<?php

namespace PO;

use PO\Helper\ArrayType;

class View
{
	
	private $template;
	private $templateVariables = [];
	
	public function __construct($template = null, array $templateVariables = null)
	{
		if (!is_null($template) && (!is_string($template) || $template == '')) {
			throw new View\Exception(
				View\Exception::TEMPLATE_PATH_NOT_STRING,
				'Type of $template: ' . gettype($template)
			);
		}
		if ($identifiedTemplate = $this->identifyTemplateFile($template)) {
			$this->template = $identifiedTemplate;
		} else {
			throw new View\Exception(
				View\Exception::TEMPLATE_FILE_COULD_NOT_BE_IDENTIFIED,
				'$template: ' . (is_null($template) ? 'null' : $template)
			);
		}
		if (is_array($templateVariables) && !ArrayType::isAssociative($templateVariables)) {
			throw new View\Exception(
				View\Exception::TEMPLATE_VARIABLES_NOT_ASSOCIATIVE_ARRAY,
				'Array: ' . implode(', ', $templateVariables)
			);
		}
		if (is_array($templateVariables)) $this->templateVariables = $templateVariables;
	}
	
	private function identifyTemplateFile($template)
	{
		
		if (file_exists($template)) return $template;
		if (file_exists($template . '.phtml')) return $template . '.phtml';
		
		if (!is_null($template)) {
			
			$trace = debug_backtrace();
			$callingFile = $trace[1]['file'];
			
			$relativePath = dirname($callingFile) . '/' . $template;
			
			if (file_exists($relativePath)) return $relativePath;
			if (file_exists($relativePath . '.phtml')) return $relativePath . '.phtml';
			
		}
		
		$calledClass = new \ReflectionClass(get_called_class());
		$calledClassParts = explode('\\', $calledClass->getName());
		$calledClassName = array_pop($calledClassParts);
		$calledClassFile = $calledClass->getFileName();
		
		if (!is_null($template)) {
			$templateFile = dirname($calledClassFile) . '/' . $template . '.phtml';
			if (file_exists($templateFile)) return $templateFile;
		}
		
		$templateFile = dirname($calledClassFile) . '/' . $calledClassName . '.phtml';
		return (file_exists($templateFile)) ? $templateFile : null;
		
	}
	
	protected function addTemplateVariable($key, $value)
	{
		$this->templateVariables[$key] = $value;
	}
	
	public function __toString()
	{
		
		extract($this->templateVariables, EXTR_SKIP);
		
		ob_start();
		include $this->template;
		$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
		
	}
	
}
