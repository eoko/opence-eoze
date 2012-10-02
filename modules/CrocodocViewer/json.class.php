<?php

namespace eoko\modules\CrocodocViewer;

use eoko\module\executor\JsonExecutor;
use eoko\log\Logger;

class Json extends JsonExecutor {

	public function test() {
		$this->value = 1;
		return true;
	}

	public function index() {
		$url = 'http://www.dcaa.mil/chap6.pdf';
		$api = $this->module->getConfig()->apiKey;
		$url = "https://crocodoc.com/api/v1/document/upload?token=$api";

		$url .= "&";

		$filename = $this->request->req('filename');
//		$url = SITE_BASE_URL . $docUrl;

//		Logger::get($this)->debug('Requesting crocodoc url: {}', $url);

//		$answer = file_get_contents(
//			"https://crocodoc.com/api/v1/document/upload?url=$url&token=$api"
//		);

		$this->sessionId = $this->uploadToCrocodoc($filename);

		return true;
	}

	private function uploadToCrocodoc($filename) {
		$api = $this->module->getConfig()->apiKey;

		$filename = MEDIA_PATH . $filename;
		$url = "https://crocodoc.com/api/v1/document/upload?token=$api&private=true";
		Logger::get($this)->debug('Requesting crocodoc url: {}', $url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		// same as <input type="file" name="file_box">
		$post = array(
			"file"=>"@$filename",
		);
//		dump(array($post, $url));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = json_decode(curl_exec($ch));

		$response = json_decode(
			file_get_contents("https://crocodoc.com/api/v1/session/get?uuid=$response->uuid&editable=false&token=$api")
		);

		return $response->sessionId;
	}
}
