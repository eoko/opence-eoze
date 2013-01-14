<?php

namespace eoze\I18n;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 nov. 2011
 */
interface LocaleHelper {

	function getPrimaryLanguage($locale);

	function getDefault();
}
