<?php

namespace PO\Application\Bootstrap;

use PO\Application\Bootstrap\MagicGateway\DependencyFactory;
use PO\Application\Bootstrap\MagicGateway\Exception;
use PO\Application\IBootstrap;
use PO\Gateway;
use PO\Gateway\Factory\Model\IBuildMapContributor;
use PO\Gateway\Factory\Model\IDismantleContributor;
use PO\IoCContainer;
use PO\Model;

class MagicGateway
implements IBootstrap
{
	
	private $gateway;
	private $dependencyFactory;
	private $searchDirectory;
	private $ioCContainer;
	
	public function __construct(
		DependencyFactory	$dependencyFactory,
		/* string */		$searchDirectory
	)
	{
		if (!is_string($searchDirectory)
		||	!file_exists($searchDirectory)
		||	!is_dir($searchDirectory)) {
			throw new Exception(
				Exception::INVALID_SEARCH_DIRECTORY,
				"Provided directory: $searchDirectory"
			);
		}
		$this->dependencyFactory = $dependencyFactory;
		$this->searchDirectory = $searchDirectory;
	}
	
	public function run(IoCContainer $ioCContainer)
	{
		
		$this->ioCContainer = $ioCContainer;
		
		$gateway = $ioCContainer->resolve('PO\Gateway');
		
		$ioCContainer->registerSingleton($gateway);
		
		foreach ($this->getAllModels() as $className) {
			$gateway->addType(
				$className,
				$this->getFactory($className),
				$this->getQueryProvider($className)
			);
		}
		
	}
	
	private function getAllModels()
	{
		$classNames = [];
		// @todo Should we inject SPL objects?
		foreach (new \DirectoryIterator($this->searchDirectory) as $file) {
			if ($file->isDot()) continue;
			$reflection = $this->getReflectionObjectFromFile($file);
			if ($reflection && $reflection->isSubclassOf(Model::Class)) {
				array_push($classNames, $reflection->getName());
			}
		}
		return $classNames;
	}
	
	private function getFactory($className)
	{
		$subDirectory = $this->getSubDirectoryFromClassName($className);
		if ($subDirectory !== null) {
			foreach (new \DirectoryIterator($subDirectory) as $file) {
				if ($file->isDot()) continue;
				$reflection = $this->getReflectionObjectFromFile($file);
				if (!$reflection->implementsInterface('PO\Gateway\IFactory')) continue;
				return $this->buildFromReflection($reflection);
			}
		}
		return $this->dependencyFactory->getModelFactory(
			$className,
			$this->getBuildMapContributors($className),
			$this->getDismantleContributors($className),
			$this->ioCContainer
		);
	}
	
	private function getQueryProvider($className)
	{
		$subDirectory = $this->getSubDirectoryFromClassName($className);
		if ($subDirectory !== null) {
			foreach (new \DirectoryIterator($subDirectory) as $file) {
				if ($file->isDot()) continue;
				$reflection = $this->getReflectionObjectFromFile($file);
				if (!$reflection->implementsInterface('PO\Gateway\IQueryProvider')) continue;
				return $this->buildFromReflection($reflection);
			}
		}
		return $this->dependencyFactory->getSimpleQueryProvider(
			$className,
			$this->getTableNameFromClassName($className)
		);
	}
	
	private function getBuildMapContributors($className)
	{
		$directory = $this->getSubDirectoryFromClassName($className);
		if ($directory === null) return null;
		$classNames = [];
		// @todo Should we inject SPL objects?
		foreach (new \DirectoryIterator($directory) as $file) {
			if ($file->isDot()) continue;
			$reflection = $this->getReflectionObjectFromFile($file);
			if ($reflection && $reflection->implementsInterface(IBuildMapContributor::Class)) {
				array_push($classNames, $this->buildFromReflection($reflection));
			}
		}
		return (count($classNames) > 0) ? $classNames : null;
	}
	
	private function getDismantleContributors($className)
	{
		$directory = $this->getSubDirectoryFromClassName($className);
		if ($directory === null) return null;
		$classNames = [];
		// @todo Should we inject SPL objects?
		foreach (new \DirectoryIterator($directory) as $file) {
			if ($file->isDot()) continue;
			$reflection = $this->getReflectionObjectFromFile($file);
			if ($reflection && $reflection->implementsInterface(IDismantleContributor::Class)) {
				array_push($classNames, $this->buildFromReflection($reflection));
			}
		}
		return (count($classNames) > 0) ? $classNames : null;
	}
	
	private function getTableNameFromClassName($className)
	{
		$nonNamespacedClassName = array_pop(explode('\\', $className));
		preg_match_all('/[A-Z][^A-Z]*/', $nonNamespacedClassName, $classNameWordsSearch);
		return strtolower(implode('_', $classNameWordsSearch[0]));
	}
	
	private function getReflectionObjectFromFile($file)
	{
		if ($file->getExtension() != 'php') return null;
		$fullClassName = $this->getFullClassNameFromFile($file->getPathname());
		include_once $file->getPathname();
		return new \ReflectionClass($fullClassName);
	}
	
	private function getFullClassNameFromFile($fileName)
	{
		$contents = file_get_contents($fileName);
		preg_match('/namespace ([A-Za-z\\\\]*);/', $contents, $namespaceSearch);
		preg_match('/class ([A-Za-z]*)/', $contents, $classNameSearch);
		return "$namespaceSearch[1]\\$classNameSearch[1]";
	}
	
	private function getSubDirectoryFromClassName($className)
	{
		$reflection = new \ReflectionClass($className);
		$directory = dirname($reflection->getFileName()) .
			'/' . array_pop(explode('\\', $className));
		if (!file_exists($directory) || !is_dir($directory)) return null;
		return $directory;
	}
	
	private function buildFromReflection($reflection)
	{
		return $this->ioCContainer->resolve($reflection->getName());
	}
	
}
