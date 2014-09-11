<?php

namespace PO;

use PO\Helper\ArrayType;

class View
{
	
	private $template;
	private $templateVariables = [];
	private $renderIntoView;
	private $renderIntoMethod;
	
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
	
	private function identifyTemplateFile($template, $targetClass = null)
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
		
		if ($targetClass === null) $targetClass = get_called_class();
		
		$reflection = new \ReflectionClass($targetClass);
		$classNameParts = explode('\\', $reflection->getName());
		$className = array_pop($classNameParts);
		$classFile = $reflection->getFileName();
		
		if (!is_null($template)) {
			$templateFile = dirname($classFile) . '/' . $template . '.phtml';
			if (file_exists($templateFile)) return $templateFile;
		}
		
		$templateFile = dirname($classFile) . '/' . $className . '.phtml';
		
		if (file_exists($templateFile)) return $templateFile;
		if ($parentReflection = $reflection->getParentClass()) {
			return $this->identifyTemplateFile($template, $parentReflection->getName());
		}
		
		return null;
		
	}
	
	protected function addTemplateVariable($key, $value)
	{
		$this->templateVariables[$key] = $value;
	}
	
	protected function useAncestorTemplate($className)
	{
		$className = trim($className, '\\');
		if (!class_exists($className)) {
			throw new View\Exception(
				View\Exception::ANCESTOR_CLASS_DOES_NOT_EXIST,
				"Class name: $className"
			);
		}
		$classIsAncestor = false;
		$inheritanceList = get_called_class();
		$reflection = new \ReflectionClass(get_called_class());
		while ($reflection = $reflection->getParentClass()) {
			if ($reflection->getName() == $className) {
				$classIsAncestor = true;
				break;
			}
			$inheritanceList .= '::' . $reflection->getName();
		};
		if (!$classIsAncestor) {
			throw new View\Exception(
				View\Exception::ANCESTOR_CLASS_NOT_ANCESTOR,
				"Inheritance list: $inheritanceList"
			);
		}
		if ($identifiedTemplate = $this->identifyTemplateFile(null, $className)) {
			$this->template = $identifiedTemplate;
		}
	}
	
	protected function renderInto(View $view, $method)
	{
		if (!method_exists($view, $method)) {
			throw new View\Exception(
				View\Exception::RENDER_INTO_METHOD_DOES_NOT_EXIST,
				'Class name: ' . get_class($view) . ", Method: $method"
			);
		}
		$this->renderIntoView = $view;
		$this->renderIntoMethod = $method;
	}
	
	public function __toString()
	{
		
		extract($this->templateVariables, EXTR_SKIP);
		
		ob_start();
		include $this->template;
		$output = ob_get_contents();
		ob_end_clean();
		
		if (isset($this->renderIntoView)) {
			$method = $this->renderIntoMethod;
			$this->renderIntoView->$method($output);
			return $this->renderIntoView->__toString();
		}
		
		return $output;
		
	}
	
}
