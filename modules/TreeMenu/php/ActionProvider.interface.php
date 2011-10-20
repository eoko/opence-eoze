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
}