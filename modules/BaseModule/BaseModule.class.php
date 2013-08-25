<?php

namespace eoko\modules\BaseModule;

use eoko\module\Module;

use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

/**
 * Base module base class (that's very basic).
 * 
 * 
 * Configuration
 * -------------
 * 
 * ### Title
 * 
 * This class implements the {@link eoko\module\HasTitle HasTitle} interface.
 * It will returns the title found in the `module.title` config node (which
 * must be a string).
 * 
 * 
 * ### iconCls
 * 
 * The BaseModule provides a default iconCls for modules under `extra.iconCls`:
 * 
 *     extra.iconCls: ico %module% %action%
 * 
 * %module% will be replaced with the actual module name, and %action% with the
 * menu action id (open, add, ...), or the action provided by the component
 * requesting the iconCls (GridModule add and edit window provides 'add' and 
 * 'edit' as action).
 * 
 * 
 * @package Modules\Eoko\BaseModule
 * @author Éric Ortéga <eric@planysphere.fr>
 */
class BaseModule extends Module implements HasTitle, HasMenuActions {

	private $actionProvider;

	/**
	 * @return eoko\modules\TreeMenu\ActionProvider
	 */
	public function getActionProvider() {
		if (!$this->actionProvider) {
			$this->actionProvider = new ModuleProvider($this);
		}
		return $this->actionProvider;
	}

	protected function setPrivateState(&$vals) {
		$this->actionProvider = $vals['actionProvider'];
		unset($vals['actionProvider']);
		parent::setPrivateState($vals);
	}

	public function getTitle() {
		if (null !== $title = $this->getConfig()->getValue('title', null)) {
			return $title;
		} else {
			$config = $this->getConfig()->get('module');
			if (isset($config['title'])) {
				return $config['title'];
			} else {
				return null;
			}
		}
	}

	public function getIconCls($action = null) {
		return $this->getActionProvider()->getIconCls($action);
	}
}
