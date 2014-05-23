<?php

namespace PO\Application\ExceptionHandler;

use PO\Application\IExceptionHandler;
use PO\Http\Response;

class JsonDebug
implements IExceptionHandler
{
	
	public function __construct()
	{
		ini_set('display_errors', false);
	}
	
	public function handleException(\Exception $exception, Response $response, $responseCode = 500)
	{
		
		$message = $exception->getMessage();
		if (method_exists($exception, 'getSubMessage')) $message .= $exception->getSubMessage();
		
		$output = [
			'error'		=> true,
			'message'	=> $message,
			'exception'	=> [
				'type'				=> get_class($exception),
				'code'				=> $exception->getCode(),
				'thrown_from_file'	=> $exception->getFile(),
				'thrown_from_line'	=> $exception->getLine()
			]
		];
		
		if (!($exception instanceof \ErrorException && count($exception->getTrace()) == 1)) {
			$output['exception']['trace'] = $this->formatStackTrace($exception->getTrace());
		}
		
		$method = 'set' . $responseCode;
		$response->$method($output);
		
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
							'name'		=> '$' . $reflectionArguments[$i]->getName(),
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
					'file'		=> $call['file'],
					'line'		=> $call['line'],
					'call'		=> "\\{$call['function']}()",
					'arguments'	=> $arguments
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
						'name'		=> '$' . $reflectionArguments[$i]->getName(),
						'optional'	=> $reflectionArguments[$i]->isOptional(),
						'type'		=> $this->getArgumentTypeString($call['args'][$i])
					]);
				}
				if (isset($call['file'])) {
					$file = $call['file'];
				} else {
					$file = '\\' . $stackTrace[$index + 1]['function'];
				}
				array_push($simpleStackTrace, [
					'file'		=> $file,
					'line'		=> isset($call['line']) ? $call['line'] : null,
					'call'		=> "{$call['class']}{$call['type']}{$call['function']}()",
					'arguments'	=> $arguments
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
