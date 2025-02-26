<?php

namespace lx;

class SpecialAjaxRouter
{
	/**
	 * Method defines if request is special AJAX
	 */
	public static function checkDialog(): bool
	{
		$dialog = \lx::$app->dialog;
		return (
			$dialog->isAjax() && $dialog->getHeader('lx-type')
		);
	}

	public function route(): ?ResourceContext
	{
		switch (\lx::$app->dialog->getHeader('lx-type')) {
			case 'service': return $this->serviceAjaxResponse();
			case 'plugin': return $this->pluginAjaxResponse();
			case 'module': return $this->moduleAjaxResponse();
		}
		return null;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function serviceAjaxResponse(): ResourceContext
	{
		$type = \lx::$app->dialog->getHeader('lx-service');

		// AJAX-request for required modules
		if ($type == 'get-modules') {
			$data = \lx::$app->dialog->getParams();
			return new ResourceContext([
				'class' => JsModuleProvider::class,
				'method' => 'getModulesResponse',
				'params' => [$data],
			]);
		}
	}

	private function pluginAjaxResponse(): ?ResourceContext
	{
		$meta = \lx::$app->dialog->getHeader('lx-plugin');
		if ($meta === null) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => 'Plugin-ajax-request without plugin!',
			]);
			return null;
		}

		$arr = explode(' ', $meta);
		$pluginName = $arr[0];
		$plugin = \lx::$app->getPlugin($pluginName);
		if ($plugin === null) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Plugin '$pluginName' not found",
			]);
			return null;
		}

		$respondentName = $arr[1] ?? null;
		return $plugin->getResourceContext($respondentName, \lx::$app->dialog->getParams());
	}

	private function moduleAjaxResponse(): ?ResourceContext
	{
		$meta = \lx::$app->dialog->getHeader('lx-module');

		$arr = explode(':', $meta);
		$data = (new JsModuleMap())->getModuleData($arr[0]);
		$moduleName = $data['backend'] ?? '';

		if (!ClassHelper::exists($moduleName)) {
			return null;
		}

		$ref = new \ReflectionClass($moduleName);
		if (!$ref->isSubclassOf(Module::class)) {
			return null;
		}

		$methodName = $arr[1];
		$params = \lx::$app->dialog->getParams();

		return new ResourceContext([
			'class' => $moduleName,
			'method' => $methodName,
			'params' => $params,
		]);
	}
}
