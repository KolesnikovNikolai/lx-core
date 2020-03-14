<?php

namespace lx;

/**
 * Class DevApplicationLifeCycleManager
 * @package lx
 */
class DevApplicationLifeCycleManager
	extends BaseObject
	implements ApplicationLifeCycleManagerInterface, FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/**
	 * Method called as the first step in [[\lx::$app->run()]]
	 */
	public function beforeRun()
	{
		$compiler = new AssetCompiler();

		$compiler->compileLxCss();

		$coreCode = $compiler->compileJsCore();
		$file = new File(\lx::$conductor->lxAssets . '/main.js');
		$file->put($coreCode);
	}

	/**
	 * Method called as last step in [[\lx::$app->run()]]
	 */
	public function afterRun()
	{

	}

	/**
	 * Method called as the first step in [[\lx\PluginConductor::getCssAssets()]]
	 *
	 * @param Plugin $plugin
	 */
	public function beforeGetPluginCssAssets($plugin)
	{
		$css = $plugin->getConfig('css');
		if (!$css) {
			return;
		}

		$css = (array)$css;
		$cssCompiler = new AssetCompiler();
		foreach ($css as $value) {
			$path = $plugin->conductor->getFullPath($value);
			$cssCompiler->compileCssInDirectory($path);
		}
	}

	/**
	 * Method called as last step in:
	 * - [[\lx\Plugin::getScripts()]]
	 * - [[\lx\Plugin::getCss()]]
	 * - [[\lx\Plugin::getImagePathes()]]
	 *
	 * @param array $originalPathes
	 * @param array $linkPathes
	 */
	public function beforeReturnAutoLinkPathes($originalPathes, $linkPathes)
	{
		$compiler = new AssetCompiler();
		$compiler->createLinks($originalPathes, $linkPathes);
	}
}
