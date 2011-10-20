<?php

namespace eoze\acl;

use ModelTable;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class ModelManager extends AbstractAclManager {
	
	/**
	 * @var ModelTable
	 */
	private $roleTable;
	/**
	 * @var ModelTable
	 */
	private $userTable;
	/**
	 * @var ModelTable
	 */
	private $groupTable;
	
	function __construct(ModelTable $roleTable, ModelTable $userTable, ModelTable $groupTable) {
		$this->roleTable = $roleTable;
		$this->userTable = $userTable;
		$this->groupTable = $groupTable;
	}
	
	public protected function getGroup($gid) {
		return $this->groupTable->loadModel($uid);
	}

	public protected function getRole($rid) {
		return $this->roleTable->loadModel($uid);
	}

	public protected function getUser($uid) {
		return $this->userTable->loadModel($uid);
	}

}
