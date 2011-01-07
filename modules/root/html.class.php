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