<?php

namespace PO;

use PO\IoCContainer\IContainment;

/**
 * IoCContainer
 * 
 * Used to create objects whilst
 * programmatically providing any
 * dependencies the class may have
 */
class IoCContainer
{
	
	/**
	 * Holds any registered callbacks
	 * 
	 * These are used to create a
	 * object using a user provided
	 * manual function
	 * 
	 * @var array
	 */
	private $callbacks = [];
	
	/**
	 * Holds any singleton classes
	 * 
	 * These can be specified by the
	 * user to be used whenever an
	 * object of this type is needed
	 * 
	 * @var array
	 */
	private $singletons = [];
	
	/**
	 * Holds any interface associations
	 * 
	 * The user can specify a specific
	 * class to use when an interface
	 * is required as a dependency
	 * 
	 * @var array
	 */
	private $interfaces = [];
	
	/**
	 * Constructor
	 * 
	 * Accepts any number of instances of
	 * IContainment which are run whilst
	 * being provided with an instance of $this
	 * 
	 * @param  PO\IoCContainer\IContainment optional A containment to be run
	 * @param  PO\IoCContainer\IContainment optional [A containment to be run...]
	 * @return null
	 */
	public function __construct()
	{
		
		foreach (func_get_args() as $containment) {
			$this->addContainment($containment);
		}
		
		$this->registerSingleton($this);
		
	}
	
	/**
	 * Creates an object based on the provided alias
	 * 
	 * Alias can be a class name or a pre-registered
	 * callback reference. Any dependencies that a
	 * class requires will be provided whenever possible.
	 * 
	 * @param  string $alias                           A class/interface name or callback reference
	 * @param  mixed  $dependencies           optional Object dependencies or data for callback
	 * @param  mixed  $downstreamDependencies optional Further dependencies or data for callback
	 * @return mixed                                   An object or return value from a callback
	 * @throws InvalidArgumentException If dependencies or downstream dependencies are not an array
	 * @throws BadMethodCallException   If alias is an interface and method is called statically
	 * @throws InvalidArgumentException If alias is not a valid class or interface
	 * @throws RuntimeException         If a non typed dependency is not provided
	 * @todo   Should accept $thisCallOnlyDependencies in the same way as call()
	 */
	public function resolve($alias, $dependencies = [], $downstreamDependencies = [])
	{
		
		$isStatic = self::isStatic();
		
		// If the method is not being called
		// statically and the alias refers to
		// a registered callback, run that
		// callback and return its value
		if (!$isStatic && isset($this->callbacks[$alias])) {
			$arguments = func_get_args();
			array_shift($arguments);
			array_unshift($arguments, $this);
			return call_user_func_array($this->callbacks[$alias], $arguments);
		}
		
		// If the dependencies or the downstream
		// dependencies are not in an array,
		// throw an exception
		if (!is_array($dependencies)) {
			throw new \InvalidArgumentException('Dependencies must be an array');
		}
		if (!is_array($downstreamDependencies)) {
			throw new \InvalidArgumentException('Downstream dependencies must be an array');
		}
		
		// If the alias refers to an interface
		// but the method is called statically,
		// throw an exception
		if (interface_exists($alias) && $isStatic) {
			throw new \BadMethodCallException('Cannot resolve an interface statically');
		}
		
		// If the alias refers to an interface
		// and we have a class type of the interface
		// registered, change the alias to the
		// class type
		if (interface_exists($alias) && isset($this->interfaces[$alias])) {
			$alias = $this->interfaces[$alias];
		}
		
		// If the method is not being called
		// statically and the alias refers to
		// a registered singleton object,
		// return the singleton
		if (!$isStatic && isset($this->singletons[$alias])) {
			return $this->singletons[$alias];
		}
		
		// If the alias does not at this point
		// refer to a class, throw an exception
		if (!class_exists($alias)) {
			throw new \InvalidArgumentException("$alias is not a valid class");
		}
		
		// If the object has a constructor, we
		// need to build the list of arguments
		$arguments = method_exists($alias, '__construct')
			? self::getArguments(
				new \ReflectionMethod($alias, '__construct'),
				$isStatic,
				$dependencies,
				$downstreamDependencies
			) : [];
		
		// Create the object and return it
		$reflection = new \ReflectionClass($alias);
		return $reflection->newInstanceArgs($arguments);
		
	}
	
	public function call(
		$object,
		$method,
		array $dependencies = [],
		array $thisCallOnlyDependencies = [],
		array $downstreamDependencies = []
	)
	{
		
		if (!is_object($object)) {
			throw new \InvalidArgumentException('Provided argument is not an object');
		}
		
		if (!method_exists($object, $method)) {
			throw new \InvalidArgumentException('Provided method does not exist on object');
		}
		
		$arguments = self::getArguments(
			new \ReflectionMethod($object, $method),
			self::isStatic(),
			$dependencies,
			$downstreamDependencies,
			$thisCallOnlyDependencies
		);
		
		return call_user_func_array([$object, $method], $arguments);
		
	}
	
	private function getArguments(
		\ReflectionMethod $method,
		$isStatic,
		$dependencies,
		$downstreamDependencies,
		$thisCallOnlyDependencies = []
	)
	{
		
		// Hold the arguments to be passed to
		// the object we are about to create
		$arguments = [];
		
		// Get a list of arguments for
		// the constructor method and
		// loop through them
		$parameters = $method->getParameters();
		foreach ($parameters as $index => $parameter) {
			
			// If the user has provided an explicit
			// dependency, use that as the argument
			if (isset($dependencies[$index]) && !is_null($dependencies[$index])) {
				$arguments[] = $dependencies[$index];
				continue;
			}
			
			// Use reflection to get the
			// type of the argument
			$export = \ReflectionParameter::export(
				array(
					$parameter->getDeclaringClass()->name,
					$parameter->getDeclaringFunction()->name
				),
				$parameter->name,
				true
			);
			$export = explode(' ', $export);
			foreach ($export as $key => $part) {
				if ($part == "\$$parameter->name") {
					$type = $export[$key - 1];
					break;
				}
			}
			
			// If the type variable currently holds
			// <optional> we know that it is not
			// typed and is optional so we pass nothing
			if ($type == '<optional>') {
				$arguments[] = null;
				continue;
			}
			
			// If the type variable currently holds
			// <required> we know that it is not
			// typed but required so we throw an
			// exception as we do not have anything
			// to pass the constructor
			if ($type == '<required>') {
				throw new \RuntimeException(
					'A dependency must be provided for non-typed arguments'
				);
			}
			
			foreach ($thisCallOnlyDependencies as $dependency) {
				if (!is_object($dependency)) {
					throw new \InvalidArgumentException(
						'Dependencies for use in this call ony must be objects'
					);
				}
				if (!$isStatic && isset($this->singletons[get_class($dependency)])) {
					throw new \RuntimeException(
						'Dependency provided for this call only conflicts with a pre-registered singleton object'
					);
				}
				if (get_class($dependency) == $type) {
					$arguments[] = $dependency;
					continue 2;
				}
			}
			
			// If the argument type refers to a
			// singleton that we have registered,
			// set the argument to be that singleton
			if (!$isStatic && isset($this->singletons[$type])) {
				$arguments[] = $this->singletons[$type];
				continue;
			}
			
			// Build a string to be passed
			// 'call_user_func' based on whether
			// the method was called statically.
			// This will point at the current method.
			$resolveFunc = ($isStatic) ? get_class() . '::resolve' : [$this, 'resolve'];
			
			// Recursively call this method again
			// to resolve the argument type from
			// the IoC container and use the result
			// as the argument
			if (isset($downstreamDependencies[$type])) {
				$arguments[] = call_user_func(
					$resolveFunc,
					$type,
					$downstreamDependencies[$type],
					$downstreamDependencies
				);
			} else {
				$arguments[] = call_user_func($resolveFunc, $type, [], $downstreamDependencies);
			}
			
		}
		
		return $arguments;
		
	}
	
	/**
	 * Registers a callback against an alias
	 * 
	 * The callback will be executed when the
	 * alias is used in a call to the resolve
	 * method and it's return value will be
	 * passed to the caller
	 * 
	 * @param  string   $alias        The alias to be used when resolving the callback
	 * @param  callable $callback     The callback
	 * @return PO\IoCContainer    $this
	 * @throws BadMethodCallException If method is called statically
	 */
	public function registerCallback($alias, callable $callback)
	{
		
		// Ensure the method is not called statically
		if (!isset($this) || get_class($this) != __CLASS__) {
			throw new \BadMethodCallException('Cannot register a callback statically');
		}
		
		// Register the callback
		$this->callbacks[$alias] = $callback;
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Registers a singleton object
	 * 
	 * The object will always be used as a dependency
	 * when an object of its type is required
	 * 
	 * @param  object $object           The object to be used as a singleton
	 * @return PO\IoCContainer      $this
	 * @throws BadMethodCallException   If method is called statically
	 * @throws InvalidArgumentException If a non object is supplied
	 * @throws RuntimeException         If a singleton of this type has already been registered
	 */
	public function registerSingleton($object)
	{
		
		// Ensure the method is not called statically
		if (!isset($this) || get_class($this) != __CLASS__) {
			throw new \BadMethodCallException('Cannot register a singleton statically');
		}
		
		// Ensure the provided argument is an object
		if (!is_object($object)) throw new \InvalidArgumentException('Singleton must be an object');
		
		// Discover the class type
		$class = get_class($object);
		
		// Ensure that no singleton has been
		// registered for this class type
		if (isset($this->singletons[$class])) {
			throw new \RuntimeException('A singleton for this class has already been registered');
		}
		
		// Register the singleton
		$this->singletons[$class] = $object;
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Registers an interface implementation
	 * 
	 * The class type will be used as a dependency
	 * when the identified interface type is required
	 * 
	 * @param  string $interface The interface name
	 * @param  string $className The class name
	 * @return PO\IoCContainer $this
	 * @throws BadMethodCallException   If method is called statically
	 * @throws InvalidArgumentException If class does not implement interface
	 */
	public function registerInterface($interface, $className)
	{
		
		// Ensure the method is not called statically
		if (!isset($this) || get_class($this) != __CLASS__) {
			throw new \BadMethodCallException('Cannot register an interface statically');
		}
		
		// Get a reflection object for the given class
		$class = new \ReflectionClass($className);
		
		// Using reflection, ensure the class
		// implements the given interface
		if (!$class->implementsInterface($interface)) {
			throw new \InvalidArgumentException('Class must implement provided interface');
		}
		
		// Register the class
		$this->interfaces[$interface] = $className;
		
		// Allow chaining
		return $this;
		
	}
	
	/**
	 * Registers an instance of IContainment
	 * 
	 * The IContainment instance will be run immediately
	 * and supplied with this object. It is expected
	 * to call one of the register methods.
	 * 
	 * @param IContainment $containment A containment object
	 */
	public function addContainment(IContainment $containment)
	{
		$containment->register($this);
	}
	
	private function isStatic()
	{
		return !(isset($this) && get_class($this) == __CLASS__);
	}
	
}