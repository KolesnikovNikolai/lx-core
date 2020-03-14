<?php

namespace lx;

/**
 * Class ServiceController
 * @package lx
 * 
 * @property-read Service $service
 */
class ServiceController extends Source
{
	/** @var Service */
	private $_service;

	/**
	 * ServiceController constructor.
	 * @param array $config
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->_service = $config['service'];
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($name == 'service') {
			return $this->_service;
		}

		return parent::__get($name);
	}
	
	/**
	 * @return array
	 */
	public static function getConfigProtocol()
	{
		$protocol = parent::getConfigProtocol();
		$protocol['service'] = [
			'require' => true,
			'instance' => Service::class,
		];
		return $protocol;
	}
}
