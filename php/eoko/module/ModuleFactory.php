<?php

namespace eoko\module;

interface ModuleFactory {
	function generateModule($name, &$cacheDeps);
}