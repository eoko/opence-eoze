<?php

namespace eoko\module\executor\html;

use eoko\template\HtmlRootTemplate;
use eoko\template\Renderer;
use eoko\template\RawRenderer;
use SystemException;
use ExtJSResponse;

abstract class HtmlExecutor extends HtmlTemplateExecutor {
	
	protected $ajax = null;
	protected $hasPartialRendering = false;
	protected $ajaxTarget = '#page';
	
	protected $executorSuffix = 'html';
	
	protected function processResult($result) {
		if ($result === null) {
			return;
		} else if ($result instanceof Renderer) {
			$this->answer($result);
		} else if (is_bool($result)) {
			$tpl = $this->getTemplate();
			if (!$tpl->isContentSet()) {
				$action = $this->getAction();
				$tpl->setFile($result ? 
					$this->findTemplatePath(array($action . 'Success', $action))
					: $this->findTemplatePath(array($action . 'Failure', $action))
				);
			}
			$this->answer($tpl);
		} else if (is_string($result)) {
			$this->answer(RawRenderer::create()->setContent($result));
		}
	}
	
	/**
	 * @return Renderer
	 */
	abstract protected function createLayout(Renderer $page);
	
	protected function onCreateLayout(HtmlRootTemplate $layout) {}
	
	protected function beforeRender(Renderer &$template) {
		return $template;
	}

	private function answer(Renderer $template) {
		
		if (null !== $tpl = $this->beforeRender($template)) {
			$template = $tpl;
		}
		
		if ($this->ajax === false || !$this->request->get('fragment', false)) {
			if (!$this->hasPartialRendering && !$this->request->get('rawFragment', false)) {
				$this->onCreateLayout($layout = $this->createLayout($template));
				$layout->render();
			} else {
				$template->render();
			}
		} else {
			$template->forceResultCaching = true;
			$content = $template->render(true);
			
			// TODO => css, js ...
			
			ExtJSResponse::put('content', $content);
			ExtJSResponse::put('target', $this->ajaxTarget);
			ExtJSResponse::answer(false);
		}
	}
	
	/**
	 * Forces the full reload of the page, that is, if the executor was 
	 * requested to answer an html fragment to update the page with ajax, it
	 * will instead order the client to reload the page with the current action
	 * request.
	 * 
	 * This method will interrupt execution if the current request was an
	 * ajax fragment request.
	 */
	protected function forcePageReload() {
		if ($this->request->get('fragment', false)) {
			$this->request->override('fragment', false);
			ExtJSResponse::put('url', $this->request->buildUrl());
			ExtJSResponse::answer(true);
		}
	}

}