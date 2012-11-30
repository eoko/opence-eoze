<?php

namespace eoko\template;
use \IllegalStateException;

class HtmlRootTemplate extends HtmlTemplate {

	public $headTemplateName = 'head';

	private $compileOptions = false;
	/**
	 * @var \eoko\config\Application
	 */
	private $applicationConfig;

	private static $currentRootTemplate = null;

	/**
	 * @return HtmlRootTemplate
	 */
	public static function getRendering() {
		return self::$currentRootTemplate;
	}

	protected function doRender() {

		if (self::$currentRootTemplate !== null) {
			throw new IllegalStateException(
				'HtmlRootTemplate rendering collision (root templates are not '
				. 'allowed to have children root templates!)'
			);
		}

		self::$currentRootTemplate = $this;

		// implem
		$this->onRender();

		self::$currentRootTemplate = null;

		parent::doRender();
	}

	protected function onRender() {

		if (!isset($this->docType)) {
			$this->set('docType', 
<<<EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
EOD
			, false);
		}

		// --- Defer head rendering to allow for all other renderer to push css & js ---

		// 1. Remove head template, if any
		if (null !== $this->headTemplateName) {
			if (!isset($this->{$this->headTemplateName})) {
				throw new IllegalStateException(
<<<MSG
Missing head template in RootHtmlTemplate (property: $this->headTemplateName)
MSG
				);
			}

			$head = $this->{$this->headTemplateName};
			unset($this->{$this->headTemplateName});
		}

		// 2. Render every other templates
		foreach ($this->vars as $var) {
			if ($var instanceof Renderer) {
				$var->forceResultCaching = true;
				$var->render(true);
			}
		}

		// -- sort existing includes *now*
		// if head doesn't add some, it will be rendered correctly in one pass
		asort($this->cssIncludes);
		asort($this->jsIncludes);

		// 3. Push back head template
		if (isset($head)) {

			$head->css = array_keys($this->cssIncludes);
			$head->js = array_keys($this->jsIncludes);

			$cssIncludes = $this->cssIncludes;
			$jsIncludes = $this->jsIncludes;
			$this->cssIncludes = array();
			$this->jsIncludes = array();

			$this->vars[$this->headTemplateName] = $head;

			// Also render the head template, in case it wants to push includes itself
			$head->forceResultCaching = true;
			$head->render(true);

			$rerender = false;
			if ($this->cssIncludes) {
				$rerender = true;
				foreach ($cssIncludes as $url => $order) {
					$this->pushInclude('css', $url, $order);
				}
				asort($this->cssIncludes);
				$head->css = $this->cssIncludes;
			} else {
				$this->cssIncludes = $cssIncludes;
			}
			if ($this->jsIncludes) {
				$rerender = true;
				foreach ($jsIncludes as $url => $order) {
					$this->pushInclude('js', $url, $order);
				}
				asort($this->jsIncludes);
				$head->js = $this->jsIncludes;
			} else {
				$this->jsIncludes = $jsIncludes;
			}

			if ($rerender) {
				$head->clearCache();
				//$head->render(true);
			}

			// javascript & css compilation
			if ($this->compileOptions) {
				$this->onCompileIncludes($head, $this->compileOptions);
			}
		}
	}

	/**
	 * Compiles javascript and css files to be included in the head section,
	 * according to the specified options.
	 * @param array $options
	 * @see setCompileOptions()
	 */
	private function onCompileIncludes(Renderer $headRenderer, $options) {

		$java = isset($options['javaCommand'])
				? $options['javaCommand']
				: false;
		$yui = isset($options['yuiCompressorCommand'])
				? $options['yuiCompressorCommand']
				: false;

		$app = $this->applicationConfig;

		if (isset($options['javascript']) && $options['javascript']) {

			$compiler = new HtmlRootTemplate\JavascriptCompiler(
				$options['javascript'],
				$yui, $java,
				$app->getName(),
				$app->getVersionId());

			if ($compiler->compile($this->jsIncludes)) {
				$headRenderer->js = $compiler->getUrls();
				$headRenderer->clearCache();
			}
		}

		if (isset($options['css']) && $options['css']) {

			$compiler = new HtmlRootTemplate\CssCompiler(
				$options['css'],
				$yui, $java,
				$app->getName(),
				$app->getVersionId());

			if ($compiler->compile($this->cssIncludes)) {
				$headRenderer->css = $compiler->getUrls();
				$headRenderer->clearCache();
			}
		}
	}

	/**
	 * Sets options for merging/compilation of javascript and css includes.
	 *
	 * Supported options are the following:
	 *
	 * -   *yuiCompressorCommand* (required to enable compression)
	 * -   *javascript* (options or `false`)
	 * -   *css* (options or `false`)
	 *
	 * The type (js or css) specific options are the following:
	 *
	 * -   *merge*: (bool)
	 *     Merge all included files into one.
	 *
	 * -   *compress*: (bool)
	 *     Compress the file resulting of the merge using.
	 *     yui-compressor. If the command is not configured, or if the
	 *	   merge option is `false`, then this option will be ignored.
	 *
	 * -   *version*: (bool)
	 *     Add the codebase unique version to the merged
	 *     file name (this option will also be ignored if merge is set to
	 *     `false`).
	 *
	 * -   *preserveRemoteUrl*: (bool)
	 *     If set to `true`, then URLs with a query string or not under
	 *     the site base url won't be merged. This option is available
	 *     for javascript only, since remote CSS files will never be
	 *     merged.
	 *
	 * @param type $options
	 * @return HtmlRootTemplate
	 */
	public function setCompileOptions($options) {
		$this->compileOptions = $options;
		return $this;
	}

	/**
	 * Sets the application config used during merging of javascript/css
	 * files (needs the application name and the code base version id).
	 * @param \eoko\config\Application $application
	 * @return HtmlRootTemplate
	 */
	public function setApplicationConfig(\eoko\config\Application $application) {
		$this->applicationConfig = $application;
		return $this;
	}
}
