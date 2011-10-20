<?php

namespace eoze\acl;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 20 oct. 2011
 */
interface HasGroups {
	
	/**
	 * @return [Group]
	 */
	function getGroups();
}
