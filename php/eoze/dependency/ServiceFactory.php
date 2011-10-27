<?php

namespace eoze\dependency;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
interface ServiceFactory {
	
	function createService(array $config = null);
}
