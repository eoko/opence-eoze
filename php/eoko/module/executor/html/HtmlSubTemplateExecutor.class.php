<?php

namespace eoko\module\executor\html;

class HtmlSubTemplateExecutor extends HtmlTemplateExecutor {
	
	/** @var HtmlTemplateExecutor */
	private $parent;
	
	public function __construct(HtmlTemplateExecutor $parent) {
		$this->parent = $parent;
		parent::__construct($this->parent->module);
	}
	
	protected function findTemplatePath($name, &$isTpl = null) {
		return $this->parent->findTemplatePath($name, $isTpl);
	}
}