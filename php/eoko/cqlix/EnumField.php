<?php

namespace eoko\cqlix;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 déc. 2011
 */
interface EnumField {

	function getEnumCode($value);

	function getEnumLabelForValue($value);

	function getCodeLabels();
}
