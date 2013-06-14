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
use eoko\module\executor\ExecutorBase;

/**
 * @todo doc
 *
 * @since 2013-06-18 15:06
 */
class LZStringWorker extends ExecutorBase {

	public function index() {
		$response = $this->getResponse();

		$response->getHeaders()->addHeaders(array(
			'Content-Type' => 'text/javascript',
		));

		$content = file_get_contents(__DIR__ . '/js.auto/lz-string-1.3.0-rc1.js');

		$content .= <<<JS
onmessage = function(msg) {
	var data = msg.data;
	switch (data.event) {
		case 'compress':
			postMessage({
				event: 'compressed'
				,id: data.id
				,data: LZString.compress(data.data)
			});
			break;
		case 'uncompress':
		case 'decompress':
			postMessage({
				event: 'decompressed'
				,id: data.id
				,data: LZString.decompress(data.data)
			});
			break;
	}
};
JS;
		$response->setContent($content);

		return $response;
	}
}
