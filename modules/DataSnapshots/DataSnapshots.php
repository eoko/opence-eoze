<?php

namespace eoko\modules\DataSnapshots;

use eoko\module\HasTitle;
use eoko\modules\TreeMenu\HasMenuActions;
use eoko\modules\TreeMenu\ActionProvider\ModuleProvider;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 3 oct. 2012
 */
class DataSnapshots extends _ implements HasMenuActions, HasTitle {

	private $actionProvider;

	protected function setPrivateState(&$vals) {
		$this->actionProvider = $vals['actionProvider'];
		unset($vals['actionProvider']);
		parent::setPrivateState($vals);
	}

	public function getActionProvider() {
		if (!$this->actionProvider) {
			$this->actionProvider = new ModuleProvider($this);
		}
		return $this->actionProvider;
	}

	public function getTitle() {
		return $this->getConfig()->get('title');
	}
}
