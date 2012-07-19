<?php

namespace eoko\template\HtmlRootTemplate;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 juil. 2012
 */
class CssCompiler extends IncludeCompiler {

	protected $extension = 'css';

	protected function isPreserveRemoteUrl() {
		return true; // cannot resolve relative paths for remote files
	}

	// replaces images urls with their path relative to the web dir url
	protected function getFileContent($file) {
		$content =  file_get_contents($file);
		$dir = dirname($file);
		$content = preg_replace_callback('/url\(([^)]+)\)/', function($matches) use($dir) {
			$rel = \eoko\util\Files::getRelativePath(WEB_DIR_PATH, $dir);
			if (substr($rel, -1) !== '/') {
				$rel .= '/';
			}
			return 'url("' . $rel . trim($matches[1], '\'" ') . '")';
		}, $content);
		return $content;
	}

}
