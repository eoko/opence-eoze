<?php

namespace eoko\template\Template;

interface PdfRenderingEngine {

	const ORIENTATION_PORTRAIT =  'P';
	const ORIENTATION_LANDSCAPE = 'L';

	function toOutput($html);

	function toFile($html);

	function toVariable($html);

	function setFilename($filename);

	function setOrientation($orientation);

	function setLanguage($lang);

	function setFormat($format);
}
