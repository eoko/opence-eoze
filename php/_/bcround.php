<?php

/**
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 6 juin 2012
 */

if (!function_exists('bcround')) {
	/**
	 * Rounds a number with arbitrary precision.
	 * @param string $number
	 * @param int $scale
	 * @return string
	 * @author http://php.net/manual/en/function.bcscale.php
	 */
	function bcround($number, $scale = 0) {
		if (substr($number, 0, 1) === '-') {
			return '-' . bcround(substr($number, 1), $scale);
		}
		$fix = '5';
		for ($i = 0; $i < $scale; $i++) {
			$fix = "0$fix";
		}
		$number = bcadd($number, "0.$fix", $scale + 1);
		return bcdiv($number, "1.0", $scale);
	}
}
