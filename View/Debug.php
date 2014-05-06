<?php

namespace PO\View;

class Debug
extends \PO\View
{
	
	public function __construct(\Exception $exception = null, $error = null, $responseCode = 500)
	{
		
		$templateVariables = [];
		
		$templateVariables['responseCode'] = $responseCode;
		
		if (isset($exception)) {
			
			$templateVariables['errorType'] = 'exception';
			$templateVariables['message'] = htmlentities($exception->getMessage());
			if (method_exists($exception, 'getSubMessage')) {
				$templateVariables['subMessage'] = htmlentities($exception->getSubMessage());
			}
			$templateVariables['type'] = get_class($exception);
			$templateVariables['code'] = $exception->getCode();
			$templateVariables['class'] = $exception->getTrace()[0]['class'];
			$templateVariables['fileName'] = $exception->getFile();
			$templateVariables['lineNumber'] = $exception->getLine();
			$templateVariables['stackTrace'] = $this->formatStackTrace($exception->getTrace());
			
		} else {
			
			$templateVariables['errorType'] = 'error';
			$templateVariables['message'] = htmlentities($error['message']);
			$templateVariables['type'] = $this->getErrorType($error['type']);
			$templateVariables['code'] = $error['type'];
			$templateVariables['fileName'] = $error['file'];
			$templateVariables['lineNumber'] = $error['line'];
			
		}
		
		foreach ($templateVariables as $key => $value) $this->addTemplateVariable($key, $value);
		
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
					if (method_exists($reflectionArguments[$i], 'getName')
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
				array_push($simpleStackTrace, [
					'fileName'		=> array_pop(explode('/', $call['file'])),
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
					$fileName = array_pop(explode('/', $call['file']));
					$filePath = $call['file'];
				} else {
					$fileName = '\\' . $stackTrace[$index + 1]['function'];
					$filePath = null;
				}
				array_push($simpleStackTrace, [
					'fileName'		=> $fileName,
					'filePath'		=> $filePath,
					'lineNumber'	=> $call['line'],
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
	
	private function getErrorType($code)
	{
		$errors = [
			'E_ERROR'				=> E_ERROR,
			'E_WARNING'				=> E_WARNING,
			'E_PARSE'				=> E_PARSE,
			'E_COMPILE_ERROR'		=> E_COMPILE_ERROR,
			'E_RECOVERABLE_ERROR'	=> E_RECOVERABLE_ERROR,
			'E_USER_ERROR'			=> E_USER_ERROR,
			'E_USER_WARNING'		=> E_USER_WARNING
		];
		foreach ($errors as $key => $value) {
			if ($value == $code) return $key;
		}
		return 'Unknown';
	}
	
}
