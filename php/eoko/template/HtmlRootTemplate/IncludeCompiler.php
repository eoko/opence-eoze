<?php

namespace eoko\template\HtmlRootTemplate;

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

	private $baseName;

	private $version;

	private $options;

	protected $extension;
	protected $yuiOptions;

	protected $force = false;

	private $urls;

	public function __construct($options, $yui, $baseName, $version, $force = null) {
		$this->options = $options;
		$this->yui = $yui;
		$this->baseName = $baseName;
		$this->version = $version;
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
	 *
	 * @param array $prioritizedUrls
	 * @return bool `true` on success, that is if the input files have been at least
	 * merged (even if compression was required but failed)
	 */
	public function compile($prioritizedUrls) {

		$this->urls = $prioritizedUrls;

		$filename = $this->baseName;

		if ($this->is('version') && $this->version) {
			$filename .= '.' . $this->version;
		}

		$mergedName = "$filename.$this->extension";
		$mergedFile = WEB_DIR_PATH . $mergedName;
		$mergedUrl = WEB_DIR_URL . $mergedName;

		$compressedName = "$filename.min.$this->extension";
		$compressedFile = WEB_DIR_PATH . $compressedName;
		$compressedUrl = WEB_DIR_URL . $compressedName;

		// Merge
		if ($this->is('merge')) {

			$localFiles = $this->extractLocalFileFromUrls($this->urls, true);
			$this->urls = array_keys($this->urls);

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

	public function getUrls() {
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
			exec($cmd, $output, $result);
			if ($result !== 0) {
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
	 * @param bool $keepRemoteUrls `true` to add raw remote URLs to the returned array.
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
