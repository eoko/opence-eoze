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

namespace eoko\modules\CkEditor;

use eoko\module\Module;
use eoko\module\traits\HasJavascriptFiles;

/**
 * CkEditor module.
 *
 * @category Eoze
 * @package CkEditor
 * @since 2013-01-30 14:32
 */
class CkEditor extends Module implements HasJavascriptFiles {

	public function getModuleJavascriptUrls() {
		return array(
			'base' => array($this->getBaseUrl() . 'ckeditor_custom/ckeditor.js'),
//			'base' => array($this->getBaseUrl() . 'ckeditor/ckeditor.js'),
//			'base' => array($this->getBaseUrl() . 'ckeditor_basic/ckeditor.js'),
		);
	}

}
