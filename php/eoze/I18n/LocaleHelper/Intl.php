<?php

namespace eoze\I18n\LocaleHelper;

use eoze\I18n\LocaleHelper;

use UnsupportedOperationException;

use Locale;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class Intl implements LocaleHelper {

	public function __construct() {
		if (!extension_loaded('intl')) {
			$class = get_class();
			throw new UnsupportedOperationException(
<<<MSG
The class $class requires the extension intl to operate.
See: http://php.net/manual/intl.installation.php
	The extension is bundled with php (5.3+).
	or Pecl package: intl
	or Debian/Ubuntu package: php5-intl
MSG
			);
		}
	}

	public function getDefault() {
		return Locale::getDefault();
	}

	public function getPrimaryLanguage($locale) {
		return Locale::getPrimaryLanguage($locale);
	}

}
