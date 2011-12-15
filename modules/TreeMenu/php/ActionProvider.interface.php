<?php

namespace eoko\modules\TreeMenu;

use eoko\modules\TreeMenu\MenuAction;
use eoko\modules\TreeMenu\MenuFamily;

interface ActionProvider {
	
	/**
	 * @return array[MenuAction]
	 */
	function getAvailableActions();

	/**
	 * @return MenuFamily
	 */
	function getFamily();
	
	/**
	 * Gets the css icon class for the given $action and $module.
	 * @return string
	 */
	function getIconCls($action = null, $module = null);
}