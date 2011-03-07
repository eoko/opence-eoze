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

	private $menuConfig = null;

	public function get() {
		$this->setTemplate(
			$this->request->requireFirst(array('menu', 'name'), true)
		);
		return true;
	}
	
	private function getMenuData() {
		if (!$this->menuConfig) $this->menuConfig = YAML::load($this->findPath('menu.yml'));
		return $this->menuConfig;
	}

	public function getMenuGroup($menu) {
		$cfg = $this->getMenuData();
		if (isset($cfg['menus'])) {
			if (array_key_exists($menu, $cfg['menus'])) {
				return isset($cfg['menus'][$menu]) ? $cfg['menus'][$menu] : array();
			}
		} else {
			// Legacy syntax
			// Known projects depending upon:
			// - spanki
			// - copb
			if (isset($cfg[$menu])) {
				return $cfg[$menu];
			}
		}
		// Not found
		throw new IllegalArgumentException('Not a menu: ' . $menu);
	}

	/**
	 * @param string $name
	 * @return HtmlTemplate
	 */
	private function createMenu($menu) {
		
		$menuData = $this->getMenuData();

		$avMenuItems = $menuData['menu-items'];
		$menuItems = array();

		foreach ($this->getMenuGroup($menu) as $level => $items) {
			if (UserSession::isAuthorized((int) $level)) {
				foreach ($items as $item) {
					if (!array_key_exists($item, $avMenuItems)) {
						$avMenuItems[$item] = self::createDefaultMenuItem($item);
					}
					$menuItems[] = $avMenuItems[$item];
				}
			}
		}

		$tpl = $this->createTemplate('menu');
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