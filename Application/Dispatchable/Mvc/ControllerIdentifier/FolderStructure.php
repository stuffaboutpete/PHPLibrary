<?php

namespace Suburb\Application\Dispatchable\Mvc\ControllerIdentifier;

use Suburb\Application\Dispatchable\Mvc\IControllerIdentifier;
use Suburb\Application\Dispatchable\Mvc\ControllerIdentifier\FolderStructure\Exception;

class FolderStructure
implements IControllerIdentifier
{
	
	private $pathParts;
	private $namespace;
	private $templatesDirectory;
	private $pathVariables;
	private $getVariables;
	
	public function __construct($controllersNamespace = null, $templatesDirectory = null)
	{
		if (isset($templatesDirectory)) {
			if (!file_exists($templatesDirectory) || !is_dir($templatesDirectory)) {
				throw new Exception(
					Exception::TEMPLATES_DIRECTORY_DOES_NOT_EXIST,
					$templatesDirectory
				);
			}
		}
		$this->namespace = $controllersNamespace;
		$this->templatesDirectory = $templatesDirectory;
	}
	
	public function receivePath($path)
	{
		$path = explode('?', $path);
		if (isset($path[1])) parse_str($path[1], $this->getVariables);
		$pathParts = explode('/', $path[0]);
		array_shift($pathParts);
		$this->pathParts = $pathParts;
	}
	
	public function getControllerClass()
	{
		$this->checkInited();
		$data = $this->lookForResource(
			$this->namespace,
			'',
			'\\',
			'class_exists',
			Exception::AMBIGUOUS_CONTROLLER_CLASS
		);
		if (is_null($data)) return null;
		$this->pathVariables = $data['pathVariables'];
		return $data['target'];
	}
	
	public function getTemplatePath()
	{
		if (!$this->templatesDirectory) return false;
		$this->checkInited();
		$data = $this->lookForResource(
			$this->templatesDirectory,
			'.phtml',
			'/',
			'file_exists',
			Exception::AMBIGUOUS_TEMPLATE_FILE
		);
		if (is_null($data)) return null;
		return $data['target'];
	}
	
	public function getPathVariables()
	{
		$this->checkInited();
		if (!isset($this->pathVariables)) $this->getControllerClass();
		if (isset($this->getVariables)) {
			return array_merge($this->pathVariables, $this->getVariables);
		} else {
			return $this->pathVariables;
		}
	}
	
	private function lookForResource(
		$prefix,
		$suffix,
		$delimiter,
		$existenceFunction,
		$ambiguityException
	)
	{
		
		$pathVariables = [[], []];
		
		$pathParts = $this->pathParts;
		
		array_walk($pathParts, function(&$part){
			$part = ucfirst($part);
		});
		
		do {
			$target = (isset($prefix)) ? $prefix . $delimiter : $delimiter;
			$target .= implode($delimiter, $pathParts) . $suffix;
			array_push($pathVariables[0], array_pop($pathParts));
		} while (!$existenceFunction($target) && count($pathParts) > 0);
		
		array_pop($pathVariables[0]);
		
		$pathParts = $this->pathParts;
		
		array_walk($pathParts, function(&$part){
			$part = ucfirst($part);
		});
		
		do {
			$indexTarget = (isset($prefix)) ? $prefix . $delimiter : $delimiter;
			$indexTarget .= implode($delimiter, $pathParts);
			$indexTarget .= ($indexTarget == $prefix . $delimiter) ? 'Index' : $delimiter . 'Index';
			$indexTarget .= $suffix;
			array_push($pathVariables[1], array_pop($pathParts));
		} while (!$existenceFunction($indexTarget) && count($pathParts) > 0);
		
		array_pop($pathVariables[1]);
		
		if ($existenceFunction($target) && $existenceFunction($indexTarget)) {
			throw new Exception($ambiguityException, "$target and $indexTarget both exist");
		}
		
		if (!$existenceFunction($target) && !$existenceFunction($indexTarget)) return null;
		
		$target = ($existenceFunction($target)) ? $target : $indexTarget;
		$pathVariables = $pathVariables[($existenceFunction($target)) ? 0 : 1];
		
		if (count($pathVariables) % 2 != 0) return null;
		
		$associativePathVariables = [];
		
		for ($i = 0; $i < count($pathVariables); $i = $i + 2) {
			$key = $this->pathParts[count($this->pathParts) - $i - 2];
			$value = $this->pathParts[count($this->pathParts) - $i - 1];
			$associativePathVariables[$key] = $value;
		}
		
		return [
			'target'		=> $target,
			'pathVariables'	=> $associativePathVariables
		];
		
	}
	
	private function checkInited()
	{
		if (!isset($this->pathParts)) {
			throw new Exception(Exception::GET_METHOD_CALLED_BEFORE_RECEIVE_PATH);
		}
	}
	
}