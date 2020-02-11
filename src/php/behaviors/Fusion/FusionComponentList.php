<?php

namespace lx;

class FusionComponentList {
	private $fusion;
	private $config = [];
	private $list = [];

	public function __construct($fusion)
	{
		$this->fusion = $fusion;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->list)) {
			return $this->list[$name];
		}

		if (array_key_exists($name, $this->config)) {
			$data = $this->config[$name];
			unset($this->config[$name]);
			$params = $data['params'];
			$params['__fusion__'] = $this->fusion;
			$this->list[$name] = new $data['class']($params);
			return $this->list[$name];
		}

		return null;
	}

	public function has($name) {
		return array_key_exists($name, $this->list) || array_key_exists($name, $this->config);
	}

	public function load($list, $defaults = []) {
		$fullList = $list + $defaults;
		foreach ($fullList as $name => $config) {
			if (!$config) {
				continue;
			}

			$data = ClassHelper::prepareConfig($config);
			if ( ! $data) {
				throw new \Exception("Component $name not found", 400);
			}

			$this->config[$name] = $data;
		}
	}
}
