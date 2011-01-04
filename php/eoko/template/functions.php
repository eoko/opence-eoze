<?php

namespace eoko\template;

use eoko\file\FileType;
use eoko\url\Maker as UrlMaker;
use eoko\i18n\Language;

function lang() {
	dump('fuck');
	return Language::callTranslate(func_get_args());
}

function _lang() {
	echo Language::callTranslate(func_get_args());
}

function _() {
	echo Language::callTranslate(func_get_args());
}

function _jsString($text) {
	echo str_replace(
		"\n", ' ',
		str_replace('"', '\"', $text)
	);
}

function useCss($css, $order = null, $require = true) {
	CurrentRenderer::get()->pushCss($css, $order, $require);
}

function useJs($js, $order = null, $require = true) {
	CurrentRenderer::get()->pushJs($js, $order, $require);
}

function tabEcho($tabs, $text) {
	if (!CurrentRenderer::get()->renderPretty) {
		echo $text;
	} else {
		if (is_int($tabs)) $tabs = \str_repeat("\t", $tabs);
		echo str_replace("\n", "\n$tabs", $text);
	}
}

function echoIf($value, $defaultValue, $excludes = array(null,'')) {
	if (!is_array($excludes)) {
		echo ($excludes === $value ? $defaultValue : $value);
	} else {
		foreach ($excludes as $ex) {
			if ($ex === $value) {
				echo $defaultValue;
				return;
			}
		}
		// not excluded
		echo $value;
	}
}

/**
 * Get the absolute url for the specified Controller's action.
 * @param string $controller
 * @param string $action
 * @param array $params
 * @return string 
 */
function urlFor($controller, $action = null, $params = null, $anchor = null) {
	if ($anchor !== null) $anchor = "#$anchor";
	return UrlMaker::getFor($controller, $action, $params) . $anchor;
}

/**
 * Echoes the absolute url for the specified Controller's action.
 * @param string $controller
 * @param string $action
 * @param string $params 
 */
function _urlFor($controller, $action = null, $params = null, $anchor = null) {
	echo urlFor($controller, $action, $params, $anchor);
}

function linkTo($url, $text, $extra = null) {
	
	$extras = array();
	if ($extra) {
		foreach ($extra as $k => $v) {
			$extras[] = "$k=\"$v\"";
		}
	}

	if (($renderer = CurrentRenderer::get()) instanceof HtmlTemplate) {
		$renderer->addLinkExtra($extras, $url);
//		$extras[] = <<<JS
//onClick="return Oce.html.update('$url')"
//JS;
	}

	$extras = implode(' ', $extras);
	
	return 
<<<CODE
<a href="$url" $extras>$text</a>
CODE;
}

function _linkTo($url, $text, $extra = null) {
	echo linkTo($url, $text, $extra);
}

function imageUrl($name, $type = FileType::IMAGE) {
	return CurrentRenderer::get()->findImageUrl($name, $type);
}

function imgUrl($name, $type = FileType::IMAGE) {
	return imageUrl($name, $type);
}

function _imageUrl($name, $type = FileType::IMAGE) {
	echo imageUrl($name, $type);
}

function _imgUrl($name, $type = FileType::IMAGE) {
	_imageUrl($name, $type);
}

function imageTag($name, $params = null, $type = FileType::IMAGE) {
	$url = imageUrl($name);
	$params = Html::concateParams($params, $type);
	return <<<TAG
<img src="$url" $params/>
TAG;
}

function _imageTag($name, $params = null, $type = FileType::IMAGE) {
	echo imageTag($name, $params);
}

function template($name, $opts = null) {
	return CurrentRenderer::get()->getSubTemplate($name, $opts);
}

function _template($name, $opts = null) {
	echo template($name, $opts);
}