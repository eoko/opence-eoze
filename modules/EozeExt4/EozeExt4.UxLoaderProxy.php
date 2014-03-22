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

namespace eoko\modules\EozeExt4;

use eoko\file\Mime;
use eoko\module\executor\ExecutorBase;
use eoko\module\ModuleManager;
use eoko\modules\root\root;
use eoko\php\ErrorException;

/**
 * This executor proxies Ext ux files (javascript and CSS) from the configured
 * Ext4 CDN.
 *
 * While working with sandboxed Ext4, the proxy tries to fix hardcoded 'x-' prefixes
 * in both javascript and CSS files.
 *
 * @since 2013-05-16 16:35
 */
class UxLoaderProxy extends ExecutorBase {

	/**
	 * Base CSS prefix for Ext4.
	 *
	 * @var string
	 */
	private $ext4BaseCssPrefix = 'x4-';

	/**
	 * Action for retrieving the contents of an ux file.
	 *
	 * @return null|\Zend\Http\Response
	 * @throws \SecurityException
	 */
	public function index() {

		$requestParams = $this->getRequest();
		$cdnIdentifier = $requestParams->req('cdn');
		$file = $requestParams->req('file');
		$file = ltrim($file, '/');

		if (strstr($file, '..')) {
			throw new \SecurityException();
		}

		/** @var EozeExt4 $module */
		$module = $this->getModule();
		$directories = $module->getExt4UxSourcePaths();

		// Try local directories
		foreach ($directories as $dir) {
			$path = $dir . '/' . $file;
			if (file_exists($path)) {
				$contents = file_get_contents($path);
				return $this->sendExt4File($file, $contents);
			}
		}

		// Try CDN
		/** @var root $rootModule */
		$rootModule = ModuleManager::getModule('root');
		$cdnConfig = $rootModule->getCdnConfig();
		$ext4BaseUrl = $cdnConfig->getBaseUrl($cdnIdentifier);

		if ($ext4BaseUrl) {
			//$path = $ext4BaseUrl . '/examples/ux/' . $file;
			$path = $ext4BaseUrl . '/' . $file;

			if (substr($path, 0, 2) === '//') {
				$path = 'http:' . $path;
			}

			// We're working with an URL here, we can't rely on file_exists
			try {
				$contents = file_get_contents($path);
				return $this->sendExt4File($file, $contents);
			} catch (ErrorException $ex) {
				// file does not exist
			}
		}

		// If nothing found, answer 404
		$response = $this->getResponse();
		$response->setStatusCode($response::STATUS_CODE_404);
		return $response;
	}

	/**
	 * Sends javascript or CSS file contents, deciding file type based on its extension, and
	 * applying hardcoded css prefix fixes accordingly.
	 *
	 * @param string $filename
	 * @param string $contents
	 * @return null
	 */
	private function sendExt4File($filename, $contents) {
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
		if (strcasecmp($extension, 'js') === 0) {
			return $this->sendExt4JsFile($contents);
		} else if (strcasecmp($extension, 'css') === 0) {
			return $this->sendExt4CssFile($contents);
		} else {
			$mime = Mime::fromFile($filename, false);
			if ($mime !== false) {
				header('Content-Type: ' . $mime);
			}
			echo $contents;
			return null;
		}
	}

	/**
	 * Sends javascript file contents with appropriate Content-Type header.
	 *
	 * @param string $contents
	 * @return null
	 */
	private function sendJsFile($contents) {
		header('Content-Type: text/javascript');
		echo $contents;
		return null;
	}

	/**
	 * Sends CSS file contents with appropriate Content-Type header.
	 *
	 * @param string $contents
	 * @return null
	 */
	private function sendCssFile($contents) {
		header('Content-Type: text/css');
		echo $contents;
		return null;
	}

	/**
	 * Sends javascript file contents with appropriate Content-Type, after having fixed hardcoded
	 * 'x-' prefixes.
	 *
	 * @param string $contents
	 * @return null
	 */
	private function sendExt4JsFile($contents) {
		$contents = $this->fixExt4BaseCssPrefix($contents);

		$contents = '(function(Ext) {' . PHP_EOL
			. $contents
			. '})(window.Ext4 || Ext);';

		return $this->sendJsFile($contents);
	}

	/**
	 * Sends CSS file contents with appropriate Content-Type, after having fixed hardcoded
	 * `.x-` prefixes.
	 *
	 * @param string $contents
	 * @return null
	 */
	private function sendExt4CssFile($contents) {

		// Fixes hardcoded .x- prefixes in css
		if (!empty($this->ext4BaseCssPrefix)) {
			$contents = str_replace('.x-', '.' . $this->ext4BaseCssPrefix, $contents);
		}

		return $this->sendCssFile($contents);
	}

	/**
	 * Remove hardcoded 'x-' strings from JS source code, and replace it with Ext.baseCSSPrefix.
	 *
	 * @param string $contents
	 * @return string The fixed contents.
	 */
	private function fixExt4BaseCssPrefix($contents) {
		foreach (array('"', "'") as $i) {
			$regex = "/{$i}[^$i\\n]*\\bx-[^$i]*$i/";

			$contents = preg_replace_callback($regex, function($matches) use($i) {
				$subject = $matches[0];
				return preg_replace('/\\bx-/', "$i + Ext.baseCSSPrefix + $i", $subject);
			}, $contents);
		}
		return $contents;
	}

}
