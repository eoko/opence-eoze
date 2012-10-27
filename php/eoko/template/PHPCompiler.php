<?php

namespace eoko\template;

use \SystemException;

/**
 * @method PHPCompiler create($opts)
 */
class PHPCompiler extends Template {
	
	private $compiled = false;
	
	protected function getContent() {
		$content = parent::getContent();
		
		// trim the first <?php tag
		if (preg_match('/^\s*<\?(?i)php(?-i)\s(.*)$/s', $content, $m)) {
			$content = $m[1];
		}
		
		return $content;
	}
	
	public function compile($outFilename = null) {
		
		if (!$this->compiled) $this->compiled = true;
		else return true;

		try {
			$code = $this->render(true);
		} catch (\Exception $ex) {
			throw new GenerationException($ex);
			return false;
		}

//		dumpl($code);
		
		if ($outFilename !== null) {
			if (file_exists($outFilename)) unlink($outFilename);
			// Logger::dbg('Writting file: {}', $filename);
			
			$file = fopen($outFilename, 'w');
			fwrite($file, "<?php\n$code");
			fclose($file);
			
			require_once $outFilename;
		} else {
			set_error_handler(array($this, 'generation_error_handler'));
//			try {
				// Logger::getLogger()->debug("Generated code:\n**{}**", $code);
				eval($code);
//			} catch (Exception $ex) {
//				throw new GenerationException($ex);
//				return false;
//			}
			restore_error_handler();
		}
		
		return true;
	}
	
	public function generation_error_handler($errno, $errstr, $errfile, $errline, $context) {
		$ex = new GenerationException(null);
		$ex->setErrorInfo(
			'Template compilation error: ' . $this->getTemplateFilename()
				. "($errline): $errstr", 
			$errno, $errstr, $errfile, $errline, $context
		);
		throw $ex;
	}
	
}

class GenerationException extends SystemException {
	
	private $errno, $errstr, $errfile, $errline, $context;
	
	function  __construct(\Exception $previous = null) {
		$debugMessage = 'Template compilation error: ' . $previous;
		parent::__construct($debugMessage, null, $previous);
	}
	
	public function setErrorInfo($msg, $errno, $errstr, $errfile, $errline, $context) {
		$this->errno = $errno;
		$this->errstr = $errstr;
		$this->errfile = $errfile;
		$this->errline = $errline;
		$this->context = $context;
	}
}
