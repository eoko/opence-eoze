<?php

namespace eoko\modules\IconProvider;

use eoko\module\Module;
use eoko\url\Maker as UrlMaker;
use eoko\util\Files;

//class IconProvider extends Module implements HtmlIncludesListener {
class IconProvider extends Module {

	protected $defaultExecutor = 'css';

	public static function registerEvents() {

		$module = $this->getConfig()->get('HTMLPageGeneratorModule');
		ModuleManager::getModule($module)->addListener('', $this);
	}

	public function onHtmlIncludes(HTMLPageGenerator $module) {
		$module->pushCss(UrlMaker::getFor($this, 'getIconCss'));
	}

	public static function makeClass($file) {
		return preg_replace(
			'/[^a-zA-Z\d-_]/', 
			'', 
			preg_match('/^(.+)\.\w+$/', $file, $m) ? $m[1] : $file
		);
	}

	public function listFiles() {
		$path = $this->findPath(
			$this->getConfig()->get('iconFolder')
		);
		return Files::listFiles($path);
	}

	public function getIconCssUrl() {
		return UrlMaker::getFor("$this.css", 'getIconCss');
	}

}
