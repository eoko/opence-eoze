<?php

namespace eoko\module;

interface HasVersion {

	/**
	 * Get the Module's title.
	 * @return string The title.
	 */
	function getVersion();

	function compareVersions();
}
