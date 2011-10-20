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

	public function __construct(ArrayManager $manager, $id = null) {
		$this->manager = $manager;
		if ($id !== null) {
			$this->id = $manager->takeId($id, $this);
		} else {
			$this->id = $manager->nextId();
		}
		$manager->add($this);
	}
	
	public function __toString() {
		$class = str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
		return basename($class . '#' . $this->getId());
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
