<?php

namespace PO\View;

class Debug
extends \PO\View
{
	
	public function __construct(\Exception $exception, $responseCode)
	{
		
		$this->addTemplateVariable('responseCode', $responseCode);
		$this->addTemplateVariable('message', htmlentities($exception->getMessage()));
		if (method_exists($exception, 'getSubMessage')) {
			$this->addTemplateVariable('subMessage', htmlentities($exception->getSubMessage()));
		}
		$this->addTemplateVariable('type', get_class($exception));
		$this->addTemplateVariable('code', $exception->getCode());
		$this->addTemplateVariable('class', $exception->getTrace()[0]['class']);
		$this->addTemplateVariable('fileName', $exception->getFile());
		$this->addTemplateVariable('lineNumber', $exception->getLine());
		if (!($exception instanceof \ErrorException && count($exception->getTrace()) == 1)) {
			$this->addTemplateVariable(
				'stackTrace',
				$this->formatStackTrace($exception->getTrace())
			);
		}
		$this->addTemplateVariable('isError', $exception instanceof \ErrorException);
		
		parent::__construct();
		
	}
	
	private function formatStackTrace($stackTrace)
	{
		$simpleStackTrace = [];
		foreach ($stackTrace as $index => $call) {
			if (!isset($call['class']) && isset($call['function'])) {
				$reflectionFunction = new \ReflectionFunction($call['function']);
				$reflectionArguments = $reflectionFunction->getParameters();
				$arguments = [];
				for ($i = 0; $i < count($call['args']); $i++) {
					if (isset($reflectionArguments[$i])
					&&	method_exists($reflectionArguments[$i], 'getName')
					&&	$reflectionArguments[$i]->getName() != '...') {
						array_push($arguments, [
							'name'		=> $reflectionArguments[$i]->getName(),
							'optional'	=> $reflectionArguments[$i]->isOptional(),
							'type'		=> $this->getArgumentTypeString($call['args'][$i])
						]);
					} else {
						array_push($arguments, [
							'name'		=> '(unnamed)',
							'optional'	=> true,
							'type'		=> $this->getArgumentTypeString($call['args'][$i])
						]);
					}
				}
				$fileParts = explode('/', $call['file']);
				array_push($simpleStackTrace, [
					'fileName'		=> array_pop($fileParts),
					'filePath'		=> $call['file'],
					'lineNumber'	=> $call['line'],
					'call'			=> "\\{$call['function']}()",
					'arguments'		=> $arguments
				]);
			} else {
				if (method_exists($call['class'], $call['function'])) {
					$reflectionMethod = new \ReflectionMethod($call['class'], $call['function']);
				} else if (method_exists($call['class'], '__call')) {
					$reflectionMethod = new \ReflectionMethod($call['class'], '__call');
				}
				$reflectionArguments = $reflectionMethod->getParameters();
				$arguments = [];
				for ($i = 0; $i < count($call['args']); $i++) {
					array_push($arguments, [
						'name'		=> $reflectionArguments[$i]->getName(),
						'optional'	=> $reflectionArguments[$i]->isOptional(),
						'type'		=> $this->getArgumentTypeString($call['args'][$i])
					]);
				}
				if (isset($call['file'])) {
					$fileParts = explode('/', $call['file']);
					$fileName = array_pop($fileParts);
					$filePath = $call['file'];
				} else {
					$fileName = '\\' . $stackTrace[$index + 1]['function'];
					$filePath = null;
				}
				array_push($simpleStackTrace, [
					'fileName'		=> $fileName,
					'filePath'		=> $filePath,
					'lineNumber'	=> isset($call['line']) ? $call['line'] : null,
					'call'			=> "{$call['class']}{$call['type']}{$call['function']}()",
					'arguments'		=> $arguments
				]);
			}
		}
		return $simpleStackTrace;
	}
	
	private function getArgumentTypeString($argument)
	{
		if (is_bool($argument)) {
			return 'Boolean: ' . ($argument ? 'true' : 'false');
		} else if (is_int($argument)) {
			return "Integer: $argument";
		} else if (is_float($argument)) {
			return "Float: $argument";
		} else if (is_string($argument)) {
			return 'String: \'' . htmlentities($argument) . '\'';
		} else if (is_array($argument)) {
			$entries = [];
			foreach ($argument as $key => $entry) {
				$entries[$key] = $this->getArgumentTypeString($entry);
			}
			array_walk($entries, function(&$value, $key){
				$value = "'$key' => $value";
			});
			return 'Array: [' . implode(', ', $entries) . ']';
		} else if (is_null($argument)) {
			return 'null';
		} else if (is_object($argument)) {
			return 'Object: ' . get_class($argument);
		}
	}
	
}
