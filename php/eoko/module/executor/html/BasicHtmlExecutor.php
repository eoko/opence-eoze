<?php

namespace eoko\module\executor\html;

use eoko\template\Renderer;
use eoko\template\Template;
use eoko\template\RawRenderer;
use eoko\template\HtmlRootTemplate;

class BasicHtmlExecutor extends HtmlExecutor {
	
	protected $defaultPageTitle = 'Eoko > Default Title';
	protected $pageTitle = null;

	protected function getPageTitle() {
		if ($this->pageTitle !== null) return $this->pageTitle;
		else return $this->makePageTitle();
	}
	
	protected function makePageTitle() {
		return $this->defaultPageTitle;
	}

	/**
	 * Finds the path of the default template with the given $name. The $name
	 * can be given with or without its extension.
	 * @param string $name
	 * @return string
	 */
	private function resolveDefaultTemplatePath($name) {
		$extension = '.html.php';
		if (substr($name, -9) !== strlen($extension)) $name .= $extension;
		return pathFromNamespace(__NAMESPACE__, "default_templates/$name");
	}

	/**
	 * Creates the layout {@link Renderer} that will be used in {@link createLayout()}.
	 *
	 * @return \eoko\template\HtmlRootTemplate
	 */
	protected function createLayoutRenderer() {
		return HtmlRootTemplate::create($this);
	}

	/**
	 * Creates the layout of an html page.
	 *
	 * @param Renderer $page
	 * @return Renderer
	 */
	protected function createLayout(Renderer $page) {
		
		if (null === $filename = $this->searchTemplatePath('layout.html.php')) {
			$filename = $this->resolveDefaultTemplatePath('layout');
		}
		
		$layoutRenderer = $this->createLayoutRenderer();

		return $layoutRenderer->setFile($filename)
				->set('head', $this->createHead()->set('meta',
					array('Content-Type' => 'text/html; charset=UTF-8')
				))
				->set('body', $this->createBody($page))
				;
	}
	
	/**
	 * Creates the head section of the html page.
	 *
	 * @return \eoko\template\Template
	 */
	protected function createHead() {
		if (null === $head = $this->createTemplate('head.html.php', false)) {
			$head = $this->createTemplate($this->resolveDefaultTemplatePath('head'));
		}
		return $head->set('title', $this->getPageTitle());
	}

	/**
	 * Creates the body section of the html page.
	 *
	 * @param Renderer $page
	 * @return Renderer
	 */
	protected function createBody(Renderer $page) {
		if (null === $body = $this->createTemplate('body.html.php', false)) {
			$body = $this->createTemplate($this->resolveDefaultTemplatePath('body'));
		}
		return $body->set('page', $page);
	}

	// --- Default Actions ---

	/**
	 * @action
	 * Default index action, which serves the page named 'index'.
	 *
	 * @see page()
	 */
	public function index() {
		$this->forward($this, 'page', array('page' => 'index'));
	}

	/**
	 * @action
	 * Serves the html template as specified by the 'page' param of the request.
	 *
	 * @return string|Renderer
	 */
	public function page() {
		
		$filename = $this->findTemplatePath($this->request->req('page'), $isTpl);
		
		if ($isTpl) {
			return $this->createTemplate($filename);
		} else {
			return RawRenderer::create()->setFile($filename)->render(true);
		}
	}
}
