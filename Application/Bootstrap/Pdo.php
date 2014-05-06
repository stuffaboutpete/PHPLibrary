<?php

namespace PO\Application\Bootstrap;

use PO\Application;
use PO\Application\IBootstrap;

class Pdo
implements IBootstrap
{
	
	private $dbName;
	private $applicationExtensionName;
	private $databaseType;
	private $host;
	private $username;
	private $password;
	
	public function __construct(
		$dbName						= null,
		$applicationExtensionName	= 'db',
		$databaseType				= 'mysql',
		$host						= null,
		$username					= null,
		$password					= null
	)
	{
		
		// Save provided settings
		$this->host						= $host;
		$this->applicationExtensionName	= $applicationExtensionName;
		$this->databaseType				= $databaseType;
		$this->dbName					= $dbName;
		$this->username					= $username;
		$this->password					= $password;
		
	}
	
	public function run(Application $application)
	{
		
		// Setup some default values
		$defaults = [
			'name'		=> null,
			'host'		=> 'localhost',
			'username'	=> 'root',
			'password'	=> 'password'
		];
		
		// If we have a config setup, overwrite
		// defaults with values from the config
		if ($application->hasExtension('config')) {
			$config = $application->getConfig();
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
		// we cannot provide a default for, throw
		// an exception if we did not get one from
		// the constructor or config
		if (!$this->dbName && !$defaults['name']) {
			throw new \RuntimeException(
				'A database name must be provided to either the ' .
				'constructor or via an application config file'
			);
		}
		
		// Use values from the constructor
		// if they were provided
		$dbName	  = ($this->dbName)	  ? $this->dbName	: $defaults['name'];
		$username = ($this->username) ? $this->username : $defaults['username'];
		$password = ($this->password) ? $this->password : $defaults['password'];
		
		// @todo Type checking - specifically database type
		
		// Create an application extension which
		// is a PDO object. By default this can be
		// accessed via $application->getDb()
		$application->extend(
			$this->applicationExtensionName,
			new \PDO(
				"$this->databaseType:host=$this->host;dbname=$dbName",
				$username,
				$password
			)
		);
		
	}
	
}
