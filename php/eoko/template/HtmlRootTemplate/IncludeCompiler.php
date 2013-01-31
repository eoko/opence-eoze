<?php

namespace eoko\template\HtmlRootTemplate;

use eoko\log\Logger;

/**
 * Base implementation for included file compiler.
 *
 * YUI Compressor
 * ==============
 * Compression of CSS and javascript files require YUI Compressor.
 *
 * On Ubuntu:
 *     sudo apt-get install yui-compressor
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 juil. 2012
 */
abstract class IncludeCompiler {

	private $yui;
	// required by last tested version of yui
	private $javaCommand = '/usr/bin/java';

	private $baseName;

	private $version;

	private $options;

	protected $extension;
	protected $yuiOptions;

	protected $force = false;

	/**
	 * @var Callback
	 */
	private $builder;

	private $urls;

	public function __construct($options, $yui, $javaCommand, $baseName, $version, $force = null,
			$builder = null) {
		$this->options = $options;
		$this->yui = $yui;
		$this->javaCommand = $javaCommand;
		$this->baseName = $baseName;
		$this->version = $version;
		$this->builder = $builder;
		if ($force !== null) {
			$this->force = $force;
		} else {
			$this->force = $this->is('force');
		}
	}

	private function is($option) {
		return isset($this->options[$option]) && $this->options[$option];
	}

	/**
	 * Gets the paths & urls of merged and compressed include.
	 * 
	 * Returns an array with the indexes: mergedFile, mergedUrl, compressedFile,
	 * and compressedUrl.
	 * 
	 * @param string $index If specified, only this entry will be returned as a string.
	 * @return array|string
	 */
	private function getTargetNames($index = null) {

		$filename = $this->baseName;

		if ($this->is('version') && $this->version) {
			$filename .= '.' . $this->version;
		}

		$mergedName = "$filename.$this->extension";
		$r['mergedFile'] = WEB_DIR_PATH . $mergedName;
		$r['mergedUrl'] = WEB_DIR_URL . $mergedName;

		$compressedName = "$filename.min.$this->extension";
		$r['compressedFile'] = WEB_DIR_PATH . $compressedName;
		$r['compressedUrl'] = WEB_DIR_URL . $compressedName;

		return $index !== null ? $r[$index] : $r;
	}

	/**
	 * Builds a flat list of urls, in the order they should be included, from a
	 * prioritized urls array of the form:
	 * 
	 *     array(
	 *         URL => PRIORITY,
	 *     )
	 * 
	 * Items with a `null` priority are put to the end of the list in a random
	 * order.
	 * 
	 * @param array $prioritizedUrls
	 * @return array
	 */
	private static function extractPrioritizedUrls($prioritizedUrls) {
		$append = $prepend = array();
		foreach ($prioritizedUrls as $url => $priority) {
			if ($priority === null) {
				$append[] = $url;
			} else {
				$prepend[$url] = $priority;
			}
		}
		asort($prepend);
		return array_merge(array_keys($prepend), $append);
	}

	/**
	 * @param array $prioritizedUrls
	 * @return bool `true` on success, that is if the input files have been at least
	 * merged (even if compression was required but failed)
	 */
	public function compile($prioritizedUrls) {

		/** @var $mergedFile */
		/** @var $mergedUrl */
		/** @var $compressedFile */
		/** @var $compressedUrl */
		extract($this->getTargetNames());

		// Merge
		if ($this->is('merge')) {

			$localFiles = $this->extractLocalFileFromUrls($prioritizedUrls, true);
			$this->urls = self::extractPrioritizedUrls($prioritizedUrls);

			if (!file_exists($mergedFile) || $this->force) {
				$this->merge($localFiles, $mergedFile);
			}

			// Compress
			if ($this->is('compress') && $this->compress($mergedFile, $compressedFile)) {
				$this->urls[] = $compressedUrl;
				return true;
			}
			// no else, to fallback on merged file if compression fails

			$this->urls[] = $mergedUrl;
			return true;
		}

		return false;
	}

	/**
	 * Build the includes and assign them to the {@link urls} property, 
	 * according to the following strategy:
	 * 
	 * - if the configuration says to merge,
	 *     - and the compiled file already exists, then use this url
	 *     - else, use the configured includes list builder to get the list, compile that
	 *       and use the result
	 * 
	 * - else, use the list builder and use the whole list of urls
	 * 
	 * Compiled file means either merged or merged & compressed, depending on the
	 * configuration.
	 * 
	 * @return void
	 */
	private function build() {

		$names = $this->getTargetNames();

		/** @var $mergedFile */
		/** @var $mergedUrl */
		extract($names);

		// if configured to merge
		if ($this->is('merge')) {
			// if already merged: done
			if (false && file_exists($mergedFile)) {
				$this->urls = array($mergedUrl);
				return;
			}
			// else, build url list & compile
			else {
				$urls = call_user_func($this->builder, $mergedFile, $mergedUrl);
				$this->compile($urls);
			}
		}
		// No merge: just build url list
		else {
			$urls = call_user_func($this->builder, $mergedFile, $mergedUrl);
			$this->urls = $this->extractPrioritizedUrls($urls);
		}
	}

	/**
	 * Get include URLs.
	 * 
	 * @param boolean $build If true, then the compiler will use the builder provided
	 * to the constructor to build the base url list (if needed) to compile according
	 * to the configuration.
	 * 
	 * @return array()
	 */
	public function getUrls($build = false) {

		if ($build) {
			$this->build();
		}

		return $this->urls;
	}

	private function compress($in, $out) {
		if (!$this->yui) {
			return false;
		}
		if (!file_exists($out) || $this->force) {
			$inArg = escapeshellarg($in);
			$outArg = escapeshellarg($out);
			$cmd = "$this->yui --charset UTF-8 -o $outArg $inArg 2>&1";
			putenv("JAVA_CMD=$this->javaCommand");
			exec($cmd, $output, $result);
			if ($result !== 0) {
				Logger::get($this)->warn('YUI Compressor failed: ' . $output);
				return false;
			}
		}
		return file_exists($out) && filesize($out) > 0;
	}

	/**
	 * Extracts local files from an array of prioritized URLs.
	 *
	 * @param array $urls
	 * An associative array with URLs as index and priority as value.
	 * E.g.
	 *	 array(
	 *		 'http://my.url/myFile.js' => 1,
	 *	 )
	 * @param int[] &$url
	 * @param bool $removeLocalUrls `true` to remove local URLs from the passed $urls array.
	 * @return string[]
	 */
	private function extractLocalFileFromUrls(&$urls, $removeLocalUrls = true) {
		$files = array();
		$baseUrlLen = strlen(SITE_BASE_URL);
		$externalUrls = array();
		foreach ($urls as $url => $priority) {
			if ($priority === null) {
				$priority = \PHP_INT_MAX;
			}
			if (substr($url, 0, $baseUrlLen) === SITE_BASE_URL
					&& !strstr($url, '?')) {
				$files[$priority][] = ROOT . substr($url, $baseUrlLen);
				if ($removeLocalUrls) {
					unset($urls[$url]);
				}
			} else if (!$this->isPreserveRemoteUrl()) {
				// Including ?! at the end of the URL is a mean to prevent them from being
				// merged, but that may also burst the browser cache. We don't want that,
				// so we clean the url.
				if (substr($url, -2) === '?!') {
					$url = substr($url, 0, -2);
				}
				$files[$priority][] = $url;
			}
		}
		ksort($files);
		$list = array();
		foreach ($files as $fileGroup) {
			$list = array_merge($list, $fileGroup);
		}
		return $list;
	}

	protected function isPreserveRemoteUrl() {
		return $this->is('preserveRemoteUrls');
	}

	private function merge($files, $destFile) {
		$content = '';
		foreach ($files as $file) {
			$name = substr($file, 0, strlen(ROOT)) === ROOT
					? substr($file, strlen(ROOT))
					: $file;
			$content .= PHP_EOL . "/* --- $name */" . PHP_EOL;
			$content .= $this->getFileContent($file) . PHP_EOL;
		}
		if ($destFile) {
			file_put_contents($destFile, $content);
		} else {
			return $content;
		}
	}

	abstract protected function getFileContent($file);
}
