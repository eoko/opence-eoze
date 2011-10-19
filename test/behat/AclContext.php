<?php

namespace eoze\behat\context;

use eoze\test\behat\DatabaseBehatContext;

class AclContext extends DatabaseBehatContext {

	protected function getDataSet() {
		return $this->createYmlDataSet('levels.yml', __DIR__);
	}

    /**
     * @Given /^l\'utilisateur est connecté$/
     */
    public function lUtilisateurEstConnecte()
    {
        throw new PendingException();
    }
	
}