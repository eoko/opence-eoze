<?php

namespace eoko\modules\root;

use eoko\module\executor\html\BasicHtmlExecutor;
use eoko\template\HtmlRootTemplate;
use eoko\template\HtmlTemplate;
use eoko\module\ModuleManager;

use \UserSession;

use eoko\template\HtmlRootTemplate\PassThroughCompiler;
use eoko\template\HtmlRootTemplate\JavascriptCompiler;
use eoko\template\HtmlRootTemplate\CssCompiler;
use eoko\template\HtmlRootTemplate\ExtJsCompiler;

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
		$js = implode(PHP_EOL, array(
			'<script type="text/javascript">',
			'	if (!window.Oce) window.Oce = { ext: {} };',
			"	Oce.ext.BLANK_IMAGE_URL = '$url';",
			'</script>',
		));

		// Extra script
		/** @noinspection PhpUndefinedFieldInspection */
		/** @noinspection PhpUndefinedMethodInspection */
		$layout->head->set('beforeJs', $js, false);
		/** @noinspection PhpUndefinedFieldInspection */
		$extra = $layout->head->extra = $this->createTemplate('head_extra_script');

		if (null !== $env = $this->request->get('env')) {
			/** @noinspection PhpUndefinedFieldInspection */
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
		$cdnConfig = $this->getModuleConfig()->get('cdn', false);

		$java = isset($options['javaCommand'])
				? $options['javaCommand']
				: false;
		$yui = isset($options['yuiCompressorCommand'])
				? $options['yuiCompressorCommand']
				: false;

		$includes = array();

		foreach (array(

			'js' => array(
				// CDN compiler
				new ExtJsCompiler('js', $cdnConfig, true, true, true),
				// Eoze + Opence
				new JavascriptCompiler(
					$options['javascript'],
					$yui, $java,
					$app->getName(),
					$app->getVersionId(),
					null,
					array($this, '_buildJavascriptIncludes')
				),
				// Module files
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
				// CDN compiler
				new ExtJsCompiler('css', $cdnConfig, true, true, true),
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
				/** @var \eoko\template\HtmlRootTemplate\IncludeCompiler $compiler */
				$urls = $compiler->getUrls(true);
				$includes[$type] = isset($includes[$type])
						? array_merge($includes[$type], $urls)
						: $urls;
			}
		}

		// Jasmine test runner
		if ($this->getModule()->getApplicationConfig()->isDevMode() && $this->request->get('jasmineIndex', false)) {
			$includes['css'][] = EOZE_BASE_URL . 'js/jasmine/jasmine.css';
			$includes['js'][] = EOZE_BASE_URL . 'js/jasmine/jasmine.js';
			$includes['js'][] = EOZE_BASE_URL . 'js/jasmine/jasmine-html.js';

			// include specs
			$includes['js'] = array_merge($includes['js'], $this->buildJasmineSpecIncludes());

			$includes['js'][] = $this->getRouter()->assemble(array(), array('name' => 'index/jasmine/app'));
		}

		return $includes;
	}

	/**
	 * Jasmine app test runner.
	 */
	public function getJasmineAppJavascript() {
		header('Content-type: text/javascript');
		echo <<<JS
Ext4.ns('eo').isUnitTestEnv = function() { return true; };

Ext4.require([
	'Eoze.lib.Eoze'
]);

Ext4.onReady(function() {
	jasmine.getEnv().addReporter(new jasmine.TrivialReporter());
	jasmine.getEnv().execute();
});
JS;

	}

	/**
	 * Gets the javascript config file for Ext4.Loader.setConfig().
	 */
	public function getExt4LoaderConfig() {

		$loaders = array();

		foreach (ModuleManager::listModules(false) as $module) {
			/** @var \eoko\module\Module $module */
			$loaders += $module->getExt4LoaderConfig();
		}

//		$loaders['Eoze.Ext'] = EOZE_BASE_URL . 'js/Eoze/Ext';
//		$loaders['Eoze.i18n'] = EOZE_BASE_URL . 'js/Eoze/i18n';
//		$loaders['Eoze.locale'] = EOZE_BASE_URL . 'js/Eoze/locale';
//		$loaders['Eoze.lib'] = EOZE_BASE_URL . 'js/Eoze/lib';

		$paths = json_encode($loaders);

		header('Content-type: text/javascript');

		echo <<<JS
Ext4.Loader.setConfig({
	enabled: true
	,disableCaching: false
	,paths: $paths
});
JS;
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
	 * @throws \DeprecatedException
	 * @return array
	 */
	private function resolveIncludeAlias($name, $type) {

		if ($name === '@ext') {
			throw new \DeprecatedException('Do not use the @ext alias (use '
					. 'eoko\template\HtmlRootTemplate\ExtJsCompiler instead).');
		}

		// Resolve @oce alias
		$this->findPath($name, 'JS', $urlSpecs);

		$urls = array();
		foreach ($urlSpecs[strtoupper($type)] as $spec) {
			$urls[$spec['url']] = $spec['extra'];
		}

		return $urls;
	}

	/**
	 * Builds javascript file list for the application (that is, both eoze, opence & modules files).
	 *
	 * @internal This method is declared public because it is passed as a handler to
	 * {@link JavascriptCompiler}, but the underscore prefix makes it unreachable as
	 * a MVC action.
	 *
	 * @return array
	 */
	public function _buildJavascriptIncludes() {
		return array_merge(
			$this->resolveIncludeAlias('@oce', 'js'),
			$this->listModulesJavascriptUrls()
		);
	}

	private function buildJasmineSpecIncludes() {

		$urls = array();

		foreach (ModuleManager::listModules(false) as $module) {
			/** @var \eoko\module\Module $module */
			$urls = array_merge($urls, $module->listLineFilesUrl('glob:*.js', 'js/tests', true));
			$urls = array_merge($urls, $module->listLineFilesUrl('glob:*.js', 'js.tests', true));
		}

		return $urls;
	}

	/**
	 * Writes the javascript code generated by modules into a file.
	 *
	 * @internal This method is declared public because it is passed as a handler to
	 * {@link JavascriptCompiler}, but the underscore prefix makes it unreachable as
	 * a MVC action.
	 * 
	 * @param string $path Path of the expected merged javascript file.
	 * @param string $url Path of the expected merged javascript file.
	 *
	 * @return array
	 */
	public function _buildModulesJavascriptIncludes($path, $url) {

		$devMode = $this->getModule()->getApplicationConfig()->isDevMode();

		$contents = array();

		foreach (ModuleManager::listModules(false) as $module) {
			if ($module instanceof \eoko\module\HasJavascript) {
				$content = $module->getJavascriptAsString();
				/** @var \eoko\module\Module $module */
				if ($content) {
					$contents[] = str_pad('// --- ' . $module->getName() . ' ', 100, '-');
					// We want errors to be crashy in dev mode
					if ($devMode) {
						$contents[] = $content;
					} else {
						array_push($contents,
							'try {',
							$content,
							'} catch (e) {',
							'   window.console && console.error && ',
							"console.error('Error in module's javascript: {$module->getName()}');",
							'}'
						);
//						$contents[] = 'try {';
//						$contents[] = $content;
//						$contents[] = '} catch (e) {';
//						$contents[] =
//							"\twindow.console && console.error('Error in module's javascript: {$module->getName()}');";
//						$contents[] = '}';
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

	private function listModulesJavascriptUrls() {
		$urls = array();

		// Include js/*.auto[order].js and auto/*.js files
		$baseJsFiles = array();
		$autoJsFiles = array();

		foreach (ModuleManager::listModules(false) as $module) {
			/** @var \eoko\module\Module $module */
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
	 * Builds css file list for the application.
	 * 
	 * This method is declared public because it is passed as a handler to {@link CssCompiler},
	 * but the underscore prefix makes it innaccessible as a MVC action.
	 * 
	 * @return array
	 */
	public function _buildCssIncludes() {

		$urls = $this->resolveIncludeAlias('@oce', 'css');

		$autoCssFiles = array();

		foreach (ModuleManager::listModules(false) as $module) {
			/** @var \eoko\module\Module $module */
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
