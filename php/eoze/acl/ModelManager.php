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

	private $idTableName = 'AclIdTable';

	private $roleTableName = 'AclRoleTable';
	
	private $groupTableName = 'AclGroupTable';
	
	private $userTableName = 'AclUserTable';
}
