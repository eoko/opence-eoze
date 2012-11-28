<?php

namespace eoko\module\executor\html;

use eoko\module\executor\TemplateExecutor;
use eoko\template\HtmlTemplate;
use eoko\template\Renderer;
use eoko\template\RawRenderer;
use eoko\file, eoko\file\FileType;
use eoko\file\MissingFileException, eoko\file\CannotFindFileException;
use \IllegalStateException;
use eoko\util\Arrays;


/**
 * Base Executor class that extends TemplateExecutor to add specific 
 * HtmlTemplate searching and creation methods.
 */
abstract class HtmlTemplateExecutor extends TemplateExecutor {

	/**
	 *
	 * @param string|array $name    the name of the template to be searched,
	 * or an array of string to search for multiple names. If an array is given,
	 * the names will be searched in order, and the first match will be returned.
	 * @param ref $isTpl        a variable reference that will be set to TRUE
	 * if the found file has a template extension (i.e. .html.php), else it
	 * will be set to FALSE (if the extension is .html).
	 * @param boolean $require
	 * @throws \eoko\file\CannotFindFileException
	 * @return string
	 */
	protected function searchTemplatePath($name, &$isTpl = null, $require = false) {
		if (is_array($name)) {
			foreach ($name as $n) {
				if (($filename = $this->searchTemplatePath($n, $isTpl, false))) {
					return $filename;
				}
			}
		} else {
			if (($filename = $this->searchPath($name, FileType::HTML(), $url, true))) {
				$isTpl = false;
				return $filename;
			} else if (($filename = $this->searchPath($name, FileType::HTML_TPL(), $url, true))) {
				$isTpl = true;
				return $filename;
			}
		}

		if ($require) {
			throw new CannotFindFileException($name);
		} else {
			return null;
		}
	}
	
	protected function findTemplatePath($name, &$isTpl = null) {
		return $this->searchTemplatePath($name, $isTpl, true);
	}

	/**
	 * Creates the template for the given $name. The $name can be either the
	 * $filename of an existing file, or the name of a template to be searched.
	 * It can also be an array containing a mix of both, in this case the first
	 * math will be returned.
	 *
	 * The type of template that is created will depend on the file extension.
	 * If the file has a php extension, then a HtmlTemplate will be created,
	 * else a RawRenderer will be created.
	 *
	 * @param string|array $name
	 * @param boolean $require
	 * @param mixed $opts
	 * @throws \eoko\file\MissingFileException
	 * @throws \IllegalStateException
	 * @return \eoko\template\Template
	 */
	protected function createTemplate($name, $require = true, $opts = null) {
		
		if ($this->ajax) {
			Arrays::apply($opts, array(
				'ajaxLinks' => true
			));
		}
		
		if ($name === null) return HtmlTemplate::create($this, $opts);
		
		if (is_array($name)) {
			foreach ($name as $n) {
				if (($tpl = $this->createTemplate($n, false, $opts))) return $tpl;
			}
			if ($require) {
				throw new MissingFileException('Missing template in ' 
						. get_class($this) . ': ' . implode('|', $name));
			} else {
				return null;
			}
		}
		
		if (!file_exists($name)) {
			if (null !== $name = $this->searchTemplatePath($name, $isTpl, $require)) {
				if (!$isTpl) {
					return RawRenderer::create($opts)->setFile($name);
				} else {
					return HtmlTemplate::create($this, $opts)->setFile($name);
				}
			} else {
				return null;
			}
		} else {
			if (FileType::PHP()->testFilename($name)) {
				return HtmlTemplate::create($this, $opts)->setFile($name);
			} else {
				return RawRenderer::create($opts)->setFile($name);
			}
		}
		
		throw new IllegalStateException('Unreachable code');
	}
}
