<?php

namespace eoko\modules\IconProvider;

use eoko\module\executor\TemplateExecutor;
use eoko\template\Template;
use eoko\url\Maker as UrlMaker;

class Css extends TemplateExecutor {

	protected function processResult($result) {
//		if ($result instanceof Template) {
//			$result->render();
//		}
	}

	protected function createTemplate($name, $require = true, $opts = null) {
		return Template::create()
				->setFile($this->findPath('icons.css.php'));
	}

	public function getIconCss() {

		$module = $this->getModule();

		$icons = array();
		foreach ($module->listFiles() as $file) {

			$url = $this->module->getBasePath() . 'icons' . DS . $file;

			$icons[] = array(
				'class' => $module->makeClass($file),
//				'url' => 'icons' . DS . $file,
				'url' => UrlMaker::getFor($this, 'getIconImage', array('img' => $file)),
			);
		}

		$this->baseClass = $module->getConfig()->get('cssBaseClass');
		$this->icons = $icons;

		header('Content-type: text/css');
		$this->getTemplate()->render();
	}

	public function getIconImage() {
		$file = $this->request->req('img');
		$filename = $this->module->getBasePath() . 'icons' . DS . $file;
		$ext = preg_match('/\.([^.]+)$/', $file, $m) ? $m[1] : 'png';
		header("Content-type: image/$ext");
		$file = fopen($filename, 'r');
		$info = fstat($file);
		echo fread($file, $info['size']);
		fclose($file);
	}
}
