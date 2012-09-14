<?php

namespace eoko\modules\root;

use eoko\module\executor\html\BasicHtmlExecutor;
use eoko\template\HtmlRootTemplate;
use eoko\template\HtmlTemplate;
use eoko\module\ModuleManager;
use eoko\util\Files;
use eoko\file\FileType;

use \UserSession;

class Html extends BasicHtmlExecutor {
	
	protected function onCreateLayout(HtmlRootTemplate $layout) {
		
		$layout->pushAlias(array(
			'@ext', '@oce'
		));

		$this->pushLayoutExtraJs($layout);

		$url = str_replace("'", "\\'", EOZE_BASE_URL . 'images/s.gif');
		$js = <<<JS
<script type="text/javascript">
	if (!window.Oce) window.Oce = { ext: {} };
	Oce.ext.BLANK_IMAGE_URL = '$url';
</script>
JS;
		$layout->head->set('beforeJs', $js, false);
		$extra = $layout->head->extra = $this->createTemplate('head_extra_script');
		
		if (null !== $env = $this->request->get('env')) {
			$extra->context = json_encode(array(
				'environment' => $env,
				'target' => $this->request->get('target'),
			));
		}

        $layout->setCompileOptions($this->getModuleConfig()->get('compilation', false));
		$layout->setApplicationConfig($this->getModule()->getApplicationConfig());
	}

	protected function pushLayoutExtraJs(HtmlRootTemplate $layout) {
		// Include js/*.auto[order].js and auto/*.js files
		$baseJsFiles = array();
		$autoJsFiles = array();
		
		$modulesJavascriptContents = array();
		
		$autoCssFiles = array();
		
		foreach (ModuleManager::listModules(false) as $module) {
			$module instanceof \eoko\module\Module;
			// js
			$autoJsFiles = array_merge($autoJsFiles, $module->listLineFilesUrl('re:\.auto\d*\.js$', ''));
			$autoJsFiles = array_merge($autoJsFiles, $module->listLineFilesUrl('re:\.auto\d*\.js$', 'js'));
			$autoJsFiles = array_merge($autoJsFiles, $module->listLineFilesUrl('glob:*.js', 'js/auto', true));
			$autoJsFiles = array_merge($autoJsFiles, $module->listLineFilesUrl('glob:*.js', 'js.auto', true));
			$baseJsFiles = array_merge($baseJsFiles, $module->listLineFilesUrl('glob:*.js', 'js.base', true));
			
			// module js file
			if ($module instanceof \eoko\module\HasJavascript) {
				$content = $module->getJavascriptAsString();
				if ($content) {
					$modulesJavascriptContents[] = str_pad('// --- ' . $module->getName() . ' ', 100, '-');
					$modulesJavascriptContents[] = 'try {';
					$modulesJavascriptContents[] = $content;
					$modulesJavascriptContents[] = '} catch (e) {';
					$modulesJavascriptContents[] = "	console.log('Error in module\'s javascript: {$module->getName()}');";
					$modulesJavascriptContents[] = '}';
				}
			}
			
			// css
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('re:\.auto\d*\.css$', ''));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('re:\.auto\d*\.css$', 'css'));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('glob:*.css', 'css/auto', true));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('glob:*.css', 'css.auto', true));
		}

		// merge modules javascript
		$modulesJsFileName = 'opence-modules.js';
		$modulesJsFilePath = WEB_DIR_PATH . $modulesJsFileName;
		$modulesJsFileUrl = WEB_DIR_URL . $modulesJsFileName;
		file_put_contents($modulesJsFilePath, implode(PHP_EOL . PHP_EOL, $modulesJavascriptContents));
		unset($modulesJavascriptContents);
		
		$urls = array();
		foreach($baseJsFiles as $url) {
			$urls[$url] = 10;
		}
		foreach ($autoJsFiles as $url) {
			$urls[$url] = preg_match('/\.auto(\d+)\.js$/', $url, $m) ? 20 + (int) $m[1] : 30;
		}
		$urls[$modulesJsFileUrl] = 100;
		$layout->pushJs($urls);
		
		$urls = array();
		foreach ($autoCssFiles as $url) {
			$urls[$url] = preg_match('/\.auto(\d+)\.css$/', $url, $m) ? 20 + (int) $m[1] : null;
		}
		$layout->pushCss($urls);
	}

	protected function beforeRender(HtmlTemplate &$tpl) {
		$tpl->user = UserSession::getUser();
	}

	public function index() {
		$this->forcePageReload();
		return true;
	}

}