<?php

namespace lx;

/**
 * Запрашиваемый ресурс может представлять собой либо плагин, либо экшен:
 * 1. Плагин - должен быть возвращен результат рендеригна плагина. Объект знает имя сервиса и имя плагина
 *	$this->data == ['service' => 'serviceName', 'plugin' => 'pluginName']
 * 2. Экшен - должен быть возвращен результат выполнения метода.
 *  Объект может знать имя сервиса, знает имя класса и метода
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 *
 * Class SourceContext
 * @package lx
 */
class SourceContext extends Object
{
	use ApplicationToolTrait;

	const RESTRICTION_FORBIDDEN_FOR_ALL = 5;
	const RESTRICTION_INSUFFICIENT_RIGHTS = 10;

	private $data;
	private $plugin;
	private $restrictions;

	/**
	 * SourceContext constructor.
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->setData($data);
		$this->restrictions = [];
	}

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param $restriction
	 */
	public function addRestriction($restriction)
	{
		$this->restrictions[] = $restriction;
	}

	/**
	 * @return bool
	 */
	public function hasRestriction()
	{
		return !empty($this->restrictions);
	}

	/**
	 * @return mixed
	 */
	public function getRestriction()
	{
		return $this->restrictions[0];
	}

	/**
	 * @return string
	 */
	public function getSourceName()
	{
		if ($this->isPlugin()) {
			return $this->data['service'] . ':' . $this->data['plugin'];
		}

		if (isset($this->data['class'])) {
			return $this->data['class'] . '::' . $this->data['method'];
		}

		if (isset($this->data['object'])) {
			$class = get_class($this->data['object']);
			return $class . '::' . $this->data['method'];
		}

		return '';
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return bool|mixed
	 */
	public function invoke($methodName = null, $params = null)
	{
		if ($this->isAction()) {
			return $this->invokeAction($methodName, $params);
		}

		if ($this->isPlugin()) {
			return $this->invokePlugin($methodName, $params);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isAction()
	{
		$data = $this->data;
		return (
			(isset($data['class']) || isset($data['object']))
			&&
			isset($data['method'])
		);
	}

	/**
	 * @return bool
	 */
	public function isPlugin()
	{
		return isset($this->data['plugin']);
	}

	/**
	 * @return Service|null
	 */
	public function getService()
	{
		if (isset($this->data['service'])) {
			return $this->app->getService($this->data['service']);
		}

		return null;
	}

	/**
	 * @return Plugin|null
	 */
	public function getPlugin()
	{
		if ( ! $this->isPlugin()) {
			return null;
		}

		if ( ! $this->plugin) {
			$plugin = $this->getService()->getPlugin($this->data['plugin']);

			if (isset($this->data['renderParams'])) {
				$plugin->addRenderParams($this->data['renderParams']);
			}

			if (isset($this->data['clientParams'])) {
				$plugin->clientParams->setProperties($this->data['clientParams']);
			}

			if (isset($this->data['dependencies'])) {
				$plugin->setDependencies($this->data['dependencies']);
			}

			$this->plugin = $plugin;
		}

		return $this->plugin;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return bool
	 */
	private function invokeAction($methodName, $params)
	{
		$object = $this->getObject();
		if (!$object) {
			return false;
		}

		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName || !method_exists($object, $methodName)) {
			return false;
		}

		$params = $params ?? $this->data['params'] ?? [];

		return $object->runAction($methodName, $params);
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return bool|mixed
	 */
	private function invokePlugin($methodName, $params)
	{
		$plugin = $this->getPlugin();
		if (!$plugin || !$methodName || !method_exists($plugin, $methodName)) {
			return false;
		}

		return $params
			? \call_user_func_array([$plugin, $methodName], $params)
			: $plugin->$methodName();
	}

	/**
	 * @return Source|null
	 */
	private function getObject()
	{
		if (isset($this->data['object'])) {
			return $this->data['object'];
		}

		if (isset($this->data['class'])) {
			$class = $this->data['class'];
			$config = [];
			if (isset($this->data['service'])) {
				$config['service'] = $this->getService();
			}

			$object = $this->app->diProcessor->create($class, $config);

			return $object;
		}

		return null;
	}
}
