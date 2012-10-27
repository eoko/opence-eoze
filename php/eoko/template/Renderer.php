<?php

namespace eoko\template;

use \IllegalStateException, \SystemException;
use eoko\options\Options;
use \Logger;
use \UnsupportedOperationException;
use eoko\module\ModuleManager;
use eoko\util\Arrays;
use eoko\output\Output;

\includeFromNamespace(__NAMESPACE__, 'functions.php');

class CurrentRenderer {
	public static $renderer = null;
	public static function get() {
		return self::$renderer;
	}
}

abstract class Renderer {
	
	/** 
	 * @var boolean Cache the rendering result when it is rendered in a string.
	 * The result will not be cached if it is rendered directly to the output.
	 * @see $forceResultCaching to force caching even when the content is
	 * rendered directly to the output.
	 */
	public $resultCaching = false;
	/**
	 * @var boolean Force the caching of the rendereding result, even when 
	 * rendered directly to the output.
	 */
	public $forceResultCaching = false;
	
	private $cachedResult = null;
	/** @var RendererOptions */
	
	/** @var Renderer may be used by functions in the template namespace */
	public static $currentRenderer;
	
	private $filename = null;
	private $content = null;
	private $contentMD5 = null;
	
	public $subTemplateProvider = null;
	
	private $previousRenderer;
	
	public function __construct($opts = null) {
		Options::apply($this, $opts);
	}
	
	abstract protected function doRender();
	
	/**
	 * @param array $opts
	 * @return Renderer
	 */
	public static function create($opts = null) {
		$class = get_called_class();
		return new $class($opts);
	}
	
	public function renderingErrorHandler($errno, $errstr, $errfile, $errline, $context) {

		Logger::get($this)->error("RENDERING ERROR: $errfile($errline): $errstr");

		// Restore state
		CurrentRenderer::$renderer = $this->previousRenderer;
//		ob_clean();
		restore_error_handler();

		if ($this->filename) {
			$msg = "Error rendering $this->filename($errline): $errstr";
		} else {
			$msg = "Error rendering string at line $errline: $errstr";
		}

		$lines = explode(PHP_EOL, $this->getContent());
		if ($errline-1 < count($lines)) {
			$msg .= ' (' . $lines[$errline-1] . ')';
		} else {
			$msg .= ' (cannot determine error location)';
		}
		
//		restore_error_handler();

		throw new RenderingException(
			$msg, $errno, $errstr, $errfile, $errline, $context
		);
//		// TODO find why the exception is not catched by the exception handler
//		// automatically
//		\ExceptionHandler::processException(
//			new RenderingException(
//				$msg, $errno, $errstr, $errfile, $errline, $context
//			)
//		);
	}
	
	protected function getTemplateFilename() {
		if ($this->filename) {
			return $this->filename;
		} else {
			return '{String}';
		}
	}

	private function performRendering() {
		set_error_handler(array($this, 'renderingErrorHandler'));
		
		$this->previousRenderer = CurrentRenderer::$renderer;
		CurrentRenderer::$renderer = $this;
		
		$this->doRender();
		
		CurrentRenderer::$renderer = $this->previousRenderer;
		restore_error_handler();
	}
	
	public function render($return = null) {
		if ($return) {
			return $this->doRenderToString();
		} else {
			if ($this->cachedResult !== null) {
				Logger::get($this)->debug('Template already rendered, outputing cache');
				Output::out($this->cachedResult);
			} else if ($this->forceResultCaching) {
				Output::out($this->doRenderToString($vars));
			} else {
				$this->performRendering();
			}
		}
	}
	
	private final function doRenderToString() {
		
		if ($this->cachedResult !== null) {
			Logger::get($this)->debug('Template already rendered, returning cache');
			return $this->cachedResult;
		}

		Logger::startBuffer();
		ob_start();
		// Don't call render(), that may result in infinite reccursion
		$this->performRendering();
		$result = ob_get_clean();
		Logger::flush();
		
		if ($this->resultCaching || $this->forceResultCaching) {
			$this->cachedResult = $result;
		}
		
		return $result;
	}
	
	public function __toString() {
		try {
			return $this->doRenderToString();
		} catch (\Exception $e) {
			Logger::get($this)->error('Exception in __toString: ', $e);
			return 'Rendering error';
		}
	}
	
	public function clearCache() {
		$this->cachedResult = null;
	}
	
	/**
	 * @param string $filename
	 * @return Renderer 
	 */
	public function setFile($filename) {
		if ($this->filename !== $filename) {
			$this->filename = $filename;
			$this->clearCache();
		}
		return $this;
	}
	
	/**
	 * @param string $content
	 * @return Renderer 
	 */
	public function setContent($content) {
		// ensure cache is kept up to date, if used
		if ($this->resultCaching || $this->forceResultCaching) {
			$md5 = md5($string);
			if ($this->contentMD5 !== $md5) {
				$this->contentMD5 = $md5;
				$this->clearCache();
				$this->content = $content;
			}
		} else {
			$this->content = $content;
		}
		
		return $this;
	}
	
	public function isContentSet() {
		return $this->filename || $this->content;
	}
	
	protected function getContent() {
		if ($this->filename) {
			return file_get_contents($this->filename);
		} else if ($this->content !== null) {
			return $this->content;
		} else {
			throw new \IllegalStateException(
				"Template content has not been set yet"
			);
		}
	}
	
	public function renderFile($filename, $return = null) {
		return $this->setFile($filename)->render($return);
	}
	
	public function renderString($content, $return = null) {
		return $this->setContent($content)->render($return);
	}
	
	private $subTemplateExecutor = null;
	
//	private function getSubTemplateExecutor($module) {
//		if ($module === null) {
//			if ($this->defaultSubTemplateModule !== null) {
//				$module = $this->defaultSubTemplateModule;
//			} else {
//				throw new IllegalStateException('Default sub templates provider module no set');
//			}
//		}
//		if (isset($this->subTemplateExecutor[$module])) {
//			return $this->subTemplateExecutor[$module];
//		} else {
//			ModuleManager::getModule($module)->getSubTemplateExecutor
//		}
//		if ($this->subTemplateExecutor) {
//			return $this->subTemplateExecutor;
//		} else if (!$this->subTemplateProvider) {
//			return $this->subTemplateExecutor$this->subTemplateProvider->
//			
//	}
	
	public function getSubTemplate($name, $controller = null, $opts = null) {
		
		if ($opts === null) {
			$opts = array('name' => $name);
		} else {
			Arrays::apply($opts, array('name' => $name));
		}

		throw new UnsupportedOperationException(
'ModuleResolver::parseAction has been modified, this method must be adapted to use the new form.
Old signature was:
public static function parseAction($controller, $action, $request, $defaultExecutor = Module::DEFAULT_EXECUTOR, $require = true)'
		);
		$action = ModuleResolver::parseAction($controller, Module::DEFAULT_INTERNAL_EXECUTOR, $name);
		return $action($opts);

//		$executor = ModuleManager::parseExecutor($controller, true);
//		return $this->subTemplateProvider->get($name, $opts);
	}
	
}

class RenderingException extends SystemException {
	
	private $errno, $errstr, $errfile, $errline, $context;

	function __construct($msg, $errno, $errstr, $errfile, $errline, $context, $previous = null) {
		$this->errno = $errno;
		$this->errstr = $errstr;
		$this->errfile = $errfile;
		$this->errline = $errline;
		$this->context = $context;
		parent::__construct($msg, '', $previous);
	}
	
}