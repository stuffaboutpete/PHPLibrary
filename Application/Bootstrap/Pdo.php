<?php

namespace PO\Application\Bootstrap;

use PO\Application\IBootstrap;
use PO\IoCContainer;

class Pdo
implements IBootstrap
{
	
	private $dbName;
	private $databaseType;
	private $host;
	private $username;
	private $password;
	
	public function __construct(
		$dbName						= null,
		$databaseType				= 'mysql',
		$host						= null,
		$username					= null,
		$password					= null
	)
	{
		
		// Save provided settings
		$this->host						= $host;
		$this->databaseType				= $databaseType;
		$this->dbName					= $dbName;
		$this->username					= $username;
		$this->password					= $password;
		
	}
	
	public function run(IoCContainer $ioCContainer)
	{
		
		// Setup some default values
		$defaults = [
			'name'		=> null,
			'host'		=> 'localhost',
			'username'	=> 'root',
			'password'	=> 'password'
		];
		
		try {
			$config = $ioCContainer->resolve('PO\Config');
		} catch (\RuntimeException $exception) {
			// There must be no singleton config
			// registered as the IoCContainer has
			// tried to create one
		}
		
		// If we have a config object, overwrite
		// defaults with values from the config
		if (isset($config)) {
			try {
				$config->get('db');
				$defaults = array_merge($defaults, $config->get('db'));
			} catch (\OutOfBoundsException $exception) {
				// This is fine, there simply
				// isn't db data in the config
				// file. We'll continue.
			}
		}
		
		// As the database name is the only value
		// we cannot provide a default for, drop
		// out here if we did not get one from
		// the constructor or config
		if (!$this->dbName && !$defaults['name']) return;
		
		// Use values from the constructor
		// if they were provided
		$dbName	  = ($this->dbName)	  ? $this->dbName	: $defaults['name'];
		$username = ($this->username) ? $this->username : $defaults['username'];
		$password = ($this->password) ? $this->password : $defaults['password'];
		
		// @todo Type checking - specifically database type
		
		// Register a new PDO object as a
		// singleton against the IoC container
		$ioCContainer->registerSingleton(new \PDO(
			"$this->databaseType:host=$this->host;dbname=$dbName",
			$username,
			$password
		));
		
	}
	
}
