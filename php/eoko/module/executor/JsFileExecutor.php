<?php

namespace eoko\module\executor;

use eoko\file\FileType;
use eoko\util\Files;
use eoko\template\Template;

use \Logger;

class JsFileExecutor extends ExecutorBase {

	protected function processResult($result) {}

	public function get_js() {
		return $this->get_module();
	}

	protected function getDefaultModule($name) {
		return false;
	}

//	protected function prepareJsTemplate(Template $tpl) {}

	public function get_module() {
		$name = $this->request->req('name');

		Logger::dbg('js module is: {}', $name);

		if (!headers_sent()) {
			header('Content-type: application/javascript');
		}

		if (!($path = $this->searchPath($name, FileType::JS))) {
			if (($path = $this->searchPath($name, FileType::JS_TPL))) {
				require $path;
			} else if (false === $this->getDefaultModule($name)) {
				Logger::get($this)->error('Cannot find js file for: "{}"', $name);
				return $this->answer404();
			} else {
				return;
			}
		} else {
			$content = rtrim(file_get_contents($path));
			if (strstr($content, '<?php')) {
				$tpl = Template::create()->setContent($content);
				if (method_exists($this, $m = "prepare{$name}Template")) {
					$this->$m($tpl);
				}
				$tpl->render();
			} else {
				echo $content;
			}
		}

		foreach (array_merge(
			$this->module->listFiles("glob:{$name}_*.js", null, FileType::JS),
			$this->module->listFiles("glob:*.js", $name, FileType::JS)
		) as $plugin) {
			$cmtName = PHP_EOL . PHP_EOL . '// --- ' . basename($plugin) . ' ';
			echo $cmtName . str_repeat('-', 80 - strlen($cmtName)) . PHP_EOL;
//			require $plugin;
			$lines = file($plugin);
			foreach ($lines as $i => $line) {
				$line = rtrim($line);
				echo $line . str_repeat(' ', max(5, 80 - strlen($line))) . '// ' . ($i+1) . PHP_EOL;
			}
		}
	}
}
