<?php

namespace eoko\modules\TreeMenu;

interface HasMenuActions {

	/**
	 * @return array[MenuAction]
	 */
	function getAvailableActions();

	/**
	 * @return MenuFamily
	 */
	function getFamily();
}