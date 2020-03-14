<?php

namespace lx;

/**
 * Class DependencyProcessor
 * @package lx
 */
class DependencyProcessor extends BaseObject implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var array */
	protected $interfaces = [];

	/** @var array */
	protected $classes = [];

	/**
	 * DependencyProcessor constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		// Validation creates dev-log messages if classes don't due to interfaces.
		// This collisions must be solved during development.
		if ($this->app->isNotProd()) {
			$this->validate();
		}

		// Default settings
		if (!array_key_exists(DataFileInterface::class, $this->interfaces)) {
			$this->interfaces[DataFileInterface::class] = DataFile::class;
		}
	}

	/**
	 * @param string $className
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	public function create($className, $params = [], $dependencies = [])
	{
		$re = new \ReflectionClass($className);
		if ($re->isSubclassOf(BaseObject::class)) {
			return $this->createObject($re, $params, $dependencies);
		}

		return $this->createProcess($re, $params, $dependencies);
	}

	/**
	 * @param string $interface
	 * @param array $params
	 * @param array $dependencies
	 * @param string $defaultClass
	 * @return mixed
	 */
	public function createByInterface($interface, $params = [], $dependencies = [], $defaultClass = null)
	{
		$className = $this->getClassNameByInterface($interface, $defaultClass);
		if (!$className) {
			return null;
		}

		return $this->create($className, $params, $dependencies);
	}

	/**
	 * @param string $interface
	 * @param string $defaultClass
	 * @return string
	 */
	public function getClassNameByInterface($interface, $defaultClass = null)
	{
		return $this->interfaces[$interface] ?? $defaultClass;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param \ReflectionClass $re
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	private function createProcess($re, $params, $dependencies)
	{
		if (!$re->hasMethod('__construct')) {
			return $re->newInstance();
		}

		$constructor = $re->getMethod('__construct');

		if (!$constructor->isPublic()) {
			return null;
		}

		$parameters = $constructor->getParameters();
		if (empty($parameters)) {
			return $re->newInstance();
		}

		if (empty($params)) {
			$paramsAreCountable = false;
		} else {
			$paramsAreCountable = !is_string(array_keys($params)[0]);
		}

		$name = $re->getName();
		$finalParams = [];
		foreach ($parameters as $i => $parameter) {
			if ($paramsAreCountable) {
				if (array_key_exists($i, $params)) {
					$finalParams[$i] = $params[$i];
					continue;
				}
			} else {
				$name = $parameter->getName();
				if (array_key_exists($name, $params)) {
					$finalParams[$i] = $params[$name];
					continue;
				}
			}

			$type = $parameter->getType();
			if ($type === null) {
				$finalParams[$i] = $parameter->isDefaultValueAvailable()
					? $parameter->getDefaultValue()
					: null;
				continue;
			}

			$typeName = $type->getName();
			$typeRe = new \ReflectionClass($typeName);
			if ($typeRe->isInterface()) {
				if (array_key_exists($typeName, $dependencies)) {
					$instance = $this->create($dependencies[$typeName]);
				} else {
					$instance = $this->createInstanceByInterface($typeName, $name);
				}
			} else {
				$instance = $this->create($typeName);
			}

			$finalParams[$i] = $instance;
		}

		$object = $re->newInstanceArgs($finalParams);
		return $object;
	}

	/**
	 * @param \ReflectionClass $re
	 * @param array $params
	 * @param array $dependencies
	 * @return mixed
	 */
	private function createObject(\ReflectionClass $reflection, $params, $dependencies)
	{
		$config = $params;
		$name = $reflection->getName();
		$protocol = $reflection->getMethod('getConfigProtocol')->invoke(null);
		$diMap = $reflection->getMethod('diMap')->invoke(null);

		foreach ($protocol as $paramName => $paramDiscr) {
			if (array_key_exists($paramName, $config)) {
				continue;
			}

			$instanceName = is_string($paramDiscr)
				? $paramDiscr
				: ($paramDiscr['instance'] ?? null);
			if (!$instanceName) {
				continue;
			}

			$paramRe = new \ReflectionClass($instanceName);
			if ($paramRe->isInterface()) {
				if (array_key_exists($instanceName, $dependencies)) {
					$instance = $this->create($dependencies[$instanceName]);
				} elseif (array_key_exists($paramName, $dependencies)) {
					$instance = $this->create($dependencies[$paramName]);
				} elseif ($this->hasDefinitionForClass($name, $instanceName)) {
					$instance = $this->createInstanceByInterface($instanceName, $name);
				} elseif (array_key_exists($instanceName, $diMap)) {
					$instance = $this->create($diMap[$instanceName]);
				} else {
					$instance = $this->createInstanceByInterface($instanceName, $name);
				}
			} else {
				$instance = $this->create($instanceName);
			}

			if (!$instance) {
				continue;
			}

			$config[$paramName] = $instance;
		}

		return $reflection->newInstance($config);
	}

	/**
	 * @param string $className
	 * @param string $interfaceName
	 * @return bool
	 */
	private function hasDefinitionForClass($className, $interfaceName)
	{
		if (!array_key_exists($className, $this->classes)) {
			return false;
		}

		if (!array_key_exists($interfaceName, $this->classes[$className])) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $interfaceName
	 * @param string|null $className
	 * @return mixed|null
	 */
	private function createInstanceByInterface($interfaceName, $className = null)
	{
		$classForInterface = null;
		if ($className !== null && array_key_exists($className, $this->classes)) {
			$classData = $this->classes[$className];
			if (array_key_exists($interfaceName, $classData)) {
				$classForInterface = $classData[$interfaceName];
			}
		}

		if (!$classForInterface) {
			if (array_key_exists($interfaceName, $this->interfaces)) {
				$classForInterface = $this->interfaces[$interfaceName];
			}
		}

		if (!$classForInterface) {
			return null;
		}

		return $this->create($classForInterface);
	}

	/**
	 * Validation creates dev-log messages if classes don't due to interfaces
	 */
	private function validate()
	{
		foreach ($this->interfaces as $interface => $class) {
			$re = new \ReflectionClass($class);
			if (!$re->implementsInterface($interface)) {
				\lx::devLog([
					'msg' => 'DI-processor notification! Configuration is wrong: class doesn\'t due to interface',
					'interface' => $interface,
					'class' => $class,
				]);
			}
		}

		foreach ($this->classes as $mainClass => $data) {
			foreach ($data as $interface => $class) {
				$re = new \ReflectionClass($class);
				if (!$re->implementsInterface($interface)) {
					\lx::devLog([
						'msg' => 'DI-processor notification! Configuration is wrong: class doesn\'t due to interface',
						'context' => $mainClass,
						'interface' => $interface,
						'class' => $class,
					]);
				}
			}
		}
	}
}
