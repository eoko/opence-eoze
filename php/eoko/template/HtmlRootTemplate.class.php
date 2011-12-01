<?php

namespace eoko\template;
use \IllegalStateException;

class HtmlRootTemplate extends HtmlTemplate {
	
	public $headTemplateName = 'head';
	
	private static $currentRootTemplate = null;
	
	/**
	 * @return HtmlRootTemplate
	 */
	public static function getRendering() {
		return self::$currentRootTemplate;
	}
	
	protected function doRender() {

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
		if (self::$currentRootTemplate !== null) {
			throw new IllegalStateException(
				'HtmlRootTemplate rendering collision (root templates are not '
				. 'allowed to have children root templates!)'
			);
		}

		self::$currentRootTemplate = $this;
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
		}
		
		self::$currentRootTemplate = null;
		
		parent::doRender();
	}
	
}