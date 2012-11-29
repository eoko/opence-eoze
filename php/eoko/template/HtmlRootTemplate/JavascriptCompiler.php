<?php

namespace eoko\template\HtmlRootTemplate;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 19 juil. 2012
 */
class JavascriptCompiler extends IncludeCompiler {

	protected $extension = 'js';

	protected function getFileContent($file) {
		return file_get_contents($file) . ';';
	}
}
