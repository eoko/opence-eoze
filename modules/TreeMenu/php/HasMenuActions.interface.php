<?php

namespace eoko\modules\TreeMenu;

interface HasMenuActions {
	
	/**
	 * @return MenuActionProvider
	 */
	function getActionProvider();
}