<?php

namespace eoze\acl\ArrayManager;

use eoze\acl\ArrayManager;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class Role implements \eoze\acl\Role {
	
	private $id;
	
	/**
	 * @var ArrayManager
	 */
	private $manager;

	public function __construct(ArrayManager $manager) {
		$this->manager = $manager;
		$this->id = $manager->nextId();
	}
	
	public function getId() {
		return $this->id;
	}

	/**
	 * @return ArrayManager
	 */
	public function getManager() {
		return $this->manager;
	}

}
