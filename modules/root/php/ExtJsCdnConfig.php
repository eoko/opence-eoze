<?php
/**
 * Copyright (C) 2013 Eoko
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
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\modules\root;

/**
 * Configuration reader for Ext JS CDNs.
 *
 * @since 2013-05-16 17:03
 */
class ExtJsCdnConfig {

	private $config;

	private $sources, $ext4, $ext3, $debugMax;

	/**
	 * Gets the root URL of the Ext JS 3 SDK, or null if none is configured.
	 *
	 * @return string|null
	 */
	public function getExt3Url() {
		return $this->ext3;
	}

	/**
	 * Gets the root URL of the Ext JS 4 SDK, or null if none is configured.
	 *
	 * @return string|null
	 */
	public function getExt4Url() {
		return $this->ext4;
	}

	/**
	 * Gets the base URL for the CDN specified with its identifier.
	 *
	 * @param string $cdnIdentifier
	 * @return string
	 * @throws Exception\Domain
	 */
	public function getBaseUrl($cdnIdentifier) {
		switch ($cdnIdentifier) {
			case 'ext4':
				return $this->ext4;
			case 'ext4ux':
				return $this->ext4 . '/examples/ux';
			case 'ext3':
				return $this->ext3;
			default:
				throw new Exception\Domain('Unknown CDN identifier: ' . $cdnIdentifier);
		}
	}

	/**
	 * Creates a new CdnConfig object.
	 *
	 * @param array|false|null $config
	 */
	public function __construct($config) {
		$this->config = $config;

		$this->extractCdnConfig(
			$this->sources,
			$this->ext3,
			$this->ext4,
			$this->debugMax
		);
	}

	/**
	 * Extracts values to the passed variables.
	 *
	 * @param object$sources
	 * @param string $ext3
	 * @param string $ext4
	 * @param object $debugMax
	 */
	public function extract(&$sources, &$ext3, &$ext4, &$debugMax) {
		$sources = $this->sources;
		$ext3 = $this->ext3;
		$ext4 = $this->ext4;
		$debugMax = $this->debugMax;
	}

	private function extractCdnConfig(&$sources, &$ext3, &$ext4, &$debugMax) {

		$debug = false;
		$sources = array(
			'sencha' => '//cdn.sencha.io',
			'eoze' => '%EOZE%',
			'opence' => '%OPENCE%',
		);

		if ($this->config) {
			$config = $this->config;

			$debug = isset($config['debug'])
				? $config['debug']
				: $debug;

			if (isset($config['sources'])) {
				$sources = array_merge($sources, $config['sources']);
			}

			// Replace source tokens
			foreach ($sources as &$source) {
				$source = str_replace('%EOZE%', rtrim(EOZE_BASE_URL, '/'), $source);
				$source = str_replace('%OPENCE%', rtrim(SITE_BASE_URL, '/'), $source);
			}
			unset($source);

			// Extract libs
			if (isset($config['lib'])) {
				foreach ($config['lib'] as $name => $url) {
					foreach ($sources as $source => $path) {
						$url = str_replace('$' . $source, $path, $url);
					}
					$$name = $url;
				}
			}
		}

		// Make it an object
		$sources = (object) $sources;

		// Apply debug max
		$debugMax = $this->debugMax($debug);
	}

	/**
	 * Gets a debug object configured with the debug suffix to use for the various
	 * debug levels, according to the supplied debug level string.
	 *
	 * @param string $debug debug level (any of: false < 'debug' < 'debug-w-comments' < 'dev')
	 * @return object
	 */
	private function debugMax($debug) {

		$debugMax = (object) array(
			'none' => '',
			'debug' => '-debug',
			'comments' => '-debug-w-comments',
			'dev' => '-dev',
		);

		switch ($debug) {
			default:
				$debugMax->debug = $debugMax->none;
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'debug':
				$debugMax->comments = $debugMax->debug;
			case 'debug-w-comments':
				$debugMax->dev = $debugMax->comments;
			case 'dev': // prevents falling into default
		}

		return $debugMax;
	}

}
