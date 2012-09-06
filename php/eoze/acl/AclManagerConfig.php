<?php

namespace eoze\acl;

use eoze\util\ConfigObject;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
class AclManagerConfig extends ConfigObject {

	public $disablableRolesDefault = true;
	
	public $disablableGroupsDefault = true;
	
	public $disablableUserDefault = true;
}
