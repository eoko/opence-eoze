<?php

namespace eoko\modules\MediaManager;

use eoko\file\Mime;
use eoko\module\executor\ExecutorBase;
use Zend\Http\Response;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 août 2012
 */
class download extends ExecutorBase {

// 2013-03-14 10:49
//<<<<<<< local
//	protected function processResult($result) {
//		return true;
//	}
//
//=======
//>>>>>>> other
	public function download() {

		$app = $this->getApplication();
		$response = $this->getResponse();

		// #auth
		if (!$app->getUserSession()->isAuthorized(100)) {
			if ($app->getActiveUserId() !== null) {
				$response->setStatusCode(Response::STATUS_CODE_403);
			} else {
				$response->setStatusCode(Response::STATUS_CODE_401);
			}
			return $response;
		}

		$path = $this->getModule()->getDownloadPath($this->request->req('path'));

		if (file_exists($path)) {
			$filename = basename($path);
			$response->getHeaders()->addHeaderLine('Content-Type: ' . self::getMime($path));
			$response->getHeaders()->addHeaderLine('Content-Disposition: attachment; filename="' . $filename . '"');
			$response->setContent(file_get_contents($path));
		} else {
			$response->setStatusCode(Response::STATUS_CODE_404);
		}

		return $response;
	}

	private static function getMime($filename) {
		$default = 'application/octet-stream';
		return Mime::fromFile($filename, $default);
	}
}
