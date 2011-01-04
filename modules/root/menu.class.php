<?php

namespace eoko\modules\root;

use eoko\module\executor\html\BasicHtmlExecutor;
use eoko\file\FileType;
use eoko\util\YmlReader as YAML;

use \ExtJSResponse;
use \UserSession;
use \Inflector;

use \IllegalArgumentException;

class menu extends BasicHtmlExecutor {

	protected $hasPartialRendering = true;

	public function get() {
		$this->setTemplate(
			$this->request->requireFirst(array('menu', 'name'), true)
		);
		return true;
	}

	/**
	 * @param string $name
	 * @return HtmlTemplate
	 */
	private function createMenu($menu) {
		switch ($menu) {
			default: throw new IllegalArgumentException('Not a menu: ' . $menu);

			case 'bookmarks':
			case 'admin':
			case 'general':
//REM				$this->get_menu_bookmarks();
		}

//		$this->setTemplate(
//			$this->findPath('menu.html.php', FileType::HTML_TPL)
//		);
		$tpl = $this->createTemplate('menu');
		$menuData = YAML::load($this->findPath('menu.yml'));
		$avMenuItems = &$menuData['menu-items'];

		$menuItems = array();
		if (isset($menuData[$menu])) foreach ($menuData[$menu] as $level => $items) {
			if (UserSession::isAuthorized((int) $level)) {
				foreach ($items as $item) {
					if (!array_key_exists($item, $avMenuItems)) {
						$avMenuItems[$item] = self::createDefaultMenuItem($item);
					}
					$menuItems[] = $avMenuItems[$item];
				}
			}
		}

		$tpl->items = $menuItems;

		return $tpl;
	}

	public function bunchGet() {
		$content = array();
		foreach ($this->request->req('names') as $name) {
			$content[$name] = $this->createMenu($name)->render(true);
		}

		ExtJSResponse::put('content', $content);
		ExtJSResponse::put('success', true);
		ExtJSResponse::answer();
	}

	private static function createDefaultMenuItem($id) {
		$defaultId = strtolower(Inflector::plural($id));
		$r = array(
			'module' => "Oce.Modules.$defaultId.$defaultId",
			'img' => 'folder.png',
			'iconClass' => 'icon-show-all',
			'title' => Inflector::capitalizeWords($id, '_', ' ')
		);
		$r['imgAlt'] = lang('Icône %module%', $r['title']);
		return $r;
	}

}