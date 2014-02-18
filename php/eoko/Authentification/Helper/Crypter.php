<?php

namespace eoko\Authentification\Helper;
use eoko\config\Application;

/**
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2014-02-18 18:23
 */
class Crypter {

	private $keyLength = 64;

	public function __construct() {
		$pathSpec = 'var/' . str_replace('\\', '/', get_class($this));
		$keyFile = Application::getInstance()->resolvePath($pathSpec) . '/key';

		if (file_exists($keyFile)) {
			$this->key = file_get_contents($keyFile);
		} else {
			$this->key = $this->secureRand($this->keyLength);
			file_put_contents($keyFile, $this->key);
			if (file_get_contents($keyFile) !== $this->key) {
				throw new \RuntimeException();
			}
		}
	}

	// http://www.zimuel.it/strong-cryptography-in-php/
	private function secureRand($length) {
		if(function_exists('openssl_random_pseudo_bytes')) {
			$rnd = openssl_random_pseudo_bytes($length, $strong);
			if ($strong === TRUE)
				return $rnd;
		}
		$sha =''; $rnd ='';
		if (file_exists('/dev/urandom')) {
			$fp = fopen('/dev/urandom', 'rb');
			if ($fp) {
				if (function_exists('stream_set_read_buffer')) {
					stream_set_read_buffer($fp, 0);
				}
				$sha = fread($fp, $length);
				fclose($fp);
			}
		}
		for ($i=0; $i<$length; $i++) {
			$sha  = hash('sha256',$sha.mt_rand());
			$char = mt_rand(0,62);
			$rnd .= chr(hexdec($sha[$char].$sha[$char+1]));
		}
		return $rnd;
	}

	public function encrypt($value) {
		return base64_encode(
			mcrypt_encrypt(
				MCRYPT_RIJNDAEL_256, md5($this->key), $value, MCRYPT_MODE_CBC, md5(md5($this->key))
			)
		);
	}

	public function decrypt($value) {
		return rtrim(
			mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256, md5($this->key), base64_decode($value), MCRYPT_MODE_CBC, md5(md5($this->key))
			), "\0"
		);
	}
}
