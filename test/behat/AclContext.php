<?php

namespace eoze\behat\context;

use eoze\test\behat\DatabaseBehatContext;
use eoze\acl\AclManager;
use eoze\acl\ArrayManager;

class AclContext extends DatabaseBehatContext {

	/**
	 * @var AclManager
	 */
	private $manager = null;

	/**
	 * @return AclManager
	 */
	public function getManager() {
		if (!$this->manager) {
			$this->manager = new ArrayManager;
		}
		return $this->manager;
	}

	/**
	 * @Given /^les roles suivants:$/
	 */
	public function lesRolesSuivants(TableNode $table) {
		foreach ($table->getHash() as $row) {
			$this->getManager()->newRole($row['id']);
		}
	}

	/**
	 * @Given /^les groupes suivants:$/
	 */
	public function lesGroupesSuivants(TableNode $table) {
		foreach ($table->getHash() as $row) {
			$manager = $this->getManager();
			$group = $manager->newGroup($row['id']);
			if ($row['roles']) {
				$group->setRoles(explode(',', $row['roles']));
			}
		}
	}

	/**
	 * @Given /^le role "([^"]*)" existe$/
	 */
	public function leRoleExiste($rid) {
		assertNotNull($this->getManager()->getRole($rid));
	}

	/**
	 * @Given /^le groupe "([^"]*)" existe$/
	 */
	public function leGroupeExiste($gid) {
		assertNotNull($this->getManager()->getGroup($gid));
	}

    /**
	 * @Given /^le groupe "([^"]*)" a le role "([^"]*)"$/
	 */
	public function leGroupeALeRole($gid, $rid) {
		assertNotNull($group = $this->getManager()->getGroup($gid));
		if (eoze\parseInt($rid) !== null) {
			assertTrue($group->hasRole($rid));
		}
	}

//	protected function getDataSet() {
//		return $this->createYmlDataSet('levels.yml', __DIR__);
//	}
//
//    /**
//     * @Given /^l\'utilisateur est connecté$/
//     */
//    public function lUtilisateurEstConnecte()
//    {
//        throw new PendingException();
//    }
}