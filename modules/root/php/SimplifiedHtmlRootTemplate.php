<?php

namespace eoko\modules\root;

use eoko\template\HtmlRootTemplate;

use eoko\template\Renderer;

/**
 * HtmlRootTemplate with simplified logic. A step toward cleaning this part of the
 * application.
 * 
 * Eoze applications do not need a powerful page rendering system, since they just
 * need to create their simple index.html file. Applications with complex MVC
 * requirements should not use Eoze MVC engine (but ZF2, for example).
 *
 * @method static SimplifiedHtmlRootTemplate create(\eoko\file\Finder $fileFinder = null, $opts = null)
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 18 sept. 2012
 */
class SimplifiedHtmlRootTemplate extends HtmlRootTemplate {

	private $forbidIncludePush = false;

	protected function pushInclude($type, $url, $order) {
		throw new \IllegalStateException(
			'Pushing includes in templates has been deprecated.'
		);
	}

	private $jsUrls, $cssUrls;

	public function setIncludeUrls(array $includes) {
		$this->jsUrls = $includes['js'];
		$this->cssUrls = $includes['css'];
	}

	protected function onRender() {

		if (!isset($this->docType)) {
			$this->set('docType', 
<<<EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
EOD
			, false);
		}

		// --- Push includes
		if (null !== $this->headTemplateName) {
			if (!isset($this->{$this->headTemplateName})) {
				throw new IllegalStateException(
<<<MSG
Missing head template in RootHtmlTemplate (property: $this->headTemplateName)
MSG
				);
			}

			// Push includes in head template
			$head = $this->{$this->headTemplateName};

			if ($this->compileOptions) {
				$this->onCompileIncludes($head, $this->compileOptions);
			}

			$head->css = $this->cssUrls;
			$head->js = $this->jsUrls;
		}

		// --- Render
		foreach ($this->vars as $var) {
			if ($var instanceof Renderer) {
				$var->forceResultCaching = true;
				$var->render(true);
			}
		}
	}
}
