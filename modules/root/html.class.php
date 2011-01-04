<?php

namespace eoko\modules\root;

use eoko\module\executor\html\BasicHtmlExecutor;
use eoko\template\HtmlRootTemplate;
use eoko\template\HtmlTemplate;
use eoko\module\ModuleManager;
use eoko\util\Files;
use eoko\file\FileType;

use \UserSession;

class html extends BasicHtmlExecutor {
	
	protected function onCreateLayout(HtmlRootTemplate $layout) {
		
		$layout->pushAlias(array(
			'@ext', '@oce'
		));

		$this->pushLayoutExtraJs($layout);

		$layout->head->extra = $this->createTemplate('head_extra_script');
	}

	protected function pushLayoutExtraJs(HtmlRootTemplate $layout) {
		$layout->pushJs(
			ModuleManager::getModule('GridModule')->listFilesUrl(Files::regex('*.js'), null, FileType::JS), 10
		);
	}

	protected function beforeRender(HtmlTemplate &$tpl) {
		$tpl->user = UserSession::getUser();
	}

	public function index() {
		$this->forcePageReload();
		return true;
	}

}