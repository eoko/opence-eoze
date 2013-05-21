<?php
/**
 * Copyright (C) 2012 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2012 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\template\HtmlRootTemplate;

use eoko\log\Logger;
use eoko\modules\root\ExtJsCdnConfig;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2012-11-22 11:26
 */
class ExtJsCompiler extends IncludeCompiler {

	private $ext3;
	private $ext4;
	private $sandboxExt4;
	/**
	 * @var ExtJsCdnConfig
	 */
	private $cdnConfig;

	/**
	 * @param string $type 'js' or 'css'
	 * @param ExtJsCdnConfig $cdnConfig
	 * @param bool $ext3
	 * @param bool $ext4
	 * @param bool $sandboxExt4
	 * @throws \UnsupportedOperationException
	 * @throws \InvalidArgumentException
	 */
	public function __construct($type, ExtJsCdnConfig $cdnConfig, $ext3 = true, $ext4 = true, $sandboxExt4 = true) {

		$method = 'build' . ucfirst(strtolower(($type))) . 'Includes';

		if (!method_exists($this, $method)) {
			throw new \InvalidArgumentException('Invalid type: ' . $type);
		}

		parent::__construct(
			array('merge' => false), null, null, null, null, false, array($this, $method)
		);

		$this->cdnConfig = $cdnConfig;
		$this->ext3 = $ext3;
		$this->ext4 = $ext4;
		$this->sandboxExt4 = $sandboxExt4;
	}

	protected function getFileContent($file) {
		throw new \RuntimeException('Unsupported operation');
	}

	public function buildJsIncludes() {

		$includes = array();

		$this->cdnConfig->extract($sources, $ext3, $ext4, $debugMax);

		if ($this->ext3) {
			$includes = array_merge($includes, array(
				// there is no 'debug-w-comments' version of the adapter
				$ext3 . '/adapter/ext/ext-base' . $debugMax->debug . '.js' => -10,
				$ext3 . '/ext-all' . $debugMax->comments . '.js' => -9,
				$ext3 . '/src/locale/ext-lang-fr.js' => -8, // was: EOZE_BASE_URL . 'js/ext/ext-lang-fr.js' => -8,
				$sources->eoze . '/js/ext/ext-basex.js' => -7, // was: EOZE_BASE_URL . 'js/ext/ext-basex.js' => -7,
			));
		}

		if ($this->ext4) {
			if ($this->sandboxExt4) {
				$includes = array_merge($includes, array(
					$ext4 . '/builds/ext-all-sandbox' . $debugMax->dev . '.js' => -11,
					// Can't use the CDN file for language because it is not sandboxed
					// $ext4 . '/locale/ext-lang-fr.js' => -5,
					$sources->eoze . '/ext4/locale/ext-lang-fr.js' => -5,
					$sources->eoze . '/ext4/locale/ext-lang-fr-eoze.js' => -4,
					$sources->eoze . '/ext4/builds/eo-ext4-compat.js' => -6, // Fixes ext 3 & 4 peaceful neighbouring
					// config for Ext4.Loader.setConfig
					$sources->opence . '/index.php?controller=root.html&action=getExt4LoaderConfig' => -3,

					// Deft
					$sources->eoze . '/js/deft/deft' . $debugMax->debug . '.js' => -2,
				));
			} else {
				throw new \UnsupportedOperationException();
			}
		}

		return $includes;
	}

	public function buildCssIncludes() {

		$includes = array();

		$this->cdnConfig->extract($sources, $ext3, $ext4, $debugMax);

		if ($this->ext3) {
			$includes = array_merge($includes, array(
				$ext3 . '/resources/css/reset-min.css' => -1,
				$ext3 . '/resources/css/ext-all.css' => 1,
			));
		}

		if ($this->ext4) {
			if ($this->sandboxExt4) {
				$includes = array_merge($includes, array(
					$ext4 . '/resources/css/ext-sandbox' . $debugMax->debug . '.css' => 1,
				));
			} else {
				throw new \UnsupportedOperationException();
			}
		}

		return $includes;
	}
}
