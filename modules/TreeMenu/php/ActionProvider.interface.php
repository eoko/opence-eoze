<?php

namespace eoko\modules\TreeMenu;

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