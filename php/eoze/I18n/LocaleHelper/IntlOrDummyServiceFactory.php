<?php

namespace eoze\I18n\LocaleHelper;

use eoze\I18n\LocaleHelper;
use eoze\Dependency\ServiceFactory;

use UnsupportedOperationException;

use Locale;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
class IntlOrDummyServiceFactory implements ServiceFactory {
	
	public function createService() {
		if (extension_loaded('intl')) {
			return new Intl();
		} else {
			return new Dummy();
		}
	}
}
