<?php

namespace eoko\modules\root;

use eoko\module\executor\html\BasicHtmlExecutor;
use eoko\template\HtmlRootTemplate;
use eoko\template\HtmlTemplate;
use eoko\module\ModuleManager;

use \UserSession;

use eoko\template\HtmlRootTemplate\JavascriptCompiler;
use eoko\template\HtmlRootTemplate\CssCompiler;

class Html extends BasicHtmlExecutor {

	protected function createLayoutRenderer() {
		return SimplifiedHtmlRootTemplate::create($this)
				->setCompileOptions($this->getModuleConfig()->get('compilation', false))
				->setApplicationConfig($this->getModule()->getApplicationConfig());
	}
	
	protected function onCreateLayout(SimplifiedHtmlRootTemplate $layout) {
		
		$layout->setIncludeUrls($this->buildIncludes());

		// Ext blank image
		$url = str_replace("'", "\\'", EOZE_BASE_URL . 'images/s.gif');
		$js = <<<JS
<script type="text/javascript">
	if (!window.Oce) window.Oce = { ext: {} };
	Oce.ext.BLANK_IMAGE_URL = '$url';
</script>
JS;
		
		// Extra script
		$layout->head->set('beforeJs', $js, false);
		$extra = $layout->head->extra = $this->createTemplate('head_extra_script');
		
		if (null !== $env = $this->request->get('env')) {
			$extra->context = json_encode(array(
				'environment' => $env,
				'target' => $this->request->get('target'),
			));
		}
	}
	
	/**
	 * Build the list of js & css files to be included in the index html page.
	 * 
	 * The returned array is of the form:
	 * 
	 *     array(
	 *         'js' => array(...), // list of js files in the order they should be included
	 *         'css' => array(...), // idem for css files
	 *     )
	 * 
	 * @return array
	 */
	private function buildIncludes() {
		
		$options = $this->getModuleConfig()->get('compilation', false);
		$app = $this->getModule()->getApplicationConfig();

		$java = isset($options['javaCommand'])
				? $options['javaCommand']
				: false;
		$yui = isset($options['yuiCompressorCommand'])
				? $options['yuiCompressorCommand']
				: false;
		
		$includes = array();
		
		foreach (array(
		
			'js' => array(
				new JavascriptCompiler(
					$options['javascript'],
					$yui, $java,
					$app->getName(), 
					$app->getVersionId(),
					null,
					array($this, '_buildJavascriptIncludes')
				),
				new JavascriptCompiler(
					$options['javascript'],
					$yui, $java,
					$app->getName() . '-modules',
					$app->getVersionId(),
					null,
					array($this, '_buildModulesJavascriptIncludes')
				),
			),
			
			'css' => array(
				new CssCompiler(
					$options['css'],
					$yui, $java,
					$app->getName(), 
					$app->getVersionId(),
					null,
					array($this, '_buildCssIncludes')
				),
			),
		) as $type => $compilers) {
			foreach ($compilers as $compiler) {
				$urls = $compiler->getUrls(true);
				$includes[$type] = isset($includes[$type])
						? array_merge($includes[$type], $urls)
						: $urls;
			}
		}
		
		return $includes;
	}
	
	/**
	 * Resolves an alias name to a list of javascript of css file. The returned
	 * array is of the form:
	 * 
	 *     array(
	 *         FILE_NAME => PRIORITY,
	 *     )
	 * 
	 * @param string $name The alias name (e.g. @ext).
	 * @param string $type JS|CSS
	 * @return array
	 */
	private function resolveIncludeAlias($name, $type) {
		
		if ($name === '@ext' && isset($_GET['ext4'])) {
			switch ($type) {
				case 'js':
					return array(
						EOZE_BASE_URL . 'js/ext/ext-base-debug.js' => -10,
						EOZE_BASE_URL . 'js/ext/ext-all-debug-w-comments.js' => -9,
						EOZE_BASE_URL . 'js/ext/ext-basex.js' => -8,
						EOZE_BASE_URL . 'js/ext/ext-lang-fr.js' => -7,
						
						EOZE_BASE_URL . 'ext4/builds/ext-all-sandbox-debug-w-comments.js' => -11,
						EOZE_BASE_URL . 'ext4/builds/eo-ext4-compat.js' => -6,
						
						EOZE_BASE_URL . 'js/deft/deft-debug.js' => -5,
					);
				case 'css':
					return array(
						EOZE_BASE_URL . 'ext4/resources/css/ext-sandbox-debug.css' => 1,
						EOZE_BASE_URL . 'css/ext-all.css' => 1,
					);
			}
		}
		
		$this->findPath($name, 'JS', $urlSpecs);
		
		$urls = array();
		foreach ($urlSpecs[strtoupper($type)] as $spec) {
			$urls[$spec['url']] = $spec['extra'];
		}
		
		return $urls;
	}
	
	/**
	 * Builds javascript file list for the application.
	 * 
	 * This method is declared public because it is passed as a handler to
	 * {@link JavascriptCompiler}.
	 * 
	 * @internal But the underscore prefix makes it innaccessible as a MVC
	 * action.
	 * 
	 * @param string $path Path of the expected merged javascript file.
	 * @param string $url Path of the expected merged javascript file.
	 * @return array
	 */
	public function _buildJavascriptIncludes() {
		
		$urls = array_merge(
			$this->resolveIncludeAlias('@ext', 'js'),
			$this->resolveIncludeAlias('@oce', 'js')
		);
		
		// Include js/*.auto[order].js and auto/*.js files
		$baseJsFiles = array();
		$autoJsFiles = array();
		
		foreach (ModuleManager::listModules(false) as $module) {
			foreach (array(
				array('re:\.auto\d*\.js$', '', false),
				array('re:\.auto\d*\.js$', 'js', false),
				array('glob:*.js', 'js/auto', true),
				array('glob:*.js', 'js.auto', true),
			) as $params) {
				$autoJsFiles = array_merge(
					$autoJsFiles,
					$module->listLineFilesUrl($params[0], $params[1], $params[2])
				);
			}
			$baseJsFiles = array_merge($baseJsFiles, $module->listLineFilesUrl('glob:*.js', 'js.base', true));
		}

		foreach($baseJsFiles as $url) {
			$urls[$url] = 10;
		}
		foreach ($autoJsFiles as $url) {
			$urls[$url] = preg_match('/\.auto(\d+)\.js$/', $url, $m) ? 20 + (int) $m[1] : 30;
		}
		
		return $urls;
	}

	/**
	 * Builds javascript for eoze modules.
	 * 
	 * This method is declared public because it is passed as a handler to
	 * {@link JavascriptCompiler}.
	 * 
	 * @internal But the underscore prefix makes it innaccessible as a MVC
	 * action.
	 * 
	 * @param string $path Path of the expected merged javascript file.
	 * @param string $url Path of the expected merged javascript file.
	 * @return array
	 */
	public function _buildModulesJavascriptIncludes($path, $url) {
		
		$devMode = $this->getModule()->getApplicationConfig()->isDevMode();

		$contents = array();
		
		foreach (ModuleManager::listModules(false) as $module) {
			if ($module instanceof \eoko\module\HasJavascript) {
				$content = $module->getJavascriptAsString();
				if ($content) {
					$contents[] = str_pad('// --- ' . $module->getName() . ' ', 100, '-');
					// We want errors to be crashy in dev mode
					if ($devMode) {
						$contents[] = $content;
					} else {
						$contents[] = 'try {';
						$contents[] = $content;
						$contents[] = '} catch (e) {';
						$contents[] = "	window.console && console.error('Error in module\'s javascript: {$module->getName()}');";
						$contents[] = '}';
					}
				}
			}
		}

		// merge modules javascript
		file_put_contents($path, implode(PHP_EOL . PHP_EOL, $contents));
		
		unset($contents);
		
		return array(
			$url => 100,
		);
	}

	/**
	 * Builds css file list for the application.
	 * 
	 * This method is declared public because it is passed as a handler to
	 * {@link CssCompiler}.
	 * 
	 * @internal But the underscore prefix makes it innaccessible as a MVC
	 * action.
	 * 
	 * @param string $path Path of the expected merged javascript file.
	 * @param string $url Path of the expected merged javascript file.
	 * @return array
	 */
	public function _buildCssIncludes() {
		
		$urls = array_merge(
			$this->resolveIncludeAlias('@ext', 'css'),
			$this->resolveIncludeAlias('@oce', 'css')
		);
		
		$autoCssFiles = array();
		
		foreach (ModuleManager::listModules(false) as $module) {
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('re:\.auto\d*\.css$', ''));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('re:\.auto\d*\.css$', 'css'));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('glob:*.css', 'css/auto', true));
			$autoCssFiles = array_merge($autoCssFiles, $module->listLineFilesUrl('glob:*.css', 'css.auto', true));
		}
		
		foreach ($autoCssFiles as $url) {
			$urls[$url] = preg_match('/\.auto(\d+)\.css$/', $url, $m) ? 20 + (int) $m[1] : null;
		}
		
		return $urls;
	}

	protected function pushLayoutExtraJs(HtmlRootTemplate $layout) {
		// Deprecated because pushing in the template is deprecated to simplify
		// building of css & js includes (a need that arose when we engaged into
		// merging & compressing include files).
		throw new \DeprecatedException();
	}

	protected function beforeRender(HtmlTemplate &$tpl) {
		$tpl->user = UserSession::getUser();
	}

	public function index() {
		$this->forcePageReload();
		return true;
	}

}