<?php

namespace eoko\modules\TreeMenu;

interface HasMenuActions {

	/**
	 * @return ActionProvider
	 */
	function getActionProvider();
}
