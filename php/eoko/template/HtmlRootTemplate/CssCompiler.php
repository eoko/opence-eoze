<?php

namespace eoko\template\HtmlRootTemplate;
use eoko\util\Files;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2012-07-19
 */
class CssCompiler extends IncludeCompiler {

	protected $extension = 'css';

	protected function isPreserveRemoteUrl() {
		return true; // cannot resolve relative paths for remote files
	}

	// replaces images urls with their path relative to the web dir url
	protected function getFileContent($file) {
		if (substr($file, 0, 2) === '//') {
			$file = "http:$file";
		}
		$content = file_get_contents($file);
		$dir = dirname($file);
		$remoteDir = preg_match('@^(?:https?:)?//@', $dir);
		if ($remoteDir) {
			$dir = preg_replace('@^(?:https?:)@', '', $dir);
			$dir = rtrim($dir, '/') . '/';
		}
		$content = preg_replace_callback('/url\(([^)]+)\)/', function($matches) use($dir, $remoteDir) {
			$file = trim($matches[1], '\'" ');
			if ($remoteDir) {
				return 'url("' . $dir . $file . '")';
			} else if (substr($matches[1], 0, 5) === 'data:') {
				return 'url("' . $file . '")';
			} else {
				$rel = Files::getRelativePath(WEB_DIR_PATH, $dir);
				if (substr($rel, -1) !== '/') {
					$rel .= '/';
				}
				return 'url("' . $rel . $file . '")';
			}
		}, $content);
		return $content;
	}

}
